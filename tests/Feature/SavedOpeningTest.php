<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Models\Offer;
use App\Models\Program;
use App\Models\SavedOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SavedOpeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_save_and_unsave_a_program(): void
    {
        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs',
            'is_open' => true,
        ]);

        $this->actingAs($user)
            ->post(route('programs.save', $program))
            ->assertRedirect();

        $this->assertDatabaseHas('saved_openings', [
            'user_id' => $user->id,
            'kind' => 'program',
            'target_id' => $program->id,
        ]);

        $this->actingAs($user)
            ->get(route('programs.show', $program))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('programs/show')
                ->where('saved', true));

        $this->actingAs($user)
            ->delete(route('programs.unsave', $program))
            ->assertRedirect();

        $this->assertDatabaseMissing('saved_openings', [
            'user_id' => $user->id,
            'target_id' => $program->id,
        ]);
    }

    public function test_saved_openings_appear_on_the_dashboard(): void
    {
        $user = User::factory()->create();
        $offer = Offer::query()->create([
            'name' => 'Dev Laravel',
            'slug' => 'dev-laravel',
            'is_open' => true,
            'work_mode' => 'remote',
        ]);

        $this->actingAs($user)
            ->post(route('offers.save', $offer))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('savedOpenings', 1)
                ->where('savedOpenings.0.name', 'Dev Laravel'));
    }

    public function test_applying_removes_the_saved_opening(): void
    {
        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs',
            'is_open' => true,
        ]);

        SavedOpening::query()->create([
            'user_id' => $user->id,
            'kind' => 'program',
            'target_id' => $program->id,
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $program), [
                'country' => 'Sénégal',
                'city' => 'Dakar',
                'education_level' => EducationLevel::Licence->value,
                'years_experience' => 2,
                'projects_count' => 1,
                'availability' => Availability::Immediate->value,
                'motivation_text' => 'Je veux rejoindre le programme.',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('saved_openings', [
            'user_id' => $user->id,
            'target_id' => $program->id,
        ]);
    }
}
