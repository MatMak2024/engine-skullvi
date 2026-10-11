<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ScoringCriterion;
use App\Models\Application;
use App\Models\ReviewNote;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use App\Services\Scoring\ScoringService;
use Illuminate\Support\Collection;

class ApplicationReviewService
{
    public function __construct(private ScoringService $scoring) {}

    /**
     * Applications ranked by current score, then verifiable proofs, then submission date.
     *
     * @return Collection<int, Application>
     */
    public function ranked(?string $programId = null, ?string $status = null, ?string $offerId = null, ?string $courseId = null): Collection
    {
        return Application::query()
            ->with([
                'candidate.user:id,first_name,name,email',
                'program:id,name',
                'course:id,name',
                'offer:id,name',
                'currentEvaluation:id,application_id,total_score,completeness_rate,suggested_status,flags,rubric_version',
                'currentEvaluation.scores:id,evaluation_id,criterion,points,max_points,explanation',
            ])
            ->when(filled($programId), fn ($query) => $query->where('program_id', $programId))
            ->when(filled($offerId), fn ($query) => $query->where('offer_id', $offerId))
            ->when(filled($courseId), fn ($query) => $query->where('course_id', $courseId))
            ->when(filled($status), fn ($query) => $query->where('status', $status))
            ->get()
            ->sortBy(fn (Application $application) => [
                -(float) ($application->currentEvaluation?->total_score ?? -1),
                -(float) ($application->currentEvaluation?->scores->firstWhere('criterion', ScoringCriterion::Proofs)?->points ?? 0),
                $application->submitted_at?->getTimestamp() ?? PHP_INT_MAX,
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Application $application): array
    {
        return [
            'id' => $application->id,
            'status' => $application->status->value,
            'submitted_at' => $application->submitted_at,
            'score' => $application->currentEvaluation?->total_score,
            'examined' => $application->examination_authorized_at !== null,
            'suggested_status' => $application->currentEvaluation?->suggested_status?->value,
            'candidate' => [
                'name' => $application->candidate->user->displayName(),
                'email' => $application->candidate->user->email,
            ],
            'program' => $this->targetPayload($application),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Application $application): array
    {
        return [
            'id' => $application->id,
            'status' => $application->status->value,
            'submitted_at' => $application->submitted_at,
            'education_level' => $application->education_level->value,
            'field_of_study' => $application->field_of_study,
            'years_experience' => $application->years_experience,
            'projects_count' => $application->projects_count,
            'github_url' => $application->github_url,
            'portfolio_url' => $application->portfolio_url,
            'linkedin_url' => $application->linkedin_url,
            'availability' => $application->availability->value,
            'motivation_text' => $application->motivation_text,
            'cv_url' => filled($application->cv_path) ? route('applications.cv', $application) : null,
            'examined' => $application->examination_authorized_at !== null,
            'documents' => $application->documents->map(fn ($document) => [
                'id' => $document->id,
                'label' => $document->label,
                'url' => route('applications.document', [$application, $document]),
            ])->values(),
            'candidate' => [
                'name' => $application->candidate->user->displayName(),
                'email' => $application->candidate->user->email,
                'phone' => $application->candidate->phone,
                'country' => $application->candidate->country,
                'city' => $application->candidate->city,
                'interests' => $application->candidate->interests ?? [],
                'other_skills' => $application->candidate->other_skills ?? [],
                'diplomas' => $application->candidate->diplomas->map(fn ($diploma) => [
                    'title' => $diploma->title,
                    'institution' => $diploma->institution,
                    'year' => $diploma->year,
                    'description' => $diploma->description,
                ])->values(),
                'trainings' => $application->candidate->trainings->map(fn ($training) => [
                    'title' => $training->title,
                    'organization' => $training->organization,
                    'year' => $training->year,
                    'description' => $training->description,
                ])->values(),
            ],
            'program' => $this->targetPayload($application),
            'skills' => $application->skills->map(fn ($skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'level' => $skill->pivot->level,
            ])->values(),
            'evaluation' => $application->currentEvaluation ? [
                'total_score' => $application->currentEvaluation->total_score,
                'completeness_rate' => $application->currentEvaluation->completeness_rate,
                'suggested_status' => $application->currentEvaluation->suggested_status->value,
                'rubric_version' => $application->currentEvaluation->rubric_version,
                'scores' => $application->currentEvaluation->scores->map(fn ($score) => [
                    'criterion' => $score->criterion->value,
                    'points' => $score->points,
                    'max_points' => $score->max_points,
                    'explanation' => $score->explanation,
                ])->values(),
            ] : null,
            'notes' => $application->reviewNotes->map(fn ($note) => [
                'id' => $note->id,
                'note' => $note->note,
                'author' => $note->author->displayName(),
                'created_at' => $note->created_at,
            ])->values(),
        ];
    }

    /**
     * @return array{id: string, name: string}
     */
    private function targetPayload(Application $application): array
    {
        $application->loadMissing(['program:id,name', 'course:id,name', 'offer:id,name']);

        return [
            'id' => (string) ($application->offer_id ?? $application->course_id ?? $application->program_id),
            'name' => $application->targetKind().' · '.$application->targetName(),
        ];
    }

    public function loadForReview(Application $application): Application
    {
        $application->load([
            'candidate.user:id,first_name,name,email',
            'candidate.diplomas',
            'candidate.trainings',
            'program:id,name',
            'course:id,name',
            'offer:id,name',
            'skills',
            'documents',
            'currentEvaluation.scores',
            'reviewNotes.author:id,name',
        ]);

        return $application;
    }

    public function authorizeExamination(Application $application): Application
    {
        if ($application->examination_authorized_at !== null) {
            return $application;
        }

        $application->forceFill([
            'examination_authorized_at' => now(),
        ])->save();

        $this->scoring->score($application->fresh());

        return $application->fresh();
    }

    public function updateStatus(Application $application, ApplicationStatus $status): Application
    {
        $previous = $application->status;

        $application->update([
            'status' => $status,
        ]);

        if ($previous !== $status) {
            $application->loadMissing('candidate.user');
            $application->candidate?->user?->notify(
                new ApplicationStatusNotification($application->fresh()),
            );
        }

        return $application;
    }

    public function addNote(Application $application, User $author, string $note): ReviewNote
    {
        return $application->reviewNotes()->create([
            'user_id' => $author->id,
            'note' => $note,
        ]);
    }
}
