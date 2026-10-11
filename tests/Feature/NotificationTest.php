<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\Role as RoleName;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\ApplicationSubmittedNotification;
use App\Notifications\NewApplicationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_shared_on_public_pages_for_authenticated_users(): void
    {
        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs-notif',
            'is_open' => true,
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $program), $this->payload())
            ->assertRedirect();

        $this->actingAs($user->fresh())
            ->get(route('programs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('notifications.unread')
                ->where('notifications.unread', 1)
                ->has('notifications.items', 1));
    }

    public function test_staff_and_candidate_are_notified_on_application(): void
    {
        Notification::fake();

        $recruiter = TalentWorld::recruiter();
        $superAdmin = User::factory()->create();
        $superAdminRole = Role::query()->firstOrCreate(
            ['slug' => RoleName::SuperAdmin->value],
            ['name' => 'Super administrateur'],
        );
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs-staff',
            'is_open' => true,
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $program), $this->payload())
            ->assertRedirect();

        Notification::assertSentTo($user, ApplicationSubmittedNotification::class);
        Notification::assertSentTo($recruiter, NewApplicationNotification::class);
        Notification::assertSentTo($superAdmin, NewApplicationNotification::class);
    }

    public function test_a_candidate_is_notified_when_status_changes(): void
    {
        Notification::fake();

        $recruiter = TalentWorld::recruiter(['application:update-status']);
        $application = TalentWorld::application(TalentWorld::program());
        $candidateUser = $application->candidate->user;

        $this->actingAs($recruiter)
            ->patch(route('recruiter.applications.update-status', $application), [
                'status' => ApplicationStatus::Accepted->value,
            ])
            ->assertRedirect();

        Notification::assertSentTo($candidateUser, ApplicationStatusNotification::class);
    }

    public function test_any_user_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs-read',
            'is_open' => true,
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $program), $this->payload())
            ->assertRedirect();

        $notification = $user->fresh()->notifications()->first();
        $this->assertNotNull($notification);

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'education_level' => EducationLevel::Licence->value,
            'years_experience' => 1,
            'projects_count' => 1,
            'availability' => Availability::Immediate->value,
            'motivation_text' => 'Je souhaite rejoindre le programme.',
        ];
    }
}
