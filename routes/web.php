<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShoeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Manajemen Rekapitulasi Sepatu SR
|--------------------------------------------------------------------------
| 1. Guest Authentication & Password Reset Routes
| 2. Logout Route (GET & POST)
| 3. Protected Routes: Sepatu SR Dashboard, Import, Export, Audit Logs, Profile
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
    // Primary Landing: Manajemen Rekapitulasi Sepatu SR
    Route::get('/', [ShoeController::class, 'index'])->name('home');

    // Sepatu SR - Import & Data Recap Module
    Route::prefix('shoes')->name('shoes.')->group(function () {
        Route::get('/', [ShoeController::class, 'index'])->name('index');
        Route::post('/import', [ShoeController::class, 'import'])->name('import');
        Route::get('/export/excel', [ShoeController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ShoeController::class, 'exportPdf'])->name('export.pdf');
        Route::delete('/batches/{batch}', [ShoeController::class, 'destroyBatch'])->name('batch.destroy');
        Route::delete('/schools/{school}', [ShoeController::class, 'destroySchool'])->name('school.destroy');
    });

    // Profile & Account Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

    // System Activity & Audit Trail
    Route::get('/audit-logs', [ActivityLogController::class, 'index'])->name('audit.index');

    // In-App & Real-Time Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('readAll');
    });
});
