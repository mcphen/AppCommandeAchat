<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class OnboardingController extends Controller
{
    public function complete(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->needsOnboarding()) {
            $user->update(['onboarding_completed_at' => now()]);
        }

        return back();
    }
}
