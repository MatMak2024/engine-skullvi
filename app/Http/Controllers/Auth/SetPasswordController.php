<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SetPasswordController extends Controller
{
    public function create(Request $request, User $user): Response|RedirectResponse
    {
        if ($user->password !== null) {
            return redirect()
                ->route('login')
                ->with('status', 'Ce compte a déjà un mot de passe. Connectez-vous.');
        }

        return Inertia::render('auth/set-password', [
            'email' => $user->email,
            'submitUrl' => $request->getRequestUri(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function store(SetPasswordRequest $request, User $user): RedirectResponse
    {
        if ($user->password !== null) {
            return redirect()
                ->route('login')
                ->with('status', 'Ce compte a déjà un mot de passe. Connectez-vous.');
        }

        $user->forceFill([
            'password' => $request->validated('password'),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return redirect()
            ->route('login')
            ->with('status', 'Mot de passe enregistré. Vous pouvez vous connecter.');
    }
}
