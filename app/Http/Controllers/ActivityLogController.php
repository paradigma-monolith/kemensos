<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of system activity audit logs and account login histories.
     */
    public function index(Request $request): Response
    {
        $activities = Activity::with('causer')
            ->latest()
            ->paginate(15);

        $loginHistories = LoginHistory::with('user')
            ->latest('login_at')
            ->paginate(15);

        return Inertia::render('AuditLog/Index', [
            'activities' => $activities,
            'login_histories' => $loginHistories,
            'current_ip' => $request->ip() ?? '127.0.0.1',
        ]);
    }
}
