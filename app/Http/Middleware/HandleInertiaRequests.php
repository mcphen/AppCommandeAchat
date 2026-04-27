<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();

        return array_merge(parent::share($request), [
            'name'  => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth'  => [
                'user' => $user ? array_merge($user->toArray(), [
                    'role'              => $user->role,
                    'niveau_validation' => $user->niveauValidation,
                    'entreprise'        => $user->entreprise,
                    'is_membre_comite'  => \App\Models\MembreComiteArbitrage::where('user_id', $user->id)->where('is_active', true)->exists(),
                ]) : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'unread_notifications_count' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}
