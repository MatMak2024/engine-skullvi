<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\Role as RoleName;
use App\Enums\SkillLevel;
use App\Models\Application;
use App\Models\Offer;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class OfferApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_unused_tables_are_gone_and_candidates_remain(): void
    {
        $this->assertFalse(Schema::hasTable('candidats'));
        $this->assertFalse(Schema::hasTable('teams'));
        $this->assertFalse(Schema::hasTable('evolution_scores'));
        $this->assertTrue(Schema::hasTable('candidates'));
        $this->assertTrue(Schema::hasTable('offers'));
        $this->assertTrue(Schema::hasTable('courses'));
    }

    public function test_several_candidates_are_ranked_on_the_same_offer(): void
    {
        $recruiter = TalentWorld::recruiter();
        $skill = Skill::query()->create(['name' => 'Laravel']);

        $this->actingAs($recruiter)
            ->post(route('admin.offers.store'), [
                'name' => 'Développeur Laravel',
                'description' => 'laravel react',
                'is_open' => '1',
                'work_mode' => 'remote',
                'required_skills' => [$skill->id],
            ])
            ->assertRedirect();

        $offer = Offer::query()->where('name', 'Développeur Laravel')->first();
        $this->assertNotNull($offer);

        $strong = User::factory()->create();
        $light = User::factory()->create();

        $this->actingAs($strong)
            ->post(route('offers.apply.store', $offer), $this->payload($skill->id, SkillLevel::Advanced, 400))
            ->assertRedirect();

        $this->actingAs($light)
            ->post(route('offers.apply.store', $offer), $this->payload($skill->id, SkillLevel::Beginner, 80))
            ->assertRedirect();

        foreach (Application::query()->where('offer_id', $offer->id)->get() as $application) {
            $this->actingAs($recruiter)
                ->patch(route('admin.applications.examination', $application))
                ->assertRedirect();
        }

        $ranked = Application::query()
            ->where('offer_id', $offer->id)
            ->with('currentEvaluation')
            ->get()
            ->sortByDesc(fn (Application $application) => $application->currentEvaluation->total_score)
            ->values();

        $this->assertCount(2, $ranked);
        $this->assertTrue($ranked[0]->currentEvaluation->total_score > $ranked[1]->currentEvaluation->total_score);
        $this->assertTrue($strong->fresh()->hasRole(RoleName::Candidate));

        $superAdmin = User::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['slug' => RoleName::SuperAdmin],
            ['name' => 'Super administrateur'],
        );
        $superAdmin->roles()->attach($role);

        $this->actingAs($superAdmin)
            ->get(route('admin.offers.index'))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->post(route('admin.offers.store'), [
                'name' => 'Interdite',
                'is_open' => '1',
                'work_mode' => 'remote',
            ])
            ->assertForbidden();
    }

    public function test_a_recruiter_suspends_an_offer_and_can_delete_it_without_applications(): void
    {
        $recruiter = TalentWorld::recruiter();
        $offer = Offer::query()->create([
            'name' => 'Offre libre',
            'slug' => 'offre-libre',
            'is_open' => true,
        ]);
        $user = User::factory()->create();

        $this->actingAs($recruiter)
            ->patch(route('admin.offers.suspension', $offer))
            ->assertRedirect();

        $this->assertFalse($offer->fresh()->is_open);

        $this->actingAs($user)
            ->get(route('offers.apply', $offer))
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->delete(route('admin.offers.destroy', $offer))
            ->assertRedirect(route('admin.offers.index'));

        $this->assertModelMissing($offer);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $skillId, SkillLevel $level, int $length): array
    {
        return [
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'education_level' => EducationLevel::Licence->value,
            'years_experience' => 3,
            'projects_count' => 2,
            'availability' => Availability::Immediate->value,
            'motivation_text' => TalentWorld::text('Je pratique laravel au quotidien. ', $length),
            'cv' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
            'skills' => [
                ['id' => $skillId, 'level' => $level->value],
            ],
        ];
    }
}
