<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ChecklistController extends Controller
{
    /**
     * Ferme définitivement la checklist d'activation pour cet admin.
     */
    public function dismiss(): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user->isAdmin(), 403);

        if ($user->checklist_dismissed_at === null) {
            $user->update(['checklist_dismissed_at' => now()]);
        }

        return back();
    }
}
