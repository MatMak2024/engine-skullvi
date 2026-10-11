<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Services\Scoring\ScoringCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'candidate_id',
    'program_id',
    'course_id',
    'offer_id',
    'education_level',
    'field_of_study',
    'years_experience',
    'projects_count',
    'github_url',
    'portfolio_url',
    'linkedin_url',
    'availability',
    'motivation_text',
    'cv_path',
    'status',
    'submitted_at',
])]
class Application extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'candidate_id', 'user_id');
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function scoringCatalog(): ScoringCatalog
    {
        $this->loadMissing([
            'program.skills',
            'program.courses.skills',
            'offer.skills',
            'course.skills',
            'course.program',
        ]);

        if ($this->offer !== null) {
            return ScoringCatalog::fromOffer($this->offer);
        }

        if ($this->course !== null) {
            return ScoringCatalog::fromCourse($this->course);
        }

        return ScoringCatalog::fromProgram($this->program);
    }

    public function targetName(): string
    {
        return $this->offer?->name
            ?? $this->course?->name
            ?? $this->program?->name
            ?? 'Candidature';
    }

    public function targetKind(): string
    {
        if ($this->offer_id !== null) {
            return 'Offre';
        }

        if ($this->course_id !== null) {
            return 'Cours';
        }

        return 'Programme';
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'application_skill')
            ->withPivot('level');
    }

    /**
     * @return HasMany<Evaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * The evaluation currently used to rank this application.
     *
     * @return HasOne<Evaluation, $this>
     */
    public function currentEvaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class)->where('is_current', true);
    }

    /**
     * @return HasMany<ReviewNote, $this>
     */
    public function reviewNotes(): HasMany
    {
        return $this->hasMany(ReviewNote::class);
    }

    /**
     * @return HasMany<ApplicationDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'education_level' => EducationLevel::class,
            'availability' => Availability::class,
            'status' => ApplicationStatus::class,
            'submitted_at' => 'datetime',
            'examination_authorized_at' => 'datetime',
        ];
    }
}
