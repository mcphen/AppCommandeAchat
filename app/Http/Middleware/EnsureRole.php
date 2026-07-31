<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role || ! in_array($user->role->slug, $roles)) {
            if ($user) {
                AuditLog::record(
                    'access_denied',
                    "Accès refusé à {$request->path()} (rôle requis : " . implode(', ', $roles) . ').',
                    target: $user,
                );
            }

            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
