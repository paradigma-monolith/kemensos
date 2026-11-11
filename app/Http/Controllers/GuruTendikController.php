<?php

namespace App\Http\Controllers;

use App\Models\GuruTendikBatch;
use App\Models\GuruTendikRecord;
use App\Models\School;
use App\Services\GuruTendikExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuruTendikController extends Controller
{
    public function __construct(
        protected GuruTendikExcelService $excelService
    ) {}

    /**
     * Display the Guru & Tendik database import and recapitulation dashboard.
     */
    public function index(Request $request)
    {
        $sekolah = $request->query('sekolah');
        $jenisKelamin = $request->query('jenis_kelamin');
        $status = $request->query('status');
        $jabatan = $request->query('jabatan');
        $pic = $request->query('pic');
        $search = $request->query('search');

        // Master PIC list
        $existingPics = GuruTendikRecord::whereNotNull('pic_nama')
            ->where('pic_nama', '!=', '')
            ->distinct()
            ->pluck('pic_nama')
            ->map(fn($p) => School::normalizePicName($p))
            ->filter()
            ->toArray();
        $allPics = array_values(array_unique(array_merge(School::DEFAULT_PICS, $existingPics)));
        sort($allPics);

        // Distinct filter options from database
        $schoolList = GuruTendikRecord::whereNotNull('sekolah')
            ->where('sekolah', '!=', '')
            ->distinct()
            ->orderBy('sekolah')
            ->pluck('sekolah')
            ->toArray();

        $statusList = GuruTendikRecord::whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->toArray();

        $jabatanList = GuruTendikRecord::whereNotNull('jabatan')
            ->where('jabatan', '!=', '')
            ->distinct()
            ->orderBy('jabatan')
            ->pluck('jabatan')
            ->toArray();

        // Filtered base query
        $baseQuery = GuruTendikRecord::query()
            ->when($sekolah, fn($q) => $q->where('sekolah', $sekolah))
            ->when($jenisKelamin, fn($q) => $q->where('jenis_kelamin', $jenisKelamin))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($jabatan, fn($q) => $q->where('jabatan', $jabatan))
            ->when($pic, fn($q) => $q->where('pic_nama', School::normalizePicName($pic)))
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%")
                        ->orWhere('sekolah', 'like', "%{$search}%");
                });
            });

        $totalData = (clone $baseQuery)->count();
        $totalLaki = (clone $baseQuery)->where('jenis_kelamin', 'Laki-laki')->count();
        $totalPerempuan = (clone $baseQuery)->where('jenis_kelamin', 'Perempuan')->count();
        $totalSekolah = (clone $baseQuery)->distinct('sekolah')->count('sekolah');
        $totalBatches = GuruTendikBatch::count();

        // Rekapitulasi per Sekolah
        $rekapSekolah = (clone $baseQuery)
            ->selectRaw('
                sekolah,
                COUNT(*) as total,
                SUM(CASE WHEN jenis_kelamin = "Laki-laki" THEN 1 ELSE 0 END) as laki,
                SUM(CASE WHEN jenis_kelamin = "Perempuan" THEN 1 ELSE 0 END) as perempuan
            ')
            ->groupBy('sekolah')
            ->orderBy('sekolah')
            ->get();

        // Rekapitulasi per Status
        $rekapStatus = (clone $baseQuery)
            ->selectRaw('
                status,
                COUNT(*) as total,
                SUM(CASE WHEN jenis_kelamin = "Laki-laki" THEN 1 ELSE 0 END) as laki,
                SUM(CASE WHEN jenis_kelamin = "Perempuan" THEN 1 ELSE 0 END) as perempuan
            ')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        // Paginated Records (15 per page)
        $records = (clone $baseQuery)
            ->orderBy('sekolah')
            ->orderBy('jenis_kelamin')
            ->orderBy('no')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        // Paginated Batches (10 per page)
        $batches = GuruTendikBatch::with('user:id,name')
            ->latest()
            ->paginate(10, ['*'], 'batch_page')
            ->withQueryString();

        $tab = $request->input('tab');
        if (!$tab) {
            if ($request->has('batch_page')) {
                $tab = 'batches';
            } else {
                $tab = 'records';
            }
        }

        return Inertia::render('GuruTendik/Index', [
            'stats' => [
                'total_data' => $totalData,
                'total_laki' => $totalLaki,
                'total_perempuan' => $totalPerempuan,
                'total_sekolah' => $totalSekolah,
                'total_batches' => $totalBatches,
            ],
            'filters' => [
                'sekolah' => $sekolah ?? '',
                'jenis_kelamin' => $jenisKelamin ?? '',
                'status' => $status ?? '',
                'jabatan' => $jabatan ?? '',
                'pic' => $pic ?? '',
                'search' => $search ?? '',
                'tab' => $tab,
            ],
            'records' => $records,
            'rekapSekolah' => $rekapSekolah,
            'rekapStatus' => $rekapStatus,
            'batches' => $batches,
            'schoolList' => $schoolList,
            'statusList' => $statusList,
            'jabatanList' => $jabatanList,
            'picList' => $allPics,
            'schema' => GuruTendikExcelService::IMPORT_SCHEMA,
        ]);
    }

    /**
     * Handle multi-file Excel import for FORMAT REKAP TOTAL REVISI.xlsx.
     */
    public function import(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

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

        $files = [];
        if ($request->hasFile('excel_files')) {
            $uploaded = $request->file('excel_files');
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
        } elseif ($request->hasFile('excel_file')) {
            $files = [$request->file('excel_file')];
        }

        if (empty($files)) {
            return redirect()->route('guru-tendik.index')->with('error', 'Silakan pilih setidaknya satu berkas Excel untuk di-import.');
        }

        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
                return redirect()->route('guru-tendik.index')->with('error', "Berkas '{$file->getClientOriginalName()}' harus berformat Excel (.xlsx atau .xls).");
            }
        }

        $importedSummaries = [];
        $totalImported = 0;
        $allWarnings = [];
        $failedFiles = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            try {
                Storage::disk('local')->makeDirectory('guru_tendik_imports');

                $storedName = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $storedPath = $file->storeAs('guru_tendik_imports', $storedName, 'local');
                $absolutePath = Storage::disk('local')->path($storedPath);

                if (!file_exists($absolutePath)) {
                    if (file_exists(storage_path('app/private/' . $storedPath))) {
                        $absolutePath = storage_path('app/private/' . $storedPath);
                    } elseif (file_exists(storage_path('app/' . $storedPath))) {
                        $absolutePath = storage_path('app/' . $storedPath);
                    } elseif ($file->getRealPath() && file_exists($file->getRealPath())) {
                        $absolutePath = $file->getRealPath();
                    }
                }

                $summary = $this->excelService->import($absolutePath, $originalName, auth()->id(), $selectedPic);
                $totalImported += $summary['total_data'];
                $importedSummaries[] = "{$originalName} ({$summary['total_data']} data: {$summary['total_laki']} L, {$summary['total_perempuan']} P)";

                if (!empty($summary['warnings'])) {
                    $maxPreview = 10;
                    $previewWarnings = array_slice($summary['warnings'], 0, $maxPreview);
                    foreach ($previewWarnings as $w) {
                        $allWarnings[] = "[{$originalName}] {$w}";
                    }
                    $remaining = count($summary['warnings']) - $maxPreview;
                    if ($remaining > 0) {
                        $allWarnings[] = "[{$originalName}] ...dan {$remaining} baris lainnya memiliki kolom kosong yang telah diisi nilai default.";
                    }
                }
            } catch (\Throwable $e) {
                $failedFiles[] = "{$originalName}: " . $e->getMessage();
            }
        }

        if (!empty($importedSummaries) && empty($failedFiles)) {
            $countFiles = count($importedSummaries);
            $message = "Berhasil mengimpor {$countFiles} berkas ({$totalImported} total data Guru & Tendik): " . implode(', ', $importedSummaries) . '.';
            return redirect()->route('guru-tendik.index')->with([
                'success' => $message,
                'import_warnings' => $allWarnings,
            ]);
        } elseif (!empty($importedSummaries) && !empty($failedFiles)) {
            $message = 'Berhasil mengimpor ' . count($importedSummaries) . ' berkas (' . implode(', ', $importedSummaries) . '), namun ' . count($failedFiles) . ' berkas gagal: ' . implode('; ', $failedFiles);
            return redirect()->route('guru-tendik.index')->with([
                'success' => $message,
                'import_warnings' => $allWarnings,
            ]);
        }

        return redirect()->route('guru-tendik.index')->with('error', 'Gagal memproses berkas Excel: ' . implode('; ', $failedFiles));
    }

    /**
     * Export Guru & Tendik data to Excel (matching FORMAT REKAP TOTAL REVISI.xlsx structure).
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '300');

        $filters = [
            'sekolah' => $request->query('sekolah'),
            'jenis_kelamin' => $request->query('jenis_kelamin'),
            'status' => $request->query('status'),
            'jabatan' => $request->query('jabatan'),
            'pic' => $request->query('pic'),
            'search' => $request->query('search'),
        ];
        $deduplicate = $request->boolean('deduplicate') || $request->boolean('remove_duplicates');

        $spreadsheet = $this->excelService->generateExportExcel($filters, $deduplicate);
        $fileName = 'FORMAT_REKAP_TOTAL_REVISI_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Download blank/sample template FORMAT REKAP TOTAL REVISI.xlsx.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = $this->excelService->generateTemplateExcel();
        $fileName = 'FORMAT REKAP TOTAL REVISI.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Download original uploaded batch Excel file.
     */
    public function downloadBatch(GuruTendikBatch $batch)
    {
        $filePath = $batch->file_path;

        if ($filePath && file_exists($filePath) && is_readable($filePath)) {
            return response()->download($filePath, $batch->file_name);
        }

        if ($filePath && Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->download($filePath, $batch->file_name);
        }

        $fallbacks = [
            storage_path('app/' . $filePath),
            storage_path('app/private/' . $filePath),
            storage_path('app/guru_tendik_imports/' . basename((string) $filePath)),
        ];

        foreach ($fallbacks as $fb) {
            if (file_exists($fb) && is_readable($fb)) {
                return response()->download($fb, $batch->file_name);
            }
        }

        return redirect()->route('guru-tendik.index')->with('error', 'Berkas fisik asli tidak ditemukan di penyimpanan.');
    }

    /**
     * Delete an import batch and all its records.
     */
    public function destroyBatch(GuruTendikBatch $batch)
    {
        $name = $batch->file_name;
        if ($batch->file_path && file_exists($batch->file_path)) {
            @unlink($batch->file_path);
        }
        $batch->delete();

        return redirect()->route('guru-tendik.index', ['tab' => 'batches'])
            ->with('success', "Riwayat berkas '{$name}' beserta seluruh datanya berhasil dihapus.");
    }

    /**
     * Update an individual Guru & Tendik record.
     */
    public function updateRecord(Request $request, GuruTendikRecord $record)
    {
        $validated = $request->validate([
            'no' => 'nullable|integer|min:1',
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|string|max:50',
            'status' => 'required|string|max:100',
            'jabatan' => 'required|string|max:150',
            'sekolah' => 'required|string|max:255',
        ]);

        $validated['jenis_kelamin'] = GuruTendikRecord::normalizeGender($validated['jenis_kelamin'], $record->jenis_kelamin);
        $record->update($validated);

        return redirect()->back()->with('success', "Data '{$record->nama_lengkap}' berhasil diperbarui.");
    }

    /**
     * Delete an individual Guru & Tendik record.
     */
    public function destroyRecord(GuruTendikRecord $record)
    {
        $name = $record->nama_lengkap;
        $batch = $record->batch;
        $record->delete();

        if ($batch) {
            $batch->update([
                'total_laki' => $batch->records()->where('jenis_kelamin', 'Laki-laki')->count(),
                'total_perempuan' => $batch->records()->where('jenis_kelamin', 'Perempuan')->count(),
                'total_data' => $batch->records()->count(),
                'total_sekolah' => $batch->records()->distinct('sekolah')->count('sekolah'),
            ]);
        }

        return redirect()->back()->with('success', "Data '{$name}' berhasil dihapus.");
    }
}

