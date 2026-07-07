<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSageApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.sage.api_token');
        $provided = $request->header('X-API-Key');

        if (! $expected || ! $provided || ! hash_equals($expected, $provided)) {
            abort(401, 'Token API invalide ou manquant.');
        }

        return $next($request);
    }
}
