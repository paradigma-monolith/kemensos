<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShoeController;
use App\Http\Controllers\ShoeDashboardController;
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
    // Primary Landing: Dashboard Interaktif Analitik & Perbandingan
    Route::get('/', [ShoeDashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [ShoeDashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/shoes/dashboard-data', [ShoeDashboardController::class, 'apiData'])->name('shoes.dashboard.api');

    // Sepatu SR - Import & Data Recap Module
    Route::prefix('shoes')->name('shoes.')->group(function () {
        Route::get('/', [ShoeController::class, 'index'])->name('index');
        Route::post('/import', [ShoeController::class, 'import'])->name('import');
        Route::get('/export/excel', [ShoeController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ShoeController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/batches/{batch}/download', [ShoeController::class, 'downloadBatch'])->name('batch.download');
        Route::delete('/batches/{batch}', [ShoeController::class, 'destroyBatch'])->name('batch.destroy');
        Route::post('/sync-storage', [ShoeController::class, 'syncStorage'])->name('sync.storage');
        Route::patch('/schools/{school}', [ShoeController::class, 'updateSchool'])->name('school.update');
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

// Utility Route: Clear application view & config cache on shared hosting / cPanel
Route::get('/clear-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');

    // Invalidate OPcache if enabled on PHP-FPM / cPanel
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    // Force delete all cached blade templates
    $viewsPath = storage_path('framework/views');
    $deletedViews = 0;
    if (is_dir($viewsPath)) {
        foreach (glob($viewsPath . '/*.php') as $v) {
            @unlink($v);
            $deletedViews++;
        }
    }

    // Ensure legacy / alternate css names exist as physical copies
    $assetsDir = public_path('build/assets');
    $createdCopies = [];
    if (is_dir($assetsDir)) {
        $cssFiles = glob($assetsDir . '/*.css');
        if (!empty($cssFiles)) {
            $masterCss = $cssFiles[0];
            foreach (['app-CePkOaw4.css', 'app-CePkGaw4.css', 'app-CI27iwGq.css'] as $target) {
                $targetPath = $assetsDir . '/' . $target;
                if (!file_exists($targetPath)) {
                    @copy($masterCss, $targetPath);
                    $createdCopies[] = $target;
                }
            }
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Cache view, config, route, OPcache, dan aset CSS berhasil disinkronkan! Silakan refresh halaman.',
        'deleted_views_count' => $deletedViews,
        'created_css_copies' => $createdCopies,
    ]);
});

// Fallback Route: Serve CSS dynamically if an old cached asset hash is requested
Route::get('/build/assets/{filename}', function ($filename) {
    if (str_ends_with($filename, '.css')) {
        $assetsPath = public_path('build/assets');
        $files = glob($assetsPath . '/*.css');
        if (!empty($files)) {
            return response(file_get_contents($files[0]), 200, [
                'Content-Type' => 'text/css; charset=UTF-8',
                'Cache-Control' => 'no-cache, private',
            ]);
        }
    }
    abort(404);
})->where('filename', '.*');

