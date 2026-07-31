<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::with(['actor', 'targetUser'])->latest();

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('actor', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('targetUser', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        return Inertia::render('Admin/SecurityLogs/Index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'event'     => $request->string('event')->toString(),
                'search'    => $request->string('search')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to'   => $request->string('date_to')->toString(),
            ],
        ]);
    }
}
