<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $status = 'Consultez votre e-mail pour choisir votre mot de passe.';

        return $request->wantsJson()
            ? new JsonResponse(['message' => $status], 201)
            : redirect()->route('login')->with('status', $status);
    }
}
