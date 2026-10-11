<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance') ?? 'light';

        // "system" suivait souvent le dark OS et rendait le texte illisible
        // sur les fonds clairs de l'interface.
        if ($appearance === 'system') {
            $appearance = 'light';
        }

        View::share('appearance', $appearance);

        return $next($request);
    }
}
