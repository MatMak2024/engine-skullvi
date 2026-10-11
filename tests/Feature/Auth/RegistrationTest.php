<?php

namespace Tests\Feature\Auth;

use App\Enums\Role as RoleName;
use App\Mail\SetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register_without_a_password()
    {
        Mail::fake();

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'name' => 'User',
            'email' => 'test@example.com',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Consultez votre e-mail pour choisir votre mot de passe.');

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->name);
        $this->assertTrue($user->hasRole(RoleName::User));
        $this->assertNull($user->password);
        $this->assertNull($user->email_verified_at);

        Mail::assertSent(SetPasswordMail::class, function (SetPasswordMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && str_contains($mail->url, '/definir-mot-de-passe/'.$user->id);
        });
    }

    public function test_user_sets_a_password_from_the_email_link_then_reaches_login()
    {
        $user = User::factory()->create([
            'password' => null,
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute('password.set', now()->addHour(), $user);

        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('auth/set-password')
            ->where('email', $user->email),
        );

        $response = $this->post($url, [
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Mot de passe enregistré. Vous pouvez vous connecter.');
        $this->assertGuest();

        $user->refresh();
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->email_verified_at);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_set_password_link_rejects_an_invalid_signature()
    {
        $user = User::factory()->create([
            'password' => null,
        ]);

        $this->get(route('password.set', $user))->assertForbidden();
    }
}
