<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsurePasswordIsChanged::class,
        ]);

        $middleware->alias([
            'role'      => \App\Http\Middleware\EnsureRole::class,
            'sage.token' => \App\Http\Middleware\EnsureSageApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Un utilisateur connecte qui tombe sur une page/action dont il n'a pas le
        // role (middleware 'role', policies, abort(403)...) est renvoye vers son
        // dashboard avec une notification, plutot que la page 403 brute de Laravel
        // (qui n'a pas de sens dans une appli Inertia avec sidebar/navigation).
        $exceptions->render(function (\Throwable $e, Request $request) {
            $status = match (true) {
                $e instanceof AuthorizationException => 403,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => null,
            };

            if ($status === 403
                && $request->user()
                && ! $request->is('api/*')
                && ! $request->expectsJson()
            ) {
                return redirect()->route('dashboard')
                    ->with('error', $e->getMessage() ?: "Vous n'avez pas accès à cette page.");
            }
        });
    })->create();
