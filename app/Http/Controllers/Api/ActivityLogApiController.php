<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

class ActivityLogApiController extends Controller
{
    /**
     * Return paginated audit trail logs for API clients.
     */
    public function index(): JsonResponse
    {
        $activities = Activity::with('causer')
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $activities,
        ]);
    }
}
