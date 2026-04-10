<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Polling léger — retourne le compteur + les 15 dernières notifs */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? 'unknown',
                'title'      => $n->data['title'] ?? '',
                'body'       => $n->data['body'] ?? '',
                'url'        => $n->data['url'] ?? null,
                'color'      => $n->data['color'] ?? 'blue',
                'read'       => ! is_null($n->read_at),
                'created_at' => $n->created_at->toISOString(),
            ]);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /** Marquer une notification comme lue et rediriger vers l'URL associée */
    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');

        return redirect($url);
    }

    /** Marquer toutes les notifications comme lues */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}
