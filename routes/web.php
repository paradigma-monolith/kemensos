<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Inertia\DashboardController;
use App\Http\Controllers\Inertia\ProductInertiaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Hybrid Architecture Web Routes
|--------------------------------------------------------------------------
| Enterprise Master Boilerplate Routes:
| 1. Guest Authentication & Password Reset Routes
| 2. Logout Route (GET & POST)
| 3. Protected Hub, Inertia, Profile, Audit Log, Export, and SPA Routes
*/

// --- Guest Authentication Routes ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// --- Logout Route (GET & POST) ---
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// --- Protected Enterprise Routes (Requires Authentication) ---
Route::middleware('auth')->group(function () {
    // Central Hybrid Hub (Protected post-login landing)
    Route::get('/', [DashboardController::class, 'index'])->name('home');

    // Inertia.js Bridge Routes
    Route::prefix('inertia')->name('inertia.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/map-demo', [DashboardController::class, 'mapDemo'])->name('map.demo');
        Route::get('/products', [ProductInertiaController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductInertiaController::class, 'store'])->name('products.store');
        Route::delete('/products/{product}', [ProductInertiaController::class, 'destroy'])->name('products.destroy');
    });

    Route::get('/map-demo', [DashboardController::class, 'mapDemo'])->name('map');

    // Profile & Account Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

    // System Activity & Audit Trail
    Route::get('/audit-logs', [ActivityLogController::class, 'index'])->name('audit.index');

    // Data Exports (Excel, CSV, PDF)
    Route::prefix('exports/products')->name('exports.products.')->group(function () {
        Route::get('/excel', [ExportController::class, 'exportExcel'])->name('excel');
        Route::get('/csv', [ExportController::class, 'exportCsv'])->name('csv');
        Route::get('/pdf', [ExportController::class, 'exportPdf'])->name('pdf');
    });

    // In-App & Real-Time Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('readAll');
        Route::post('/demo', [NotificationController::class, 'sendDemo'])->name('demo');
    });

    // Real-Time Live Chat (Reverb WebSocket Powered)
    Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/messages', [\App\Http\Controllers\ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/demo-reply', [\App\Http\Controllers\ChatController::class, 'demoReply'])->name('chat.demoReply');

    // Traditional SPA Host Route
    Route::get('/spa/{any?}', function () {
        $user = auth()->user();

        return view('spa', [
            'authUser' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ] : null,
        ]);
    })->where('any', '.*')->name('spa');
});
