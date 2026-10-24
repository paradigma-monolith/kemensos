<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\ShoeImportBatch;
use App\Models\ShoeRecipient;
use App\Services\ShoeExcelImportService;
use App\Services\ShoeExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShoeController extends Controller
{
    public function __construct(
        protected ShoeExcelImportService $importService,
        protected ShoeExportService $exportService
    ) {}

    /**
     * Display the shoe management and recap dashboard.
     */
    public function index(Request $request)
    {
        $schoolId = $request->query('school_id');
        $kategori = $request->query('kategori');
        $gender = $request->query('gender');
        $pic = $request->query('pic');
        $search = $request->query('search');
        $onlyAnomaly = $request->boolean('anomaly');

        // Master PIC list combining default constant and any existing database values
        $existingPics = School::whereNotNull('pic_nama')
            ->where('pic_nama', '!=', '')
            ->distinct()
            ->pluck('pic_nama')
            ->map(fn($p) => School::normalizePicName($p))
            ->filter()
            ->toArray();
        $allPics = array_values(array_unique(array_merge(School::DEFAULT_PICS, $existingPics)));
        sort($allPics);

        // 1. Overall & Filtered Stats
        $statsQuery = ShoeRecipient::query();
        if ($schoolId) {
            $statsQuery->where('school_id', $schoolId);
        }
        if ($pic) {
            $normPic = School::normalizePicName($pic);
            $statsQuery->whereHas('school', fn($q) => $q->where('pic_nama', $normPic)->orWhere('pic_nama', 'PIC ' . $normPic));
        }

        $totalRecipients = (clone $statsQuery)->count();
        $totalSiswa = (clone $statsQuery)->where('kategori', 'siswa')->count();
        $totalGuru = (clone $statsQuery)->where('kategori', 'guru')->count();
        $totalTendik = (clone $statsQuery)->where('kategori', 'tendik')->count();
        $totalPria = (clone $statsQuery)->where('jenis_kelamin', 'L')->count();
        $totalWanita = (clone $statsQuery)->where('jenis_kelamin', 'P')->count();
        $totalAnomalies = (clone $statsQuery)->anomaly()->count();
        $extremeAnomalies = (clone $statsQuery)->where('ukuran_sepatu', '>', 100)->count();

        $totalSchools = School::count();
        $totalBatches = ShoeImportBatch::count();

        // 2. Matrix Breakdown by Shoe Size
        $matrixQuery = ShoeRecipient::query();
        if ($schoolId) {
            $matrixQuery->where('school_id', $schoolId);
        }
        if ($pic) {
            $normPic = School::normalizePicName($pic);
            $matrixQuery->whereHas('school', fn($q) => $q->where('pic_nama', $normPic)->orWhere('pic_nama', 'PIC ' . $normPic));
        }

        $sizeMatrix = $matrixQuery->selectRaw('
            ukuran_sepatu,
            COUNT(*) as total,
            SUM(CASE WHEN kategori = "siswa" AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as siswa_l,
            SUM(CASE WHEN kategori = "siswa" AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as siswa_p,
            SUM(CASE WHEN kategori = "guru" AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as guru_l,
            SUM(CASE WHEN kategori = "guru" AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as guru_p,
            SUM(CASE WHEN kategori = "tendik" AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as tendik_l,
            SUM(CASE WHEN kategori = "tendik" AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as tendik_p,
            SUM(CASE WHEN jenis_kelamin = "L" THEN 1 ELSE 0 END) as total_pria,
            SUM(CASE WHEN jenis_kelamin = "P" THEN 1 ELSE 0 END) as total_wanita
        ')
        ->groupBy('ukuran_sepatu')
        ->orderBy('ukuran_sepatu')
        ->get();

        // 3. Paginated Detailed Recipients
        $recipientQuery = ShoeRecipient::with('school:id,nama_sekolah,kota,pic_nama')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($kategori, fn($q) => $q->where('kategori', $kategori))
            ->when($gender, fn($q) => $q->where('jenis_kelamin', $gender))
            ->when($onlyAnomaly, fn($q) => $q->anomaly())
            ->when($pic, function ($q, $pic) {
                $norm = School::normalizePicName($pic);
                $q->whereHas('school', fn($sq) => $sq->where('pic_nama', $norm)->orWhere('pic_nama', 'PIC ' . $norm));
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%")
                        ->orWhere('ukuran_sepatu', 'like', "%{$search}%")
                        ->orWhereHas('school', fn($sq) => $sq->where('nama_sekolah', 'like', "%{$search}%")->orWhere('pic_nama', 'like', "%{$search}%"));
                });
            })
            ->orderBy('school_id')
            ->orderBy('kategori')
            ->orderBy('nomor_urut')
            ->paginate(15)
            ->withQueryString();

        // 4. Schools list
        $schools = School::withCount(['recipients', 'importBatches'])
            ->when($pic, function ($q, $pic) {
                $norm = School::normalizePicName($pic);
                $q->where('pic_nama', $norm)->orWhere('pic_nama', 'PIC ' . $norm);
            })
            ->orderBy('nama_sekolah')
            ->get();

        // 5. Import Batches
        $batches = ShoeImportBatch::with(['school:id,nama_sekolah,pic_nama', 'user:id,name'])
            ->latest()
            ->take(20)
            ->get();

        $tab = $request->input('tab');
        if (!$tab) {
            $tab = ($request->has('page') || $onlyAnomaly || $search) ? 'recipients' : 'matrix';
        }

        return Inertia::render('Shoes/Index', [
            'stats' => [
                'total_sepatu' => $totalRecipients,
                'total_siswa' => $totalSiswa,
                'total_guru' => $totalGuru,
                'total_tendik' => $totalTendik,
                'total_pria' => $totalPria,
                'total_wanita' => $totalWanita,
                'total_schools' => $totalSchools,
                'total_batches' => $totalBatches,
                'total_anomalies' => $totalAnomalies,
                'extreme_anomalies' => $extremeAnomalies,
            ],
            'filters' => [
                'school_id' => $schoolId ? (int)$schoolId : null,
                'kategori' => $kategori,
                'gender' => $gender,
                'pic' => $pic,
                'search' => $search,
                'anomaly' => $onlyAnomaly,
                'tab' => $tab,
            ],
            'sizeMatrix' => $sizeMatrix,
            'recipients' => $recipientQuery,
            'schools' => $schools,
            'batches' => $batches,
            'picList' => $allPics,
        ]);
    }

    /**
     * Handle Excel file upload and import (supports multiple files and PIC selection).
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_files' => 'nullable|array',
            'excel_files.*' => 'file|max:51200',
            'excel_file' => 'nullable|file|max:51200',
            'pic_nama' => 'nullable|string|max:100',
        ], [
            'excel_files.*.max' => 'Ukuran setiap berkas maksimal adalah 50MB.',
            'excel_file.max' => 'Ukuran berkas maksimal adalah 50MB.',
        ]);

        $selectedPic = School::normalizePicName($request->input('pic_nama'));

        // Collect all uploaded files into an array
        $files = [];
        if ($request->hasFile('excel_files')) {
            $uploaded = $request->file('excel_files');
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
        } elseif ($request->hasFile('excel_file')) {
            $files = [$request->file('excel_file')];
        }

        if (empty($files)) {
            return redirect()->route('shoes.index')->with('error', 'Silakan pilih setidaknya satu berkas Excel untuk di-import.');
        }

        // Validate extensions directly to avoid finfo zip mime-type ambiguity on Linux
        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
                return redirect()->route('shoes.index')->with('error', "Berkas '{$file->getClientOriginalName()}' harus berformat Excel (.xlsx atau .xls).");
            }
        }

        $importedSummaries = [];
        $totalImportedShoes = 0;
        $allWarnings = [];
        $failedFiles = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            try {
                // Ensure target directory exists on local disk
                Storage::disk('local')->makeDirectory('excel_imports');

                $storedName = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $storedPath = $file->storeAs('excel_imports', $storedName, 'local');
                $absolutePath = Storage::disk('local')->path($storedPath);

                // Fallback check if file is in another storage location or temp directory
                if (!file_exists($absolutePath)) {
                    if (file_exists(storage_path('app/private/' . $storedPath))) {
                        $absolutePath = storage_path('app/private/' . $storedPath);
                    } elseif (file_exists(storage_path('app/' . $storedPath))) {
                        $absolutePath = storage_path('app/' . $storedPath);
                    } elseif ($file->getRealPath() && file_exists($file->getRealPath())) {
                        $absolutePath = $file->getRealPath();
                    }
                }

                $summary = $this->importService->import($absolutePath, $originalName, auth()->id(), $selectedPic);
                $totalImportedShoes += $summary['total_sepatu'];
                $importedSummaries[] = "{$summary['school_name']} ({$summary['total_sepatu']} pasang)";

                if (!empty($summary['warnings'])) {
                    foreach ($summary['warnings'] as $w) {
                        $allWarnings[] = "[{$originalName}] {$w}";
                    }
                }
            } catch (\Throwable $e) {
                $failedFiles[] = "{$originalName}: " . $e->getMessage();
            }
        }

        // Prepare feedback messages
        if (!empty($importedSummaries) && empty($failedFiles)) {
            $countFiles = count($importedSummaries);
            $message = "Berhasil mengimpor {$countFiles} berkas sekolah ({$totalImportedShoes} total pasang sepatu): " . implode(', ', $importedSummaries) . ".";
            return redirect()->route('shoes.index')->with([
                'success' => $message,
                'import_warnings' => $allWarnings,
            ]);
        } elseif (!empty($importedSummaries) && !empty($failedFiles)) {
            $message = "Berhasil mengimpor " . count($importedSummaries) . " berkas (" . implode(', ', $importedSummaries) . "), namun " . count($failedFiles) . " berkas gagal diproses: " . implode('; ', $failedFiles);
            return redirect()->route('shoes.index')->with([
                'success' => $message,
                'import_warnings' => $allWarnings,
            ]);
        } else {
            return redirect()->route('shoes.index')->with('error', 'Gagal memproses berkas Excel: ' . implode('; ', $failedFiles));
        }
    }

    /**
     * Export rekapitulasi to Excel.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $schoolId = $request->query('school_id');
        $spreadsheet = $this->exportService->generateRecapExcel($schoolId ? (int)$schoolId : null);

        $schoolPrefix = 'Semua_Sekolah';
        if ($schoolId) {
            $school = School::find($schoolId);
            if ($school) {
                $schoolPrefix = str_replace(' ', '_', $school->nama_sekolah);
            }
        }
        $fileName = "Rekap_Sepatu_{$schoolPrefix}_" . date('Ymd_His') . ".xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            @ini_set('memory_limit', '1024M');
            @ini_set('max_execution_time', '300');
            @set_time_limit(300);

            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export rekapitulasi to PDF.
     */
    public function exportPdf(Request $request)
    {
        $schoolId = $request->query('school_id');
        $school = $schoolId ? School::find($schoolId) : null;

        $query = ShoeRecipient::query();
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $matrix = $query->selectRaw('
            ukuran_sepatu,
            COUNT(*) as total,
            SUM(CASE WHEN kategori = "siswa" AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as siswa_l,
            SUM(CASE WHEN kategori = "siswa" AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as siswa_p,
            SUM(CASE WHEN kategori IN ("guru", "tendik") AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as staff_l,
            SUM(CASE WHEN kategori IN ("guru", "tendik") AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as staff_p,
            SUM(CASE WHEN jenis_kelamin = "L" THEN 1 ELSE 0 END) as total_pria,
            SUM(CASE WHEN jenis_kelamin = "P" THEN 1 ELSE 0 END) as total_wanita
        ')
        ->groupBy('ukuran_sepatu')
        ->orderBy('ukuran_sepatu')
        ->get();

        $pdf = Pdf::loadView('pdf.shoe_recap', [
            'school' => $school,
            'matrix' => $matrix,
            'date' => now()->translatedFormat('d F Y'),
        ])->setPaper('a4', 'portrait');

        $fileName = 'Rekap_Sepatu_' . ($school ? str_replace(' ', '_', $school->nama_sekolah) : 'Semua') . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Download the original imported Excel file.
     */
    public function downloadBatch(ShoeImportBatch $batch)
    {
        $filePath = $batch->file_path;

        if ($filePath && file_exists($filePath)) {
            return response()->download($filePath, $batch->file_name);
        }

        if ($filePath && Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->download($filePath, $batch->file_name);
        }

        // Check fallback paths
        $fallbacks = [
            storage_path('app/' . $filePath),
            storage_path('app/private/' . $filePath),
            storage_path('app/excel_imports/' . basename($filePath)),
        ];

        foreach ($fallbacks as $fb) {
            if (file_exists($fb)) {
                return response()->download($fb, $batch->file_name);
            }
        }

        return redirect()->back()->with('error', 'Berkas Excel asli tidak ditemukan di storage server.');
    }

    /**
     * Delete an import batch and its records.
     */
    public function destroyBatch(ShoeImportBatch $batch)
    {
        $schoolName = $batch->school?->nama_sekolah;
        $batch->delete();

        return redirect()->route('shoes.index')->with('success', "Batch import untuk {$schoolName} berhasil dihapus.");
    }

    /**
     * Delete an entire school and all its shoe data.
     */
    public function destroySchool(School $school)
    {
        $name = $school->nama_sekolah;
        $school->delete();

        return redirect()->route('shoes.index')->with('success', "Data sekolah {$name} beserta seluruh data sepatu berhasil dihapus.");
    }

    /**
     * Update school metadata (e.g. PIC, Kota, Kepala Sekolah).
     */
    public function updateSchool(Request $request, School $school)
    {
        $validated = $request->validate([
            'pic_nama' => 'nullable|string|max:100',
            'kota' => 'nullable|string|max:100',
            'kepala_sekolah_nama' => 'nullable|string|max:100',
            'kepala_sekolah_nip' => 'nullable|string|max:100',
        ]);

        if (array_key_exists('pic_nama', $validated)) {
            $validated['pic_nama'] = School::normalizePicName($validated['pic_nama']);
        }

        $school->update($validated);

        return redirect()->back()->with('success', "Informasi sekolah {$school->nama_sekolah} berhasil diperbarui.");
    }

    /**
     * Re-synchronize all Excel files in storage into database.
     */
    public function syncStorage(Request $request)
    {
        $batches = ShoeImportBatch::with('school')->get();
        $processedCount = 0;
        $totalShoes = 0;
        $errors = [];

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
                    $errors[] = "Berkas {$batch->file_name} tidak ditemukan di storage.";
                    continue;
                }

                try {
                    $currentPic = $batch->school?->pic_nama;
                    $summary = $this->importService->import($filePath, $batch->file_name, $batch->imported_by, $currentPic);
                    $totalShoes += $summary['total_sepatu'];
                    $processedCount++;
                } catch (\Throwable $e) {
                    $errors[] = "Gagal memproses {$batch->file_name}: " . $e->getMessage();
                }
            }
        } else {
            $files = Storage::disk('local')->files('excel_imports');
            foreach ($files as $rel) {
                $full = Storage::disk('local')->path($rel);
                $orig = basename($rel);
                try {
                    $summary = $this->importService->import($full, $orig, auth()->id());
                    $totalShoes += $summary['total_sepatu'];
                    $processedCount++;
                } catch (\Throwable $e) {
                    $errors[] = "Gagal memproses {$orig}: " . $e->getMessage();
                }
            }
        }

        if ($processedCount === 0 && empty($errors)) {
            return redirect()->back()->with('error', 'Tidak ada berkas Excel di storage yang dapat disinkronkan.');
        }

        $msg = "Sinkronisasi selesai: Berhasil memperbarui {$processedCount} berkas sekolah ({$totalShoes} total pasang sepatu).";
        if (!empty($errors)) {
            $msg .= " Catatan kendala: " . implode('; ', $errors);
        }

        return redirect()->route('shoes.index')->with('success', $msg);
    }

    /**
     * Update recipient data (e.g. to fix anomalies in shoe size or foot length).
     */
    public function updateRecipient(Request $request, ShoeRecipient $recipient)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'jabatan' => 'nullable|string|max:255',
            'kategori' => 'required|in:siswa,guru,tendik',
            'panjang_kaki' => 'nullable|numeric|min:0|max:100',
            'ukuran_sepatu' => 'required|integer|min:15|max:150',
            'catatan' => 'nullable|string|max:500',
        ]);

        $recipient->update($validated);

        // Recalculate batch statistics if batch exists
        if ($recipient->batch) {
            $batch = $recipient->batch;
            $batch->update([
                'total_sepatu' => $batch->recipients()->count(),
                'total_siswa' => $batch->recipients()->where('kategori', 'siswa')->count(),
                'total_guru' => $batch->recipients()->where('kategori', 'guru')->count(),
                'total_tendik' => $batch->recipients()->where('kategori', 'tendik')->count(),
            ]);
        }

        return redirect()->back()->with('success', "Data penerima {$recipient->nama_lengkap} (Ukuran: {$recipient->ukuran_sepatu}) berhasil diperbarui.");
    }
}
