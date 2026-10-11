<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\SkillLevel;
use App\Models\Offer;
use App\Models\Program;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CandidateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_save_a_professional_profile_with_cv(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['country' => 'Sénégal']);
        $skill = Skill::query()->create([
            'name' => 'Laravel',
            'is_open' => true,
        ]);

        $this->actingAs($user)
            ->post(route('candidate-profile.update'), [
                'phone' => '770000000',
                'country' => 'Sénégal',
                'city' => 'Dakar',
                'education_level' => EducationLevel::Licence->value,
                'field_of_study' => 'Informatique',
                'years_experience' => 3,
                'projects_count' => 4,
                'availability' => Availability::Immediate->value,
                'github_url' => 'https://github.com/exemple',
                'interests' => ['Open source', 'Design'],
                'skills' => [
                    ['id' => $skill->id, 'level' => SkillLevel::Advanced->value],
                ],
                'diplomas' => [
                    [
                        'title' => 'Licence informatique',
                        'institution' => 'UCAD',
                        'year' => 2022,
                        'description' => null,
                    ],
                ],
                'trainings' => [
                    [
                        'title' => 'Bootcamp Laravel',
                        'organization' => 'SKULLVI',
                        'year' => 2024,
                        'description' => null,
                    ],
                ],
                'cv' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('candidate-profile.edit'));

        $candidate = $user->fresh()->candidate;

        $this->assertNotNull($candidate);
        $this->assertSame('Dakar', $candidate->city);
        $this->assertTrue($candidate->hasCv());
        $this->assertSame(['Open source', 'Design'], $candidate->interests);
        $this->assertDatabaseHas('candidate_skill', [
            'candidate_id' => $user->id,
            'skill_id' => $skill->id,
            'level' => SkillLevel::Advanced->value,
        ]);
        $this->assertDatabaseHas('candidate_diplomas', [
            'candidate_id' => $user->id,
            'title' => 'Licence informatique',
        ]);
        $this->assertDatabaseHas('candidate_trainings', [
            'candidate_id' => $user->id,
            'title' => 'Bootcamp Laravel',
        ]);

        $this->actingAs($user->fresh())
            ->get(route('candidate-profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('candidate/profile')
                ->has('account.email')
                ->has('skillOptions')
                ->where('candidate.city', 'Dakar')
                ->where('candidate.has_cv', true));
    }

    public function test_application_create_receives_rich_profile_payload(): void
    {
        $user = User::factory()->create();
        $skill = Skill::query()->create([
            'name' => 'React',
            'is_open' => true,
        ]);

        $candidate = $user->candidate()->create([
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'education_level' => EducationLevel::Licence,
            'years_experience' => 2,
            'projects_count' => 3,
            'availability' => Availability::Immediate,
            'interests' => ['Open source'],
            'other_skills' => [
                ['name' => 'Figma', 'level' => SkillLevel::Intermediate->value],
            ],
        ]);
        $candidate->skills()->attach($skill->id, ['level' => SkillLevel::Advanced->value]);
        $candidate->diplomas()->create([
            'title' => 'Licence informatique',
            'institution' => 'UCAD',
            'year' => 2022,
        ]);
        $candidate->trainings()->create([
            'title' => 'Bootcamp',
            'organization' => 'SKULLVI',
            'year' => 2024,
        ]);

        $program = Program::query()->create([
            'name' => 'Programme test',
            'slug' => 'programme-test',
            'is_open' => true,
            'work_mode' => 'remote',
        ]);

        $this->actingAs($user)
            ->get(route('applications.create', $program))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('applications/create')
                ->where('candidate.city', 'Dakar')
                ->where('candidate.interests.0', 'Open source')
                ->where('candidate.other_skills.0.name', 'Figma')
                ->where('candidate.diplomas.0.title', 'Licence informatique')
                ->where('candidate.trainings.0.title', 'Bootcamp')
                ->where('candidate.skills.0.id', $skill->id));
    }

    public function test_offer_application_can_reuse_the_profile_cv(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = UploadedFile::fake()
            ->create('cv.pdf', 100, 'application/pdf')
            ->store('cvs', 'local');

        $user->candidate()->create([
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'cv_path' => $path,
            'education_level' => EducationLevel::Licence,
            'years_experience' => 2,
            'projects_count' => 1,
            'availability' => Availability::Immediate,
        ]);

        $offer = Offer::query()->create([
            'name' => 'Dev Laravel',
            'slug' => 'dev-laravel',
            'is_open' => true,
            'work_mode' => 'remote',
        ]);

        $this->actingAs($user)
            ->post(route('offers.apply.store', $offer), [
                'country' => 'Sénégal',
                'city' => 'Dakar',
                'education_level' => EducationLevel::Licence->value,
                'years_experience' => 2,
                'projects_count' => 1,
                'availability' => Availability::Immediate->value,
                'motivation_text' => str_repeat('Je suis motivé pour cette offre. ', 5),
                'use_profile_cv' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'candidate_id' => $user->id,
            'offer_id' => $offer->id,
        ]);

        $application = $user->candidate->applications()->first();
        $this->assertNotNull($application?->cv_path);
        $this->assertNotSame($path, $application->cv_path);
        Storage::disk('local')->assertExists($application->cv_path);
    }
}
