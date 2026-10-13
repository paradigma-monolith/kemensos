<?php

use App\Http\Controllers\Api\ActivityLogApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\StatsApiController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return response()->json([
        'user' => $request->user(),
        'roles' => $request->user()->getRoleNames(),
        'permissions' => $request->user()->getAllPermissions()->pluck('name'),
    ]);
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Public telemetry stats
    Route::get('/stats', [StatsApiController::class, 'index']);

    // Products API
    Route::apiResource('products', ProductApiController::class);

    // Exports API
    Route::get('/exports/products/excel', [ExportController::class, 'exportExcel']);
    Route::get('/exports/products/csv', [ExportController::class, 'exportCsv']);
    Route::get('/exports/products/pdf', [ExportController::class, 'exportPdf']);

    // Protected API Routes
    Route::middleware('auth:sanctum')->group(function () {
        // User Profile & Avatar
        Route::get('/profile', [ProfileApiController::class, 'show']);
        Route::patch('/profile', [ProfileApiController::class, 'update']);
        Route::put('/profile/password', [ProfileApiController::class, 'updatePassword']);
        Route::post('/profile/avatar', [ProfileApiController::class, 'updateAvatar']);
        Route::get('/profile/login-histories', [ProfileApiController::class, 'loginHistories']);

        // Audit Trail Logs
        Route::get('/audit-logs', [ActivityLogApiController::class, 'index']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/demo', [NotificationController::class, 'sendDemo']);
    });
});
