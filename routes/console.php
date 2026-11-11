<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('shoes:sync-storage', function () {
    $this->info('Memulai sinkronisasi berkas Excel dari storage ke database...');
    $importService = app(\App\Services\ShoeExcelImportService::class);
    $batches = \App\Models\ShoeImportBatch::with('school')->get();
    $processed = 0;
    $totalShoes = 0;

    if ($batches->isNotEmpty()) {
        foreach ($batches as $batch) {
            $filePath = $batch->file_path;
            if (!file_exists($filePath)) {
                $candidates = [
                    storage_path('app/' . $filePath),
                    storage_path('app/private/' . $filePath),
                    storage_path('app/excel_imports/' . basename($filePath)),
                ];
                foreach ($candidates as $c) {
                    if (file_exists($c)) {
                        $filePath = $c;
                        break;
                    }
                }
            }

            if (!file_exists($filePath)) {
                $this->warn("Berkas {$batch->file_name} tidak ditemukan di storage.");
                continue;
            }

            try {
                $pic = $batch->school?->pic_nama;
                $summary = $importService->import($filePath, $batch->file_name, $batch->imported_by, $pic);
                $totalShoes += $summary['total_sepatu'];
                $processed++;
                $this->info("[OK] Sinkronisasi: {$batch->file_name} -> {$summary['school_name']} ({$summary['total_sepatu']} pasang)");
            } catch (\Throwable $e) {
                $this->error("[FAIL] Gagal {$batch->file_name}: " . $e->getMessage());
            }
        }
    } else {
        $files = \Illuminate\Support\Facades\Storage::disk('local')->files('excel_imports');
        foreach ($files as $rel) {
            $full = \Illuminate\Support\Facades\Storage::disk('local')->path($rel);
            $orig = basename($rel);
            try {
                $summary = $importService->import($full, $orig);
                $totalShoes += $summary['total_sepatu'];
                $processed++;
                $this->info("[OK] Sinkronisasi: {$orig} -> {$summary['school_name']} ({$summary['total_sepatu']} pasang)");
            } catch (\Throwable $e) {
                $this->error("[FAIL] Gagal {$orig}: " . $e->getMessage());
            }
        }
    }

    $this->info("Sinkronisasi selesai! Total {$processed} berkas diproses ({$totalShoes} pasang sepatu).");
})->purpose('Sinkronkan seluruh berkas Excel di storage ke database secara otomatis');

