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

    // Remove Vite hot reload file if present in production
    $removedHot = false;
    foreach ([public_path('hot'), base_path('public/hot'), base_path('public_html/hot')] as $h) {
        if (file_exists($h)) {
            @unlink($h);
            $removedHot = true;
        }
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

    // Auto-sync files from base_path('public/build') to public_path('build') if they differ
    $baseBuild = base_path('public/build');
    $pubBuild = public_path('build');
    $syncedBuild = false;

    if ($baseBuild !== $pubBuild && is_dir($baseBuild)) {
        if (!is_dir($pubBuild)) {
            @mkdir($pubBuild, 0755, true);
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseBuild, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $item) {
            $sub = substr($item->getPathname(), strlen($baseBuild));
            $dest = $pubBuild . $sub;
            if ($item->isDir()) {
                if (!is_dir($dest)) @mkdir($dest, 0755, true);
            } else {
                @copy($item->getPathname(), $dest);
            }
        }
        $syncedBuild = true;
    }

    // Auto-extract public.zip if build/assets is missing or empty
    $assetsDir = public_path('build/assets');
    $unzipped = false;
    if (!is_dir($assetsDir) || count(glob($assetsDir . '/*')) === 0) {
        $zipPath = base_path('public.zip');
        if (file_exists($zipPath) && class_exists('ZipArchive')) {
            $zip = new \ZipArchive;
            if ($zip->open($zipPath) === true) {
                $zip->extractTo(public_path());
                $zip->close();
                $unzipped = true;
            }
        }
    }

    // Ensure legacy / alternate css names exist as physical copies
    $assetsDir = public_path('build/assets');
    $createdCopies = [];
    if (is_dir($assetsDir)) {
        $cssFiles = glob($assetsDir . '/*.css');
        if (!empty($cssFiles)) {
            $masterCss = $cssFiles[0];
            foreach (['app-CePkOaw4.css', 'app-CePkGaw4.css', 'app-CI27iwGq.css', 'app-xGliZk1d.css', 'app-DHr3JjlA.css', 'app-Bl-vxXia.css'] as $target) {
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
        'message' => 'Cache view, config, route, OPcache, dan aset JS/CSS berhasil disinkronkan!',
        'deleted_views_count' => $deletedViews,
        'removed_hot' => $removedHot,
        'created_css_copies' => $createdCopies,
        'synced_build' => $syncedBuild,
        'unzipped' => $unzipped,
        'base_path' => base_path(),
        'public_path' => public_path(),
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
        'assets_count' => is_dir($assetsDir) ? count(glob($assetsDir . '/*')) : 0,
        'assets_sample' => is_dir($assetsDir) ? array_map('basename', array_slice(glob($assetsDir . '/*'), 0, 10)) : [],
    ]);
});

// Robust Fallback Route: Serve CSS and JS dynamically if an asset hash is requested
Route::match(['GET', 'HEAD'], '/build/assets/{filename}', function ($filename) {
    $searchCandidates = [
        public_path('build/assets/' . $filename),
        base_path('public/build/assets/' . $filename),
        base_path('public_html/build/assets/' . $filename),
        (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/build/assets/' . $filename : ''),
    ];

    foreach ($searchCandidates as $candidate) {
        if (!empty($candidate) && file_exists($candidate)) {
            $mime = str_ends_with($filename, '.css')
                ? 'text/css; charset=UTF-8'
                : (str_ends_with($filename, '.js') ? 'application/javascript; charset=UTF-8' : 'application/octet-stream');
            return response(file_get_contents($candidate), 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }
    }

    // Dynamic fallback for JS modules
    if (str_ends_with($filename, '.js')) {
        $prefix = explode('-', $filename)[0]; // e.g. 'inertia', 'echo', 'spa'
        $dirs = [
            public_path('build/assets'),
            base_path('public/build/assets'),
            isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/build/assets' : null,
        ];
        foreach ($dirs as $d) {
            if (!empty($d) && is_dir($d)) {
                $matches = glob($d . '/' . $prefix . '-*.js');
                if (!empty($matches)) {
                    return response(file_get_contents($matches[0]), 200, [
                        'Content-Type' => 'application/javascript; charset=UTF-8',
                        'Cache-Control' => 'no-cache, private',
                    ]);
                }
            }
        }
    }

    // Dynamic fallback for CSS
    if (str_ends_with($filename, '.css')) {
        $dirs = [
            public_path('build/assets'),
            base_path('public/build/assets'),
            isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/build/assets' : null,
        ];
        foreach ($dirs as $d) {
            if (!empty($d) && is_dir($d)) {
                $matches = glob($d . '/*.css');
                if (!empty($matches)) {
                    return response(file_get_contents($matches[0]), 200, [
                        'Content-Type' => 'text/css; charset=UTF-8',
                        'Cache-Control' => 'no-cache, private',
                    ]);
                }
            }
        }
    }

    return response("Asset not found: " . htmlspecialchars($filename), 404, ['Content-Type' => 'text/plain']);
})->where('filename', '.*');

