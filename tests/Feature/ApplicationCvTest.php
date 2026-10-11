<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class ApplicationCvTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_and_a_recruiter_can_download_the_cv(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('cvs/demo.pdf', 'cv-content');

        $program = TalentWorld::program();
        $application = TalentWorld::application($program, [
            'cv_path' => 'cvs/demo.pdf',
        ]);
        $recruiter = TalentWorld::recruiter(['application:view']);

        $this->actingAs($application->candidate->user)
            ->get(route('applications.cv', $application))
            ->assertOk()
            ->assertDownload('cv.pdf');

        $this->actingAs($recruiter)
            ->get(route('applications.cv', $application))
            ->assertOk()
            ->assertDownload('cv.pdf');
    }

    public function test_another_user_cannot_download_the_cv(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('cvs/demo.pdf', 'cv-content');

        $application = TalentWorld::application(TalentWorld::program(), [
            'cv_path' => 'cvs/demo.pdf',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('applications.cv', $application))
            ->assertForbidden();
    }

    public function test_a_missing_or_unsafe_cv_returns_not_found(): void
    {
        Storage::fake('local');

        $application = TalentWorld::application(TalentWorld::program());
        $owner = $application->candidate->user;

        $this->actingAs($owner)
            ->get(route('applications.cv', $application))
            ->assertNotFound();

        $application->update(['cv_path' => '../.env']);

        $this->actingAs($owner)
            ->get(route('applications.cv', $application))
            ->assertNotFound();
    }
}
