<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    private function companyProps(): array
    {
        try {
            if (! Schema::hasTable('app_settings')) {
                return [];
            }

            $settings = AppSetting::allAsArray();
            $logoPath = $settings['company_logo'] ?? null;

            return [
                'name'    => $settings['company_name'] ?? config('app.name'),
                'address' => $settings['company_address'] ?? null,
                'phone'   => $settings['company_phone'] ?? null,
                'email'   => $settings['company_email'] ?? null,
                'website' => $settings['company_website'] ?? null,
                'nif'     => $settings['company_nif'] ?? null,
                'rccm'    => $settings['company_rccm'] ?? null,
                'logoUrl' => $logoPath ? '/storage/' . ltrim($logoPath, '/') : null,
            ];
        } catch (\Exception) {
            return [];
        }
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name'  => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth'  => [
                'user' => $user ? array_merge($user->toArray(), [
                    'role'              => $user->role,
                    'validation_levels' => $user->validationLevels()->with('circuit')->get(),
                ]) : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'unread_notifications_count' => $user ? $user->unreadNotifications()->count() : 0,
            'show_onboarding'            => $user ? $user->needsOnboarding() : false,
            'company'                    => $this->companyProps(),
        ]);
    }
}
