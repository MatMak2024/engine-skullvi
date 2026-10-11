<?php

namespace Tests\Unit\Scoring;

use App\Enums\ApplicationStatus;
use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\SkillLevel;
use App\Models\Evaluation;
use App\Services\Scoring\AvailabilityCriterion;
use App\Services\Scoring\EducationCriterion;
use App\Services\Scoring\ExperienceCriterion;
use App\Services\Scoring\MotivationCriterion;
use App\Services\Scoring\ProofsCriterion;
use App\Services\Scoring\ScoringService;
use App\Services\Scoring\SkillsCriterion;
use App\Services\Scoring\SuggestedStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_criterion_follows_the_rubric(): void
    {
        $program = TalentWorld::program([], ['PHP', 'Laravel', 'React', 'SQL'], ['Git']);
        $skills = $program->skills->keyBy('name');

        $application = TalentWorld::application($program, [
            'education_level' => EducationLevel::Licence,
            'years_experience' => 8,
            'projects_count' => 2,
            'github_url' => 'https://github.com/example',
            'portfolio_url' => 'https://example.com/not-a-portfolio-wait',
            'availability' => Availability::OneMonth,
            'motivation_text' => TalentWorld::text('Je pratique laravel au quotidien. ', 220),
        ], [
            $skills['PHP']->id => SkillLevel::Advanced->value,
            $skills['Laravel']->id => SkillLevel::Advanced->value,
            $skills['React']->id => SkillLevel::Advanced->value,
            $skills['Git']->id => SkillLevel::Advanced->value,
        ]);

        $skillsScore = app(SkillsCriterion::class)->evaluate($application);
        $this->assertEqualsWithDelta(23.75, $skillsScore->points, 0.01);
        $this->assertSame(30.0, $skillsScore->maxPoints);

        $experience = app(ExperienceCriterion::class)->evaluate($application);
        $this->assertSame(14.0, $experience->points);

        $education = app(EducationCriterion::class)->evaluate($application);
        $this->assertSame(10.0, $education->points);

        $proofs = app(ProofsCriterion::class)->evaluate($application);
        $this->assertSame(13.0, $proofs->points);

        $withCv = TalentWorld::application($program, [
            'github_url' => 'https://github.com/example',
            'cv_path' => 'cvs/exemple.pdf',
        ]);
        $program->forceFill([
            'requested_documents' => ['Diplôme', 'Relevé'],
        ])->save();
        $withCv->documents()->create([
            'label' => 'Diplôme',
            'path' => 'documents/diplome.pdf',
        ]);
        $proofsWithCv = app(ProofsCriterion::class)->evaluate($withCv->fresh());
        $this->assertSame(12.0, $proofsWithCv->points);
        $this->assertContains('missing_qualifications', $proofsWithCv->flags);

        $motivation = app(MotivationCriterion::class)->evaluate($application);
        $this->assertEqualsWithDelta(6.0, $motivation->points, 0.01);

        $availability = app(AvailabilityCriterion::class)->evaluate(
            TalentWorld::application($program, ['availability' => Availability::Immediate]),
        );
        $this->assertSame(5.0, $availability->points);
        $this->assertSame(3.0, app(AvailabilityCriterion::class)->evaluate($application)->points);
        $this->assertSame(1.0, app(AvailabilityCriterion::class)->evaluate(
            TalentWorld::application($program, ['availability' => Availability::Later]),
        )->points);
    }

    public function test_reference_profile_scores_65_and_suggests_to_review(): void
    {
        $names = [];
        for ($index = 0; $index < 10; $index++) {
            $names[] = 'Competence '.$index;
        }

        $program = TalentWorld::program([
            'work_mode' => 'onsite',
            'work_country' => 'Togo',
            'work_city' => 'Lomé',
        ], $names);
        $levels = [];
        foreach ($program->skills->values() as $index => $skill) {
            $levels[$skill->id] = $index === 0 ? SkillLevel::Beginner->value : SkillLevel::Advanced->value;
        }

        $application = TalentWorld::application($program, [
            'education_level' => EducationLevel::Licence,
            'years_experience' => 3,
            'projects_count' => 3,
            'github_url' => 'https://github.com/example',
            'availability' => Availability::Immediate,
            'motivation_text' => TalentWorld::text('Je veux progresser avec laravel sur des projets concrets. ', 220),
        ], $levels);

        $evaluation = app(ScoringService::class)->score($application);
        $points = $evaluation->scores->mapWithKeys(
            fn ($score) => [$score->criterion->value => (float) $score->points],
        );

        $this->assertEqualsWithDelta(24, $points['skills'], 0.01);
        $this->assertSame(12.0, $points['experience']);
        $this->assertSame(10.0, $points['education']);
        $this->assertSame(8.0, $points['proofs']);
        $this->assertEqualsWithDelta(6, $points['motivation'], 0.01);
        $this->assertSame(5.0, $points['availability']);
        $this->assertSame(0.0, $points['location']);
        $this->assertSame(65, $evaluation->total_score);
        $this->assertSame(ApplicationStatus::ToReview, $evaluation->suggested_status);
        $this->assertSame('v2', $evaluation->rubric_version);
        $this->assertSame(ApplicationStatus::Received, $application->fresh()->status);
    }

    public function test_a_complete_profile_can_reach_priority_and_an_empty_one_stays_incomplete(): void
    {
        $program = TalentWorld::program([], ['PHP'], ['Git']);
        $skills = $program->skills->keyBy('name');
        $levels = [
            $skills['PHP']->id => SkillLevel::Advanced->value,
            $skills['Git']->id => SkillLevel::Advanced->value,
        ];

        $perfect = TalentWorld::application($program, [
            'phone' => '+221770000000',
            'education_level' => EducationLevel::Master,
            'field_of_study' => 'Informatique',
            'years_experience' => 5,
            'projects_count' => 5,
            'github_url' => 'https://github.com/example',
            'portfolio_url' => 'https://example.com/portfolio',
            'linkedin_url' => 'https://www.linkedin.com/in/example',
            'availability' => Availability::Immediate,
            'motivation_text' => TalentWorld::text('Je pratique laravel et react chaque semaine. ', 400),
            'cv_path' => 'cvs/exemple.pdf',
        ], $levels);
        $perfect->candidate->user->forceFill([
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'address' => '12 avenue Cheikh Anta Diop',
        ])->save();

        $evaluation = app(ScoringService::class)->score($perfect->fresh());

        $this->assertSame(100, $evaluation->total_score);
        $this->assertSame(100, $evaluation->completeness_rate);
        $this->assertSame(ApplicationStatus::Priority, $evaluation->suggested_status);

        $empty = TalentWorld::application($program, [
            'country' => 'Sénégal',
            'education_level' => EducationLevel::None,
            'years_experience' => 0,
            'projects_count' => 0,
            'availability' => Availability::Later,
            'motivation_text' => '',
        ]);

        $emptyEvaluation = app(ScoringService::class)->score($empty);

        $this->assertSame(5, $emptyEvaluation->total_score);
        $this->assertSame(ApplicationStatus::Incomplete, $emptyEvaluation->suggested_status);
        $this->assertContains('no_skills', $emptyEvaluation->flags);
        $this->assertContains('short_motivation', $emptyEvaluation->flags);
    }

    public function test_invalid_links_score_zero_and_caps_limit_experience(): void
    {
        $program = TalentWorld::program();
        $application = TalentWorld::application($program, [
            'years_experience' => 12,
            'projects_count' => 9,
            'github_url' => 'https://example.com/not-github',
            'linkedin_url' => 'pas-une-url',
        ]);

        $evaluation = app(ScoringService::class)->score($application);
        $points = $evaluation->scores->mapWithKeys(
            fn ($score) => [$score->criterion->value => (float) $score->points],
        );

        $this->assertSame(20.0, $points['experience']);
        $this->assertSame(0.0, $points['proofs']);
        $this->assertContains('invalid_github', $evaluation->flags);
        $this->assertContains('invalid_linkedin', $evaluation->flags);
    }

    public function test_a_closed_program_is_ineligible_and_rescore_replaces_the_current_evaluation(): void
    {
        $program = TalentWorld::program(['is_open' => false], ['PHP']);
        $skill = $program->skills->first();
        $application = TalentWorld::application($program, [
            'education_level' => EducationLevel::Master,
            'years_experience' => 5,
            'projects_count' => 5,
            'availability' => Availability::Immediate,
        ], [
            $skill->id => SkillLevel::Advanced->value,
        ]);

        $first = app(ScoringService::class)->score($application);
        $this->assertSame(ApplicationStatus::Ineligible, $first->suggested_status);

        $application->update(['years_experience' => 0, 'projects_count' => 0]);
        $second = app(ScoringService::class)->score($application->fresh());

        $this->assertFalse($first->fresh()->is_current);
        $this->assertTrue($second->is_current);
        $this->assertSame(2, Evaluation::query()->count());
        $this->assertNotSame($first->total_score, $second->total_score);
    }

    public function test_thresholds_separate_fit_from_eligibility(): void
    {
        $status = app(SuggestedStatus::class);

        $this->assertSame(ApplicationStatus::Priority, $status->from(70, true, true));
        $this->assertSame(ApplicationStatus::ToReview, $status->from(69, true, true));
        $this->assertSame(ApplicationStatus::ToReview, $status->from(50, true, true));
        $this->assertSame(ApplicationStatus::LowFit, $status->from(49, true, true));
        $this->assertSame(ApplicationStatus::Incomplete, $status->from(90, true, false));
        $this->assertSame(ApplicationStatus::Ineligible, $status->from(90, false, true));
    }
}
