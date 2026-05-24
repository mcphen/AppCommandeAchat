<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppNotificationSettingsController extends Controller
{
    public function index(): Response
    {
        $users = User::with('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'whatsapp_notifications', 'role_id']);

        return Inertia::render('Admin/WhatsAppNotificationSettings', [
            'users' => $users,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'users'   => ['required', 'array'],
            'users.*' => ['boolean'],
        ]);

        foreach ($request->users as $userId => $enabled) {
            User::where('id', $userId)->update(['whatsapp_notifications' => (bool) $enabled]);
        }

        return back()->with('success', 'Paramètres de notification mis à jour.');
    }
}
