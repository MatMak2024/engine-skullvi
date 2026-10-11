<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = collect($roles)
            ->flatMap(fn (string $role) => explode(',', $role))
            ->contains(fn (string $role) => $request->user()?->hasRole($role));

        abort_unless($allowed, 403);

        return $next($request);
    }
}
