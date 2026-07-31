<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Routes accessibles meme quand un changement de mot de passe est impose
     * (la page de changement elle-meme, et la deconnexion).
     */
    private const ALLOWED_ROUTES = [
        'password.edit',
        'password.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            return redirect()->route('password.edit')
                ->with('error', 'Pour des raisons de securite, vous devez changer votre mot de passe avant de continuer.');
        }

        return $next($request);
    }
}
