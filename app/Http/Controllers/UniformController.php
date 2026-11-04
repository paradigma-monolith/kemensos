<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\UniformImportBatch;
use App\Models\UniformRecipient;
use App\Services\UniformExcelImportService;
use App\Services\UniformExportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UniformController extends Controller
{
    public function __construct(
        protected UniformExcelImportService $uniformService,
        protected UniformExportService $exportService
    ) {}

    /**
     * Display the uniform recapitulation dashboard, import history, and data view.
     */
    public function index(Request $request): Response
    {
        $schema = UniformExcelImportService::SCHEMA;
        $schoolId = $request->query('school_id');
        $pic = $request->query('pic');
        $jenjang = $request->query('jenjang');
        $kategori = $request->query('kategori');
        $gender = $request->query('gender');
        $search = $request->query('search');
        $batchId = $request->query('batch_id');

        // Master PIC list combining default constant, schools, and import batches
        $existingPics = School::whereNotNull('pic_nama')
            ->where('pic_nama', '!=', '')
            ->distinct()
            ->pluck('pic_nama')
            ->map(fn($p) => School::normalizePicName($p))
            ->filter()
            ->toArray();

        $batchPics = UniformImportBatch::whereNotNull('pic_nama')
            ->where('pic_nama', '!=', '')
            ->distinct()
            ->pluck('pic_nama')
            ->map(fn($p) => School::normalizePicName($p))
            ->filter()
            ->toArray();

        $allPics = array_values(array_unique(array_merge(School::DEFAULT_PICS, $existingPics, $batchPics)));
        sort($allPics);

        // Import Batches query (15 items per page)
        $batchQuery = UniformImportBatch::with(['school:id,nama_sekolah,pic_nama', 'user:id,name'])
            ->when($pic, function ($q, $pic) {
                $norm = School::normalizePicName($pic);
                $q->where(function ($sub) use ($norm) {
                    $sub->where('pic_nama', $norm)
                        ->orWhere('pic_nama', 'PIC ' . $norm)
                        ->orWhereHas('school', fn($sq) => $sq->where('pic_nama', $norm)->orWhere('pic_nama', 'PIC ' . $norm));
                });
            })
            ->when($schoolId, function ($q, $sId) {
                $q->where('school_id', $sId);
            })
            ->when($jenjang, function ($q, $j) {
                $q->where(function ($sub) use ($j) {
                    $sub->where('school_name', 'like', "%{$j}%")
                        ->orWhere('file_name', 'like', "%{$j}%");
                });
            })
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('file_name', 'like', "%{$s}%")
                        ->orWhere('school_name', 'like', "%{$s}%");
                });
            })
            ->latest();

        $batches = (clone $batchQuery)->paginate(15, ['*'], 'batch_page')->withQueryString();

        // High-level statistics
        $statsBatchQuery = clone $batchQuery;
        $totalPenerima = (int)$statsBatchQuery->sum('total_rows');
        $totalSiswa = (int)$statsBatchQuery->sum('total_siswa');
        $totalGuruTendik = (int)(
            $statsBatchQuery->sum('total_kepsek') +
            $statsBatchQuery->sum('total_guru_lama') +
            $statsBatchQuery->sum('total_guru_baru') +
            $statsBatchQuery->sum('total_tendik_lama') +
            $statsBatchQuery->sum('total_tendik_baru')
        );
        $totalStaffKhusus = (int)(
            $statsBatchQuery->sum('total_satpam') +
            $statsBatchQuery->sum('total_kebersihan') +
            $statsBatchQuery->sum('total_dapur')
        );
        $totalBatches = $statsBatchQuery->count();
        $totalSchools = School::whereIn('id', (clone $statsBatchQuery)->whereNotNull('school_id')->pluck('school_id'))->distinct()->count();
        $matchingBatchIds = (clone $statsBatchQuery)->pluck('id')->toArray();

        // Jenjang stats for quick cards (SRD, SRMP, SRMA) strictly for SISWA within matching batches
        $sheetStats = [
            'srd' => ['total' => 0, 'pria' => 0, 'wanita' => 0],
            'srmp' => ['total' => 0, 'pria' => 0, 'wanita' => 0],
            'srma' => ['total' => 0, 'pria' => 0, 'wanita' => 0],
        ];

        if (!empty($matchingBatchIds)) {
            $siswaRecipients = UniformRecipient::whereIn('batch_id', $matchingBatchIds)
                ->where('sheet_name', 'SISWA')
                ->get(['jenjang', 'jenis_kelamin', 'batch_id']);

            $batchSchoolNames = UniformImportBatch::whereIn('id', $matchingBatchIds)->pluck('school_name', 'id')->toArray();

            foreach ($siswaRecipients as $sr) {
                $j = strtoupper(trim((string)$sr->jenjang));
                $sch = strtoupper((string)($batchSchoolNames[$sr->batch_id] ?? ''));
                $jk = UniformExcelImportService::normalizeGender($sr->jenis_kelamin);

                if (str_contains($j, 'SD') || str_contains($j, 'SRD') || str_contains($sch, 'SRD')) {
                    $code = 'srd';
                } elseif (str_contains($j, 'SMP') || str_contains($j, 'SRMP') || str_contains($sch, 'SRMP')) {
                    $code = 'srmp';
                } else {
                    $code = 'srma';
                }

                $sheetStats[$code]['total']++;
                if ($jk === 'L') {
                    $sheetStats[$code]['pria']++;
                } else {
                    $sheetStats[$code]['wanita']++;
                }
            }
        }

        // Schools that have uniform batches
        $schools = School::withCount(['uniformBatches', 'uniformRecipients'])
            ->whereIn('id', UniformImportBatch::whereNotNull('school_id')->pluck('school_id'))
            ->orWhereHas('uniformBatches')
            ->orderBy('nama_sekolah')
            ->get();

        // Determine active recapitulation data
        $activeRecap = null;
        if ($batchId) {
            $selectedBatch = UniformImportBatch::find($batchId);
            if ($selectedBatch && !empty($selectedBatch->recap_data)) {
                $activeRecap = $selectedBatch->recap_data;
                $activeRecap['batch_id'] = $selectedBatch->id;
                $activeRecap['pic_nama'] = $selectedBatch->pic_nama;
                $activeRecap['school_name'] = $selectedBatch->school_name;
                $activeRecap['file_name'] = $selectedBatch->file_name;
            }
        }

        if (!$activeRecap && session('uniform_recap')) {
            $activeRecap = session('uniform_recap');
        }

        if (!$activeRecap && $schoolId) {
            $schoolBatch = UniformImportBatch::where('school_id', $schoolId)->latest()->first();
            if ($schoolBatch && !empty($schoolBatch->recap_data)) {
                $activeRecap = $schoolBatch->recap_data;
                $activeRecap['batch_id'] = $schoolBatch->id;
                $activeRecap['pic_nama'] = $schoolBatch->pic_nama;
                $activeRecap['school_name'] = $schoolBatch->school_name;
                $activeRecap['file_name'] = $schoolBatch->file_name;
            }
        }

        if (!$activeRecap) {
            $latestBatch = UniformImportBatch::latest()->first();
            if ($latestBatch && !empty($latestBatch->recap_data)) {
                $activeRecap = $latestBatch->recap_data;
                $activeRecap['batch_id'] = $latestBatch->id;
                $activeRecap['pic_nama'] = $latestBatch->pic_nama;
                $activeRecap['school_name'] = $latestBatch->school_name;
                $activeRecap['file_name'] = $latestBatch->file_name;
            }
        }

        if (!$activeRecap) {
            $initialData = [];
            foreach ($schema as $key => $conf) {
                $initialData[$key] = [
                    'structure' => $conf['structure'],
                    'data' => [],
                ];
            }
            $activeRecap = [
                'school_name' => null,
                'file_name' => null,
                'pic_nama' => null,
                'header_rows' => [1, 2],
                'data_start_row' => 3,
                'total_rows' => 0,
                'sheet_counts' => array_fill_keys(array_keys($schema), 0),
                'recapitulation' => $initialData,
            ];
        }

        // Fallback to activeRecap if database has no rows for quick cards
        if ($sheetStats['srd']['total'] === 0 && $sheetStats['srmp']['total'] === 0 && $sheetStats['srma']['total'] === 0 && !empty($activeRecap['recapitulation']['SISWA']['data'])) {
            $sch = strtoupper((string)($activeRecap['school_name'] ?? ''));
            foreach ($activeRecap['recapitulation']['SISWA']['data'] as $r) {
                $j = strtoupper(trim((string)($r['jenjang'] ?? '')));
                $jk = UniformExcelImportService::normalizeGender($r['jenis_kelamin'] ?? null);

                if (str_contains($j, 'SD') || str_contains($j, 'SRD') || str_contains($sch, 'SRD')) {
                    $code = 'srd';
                } elseif (str_contains($j, 'SMP') || str_contains($j, 'SRMP') || str_contains($sch, 'SRMP')) {
                    $code = 'srmp';
                } else {
                    $code = 'srma';
                }

                $sheetStats[$code]['total']++;
                if ($jk === 'L') {
                    $sheetStats[$code]['pria']++;
                } else {
                    $sheetStats[$code]['wanita']++;
                }
            }
        }

        // Aggregate counts by sheet and gender across matching batches
        $sheetCountsMap = [];
        if (!empty($matchingBatchIds)) {
            $recipientCountsBySheet = UniformRecipient::whereIn('batch_id', $matchingBatchIds)
                ->selectRaw('sheet_name, jenis_kelamin, COUNT(*) as total')
                ->groupBy('sheet_name', 'jenis_kelamin')
                ->get();

            foreach ($recipientCountsBySheet as $row) {
                $sName = $row->sheet_name;
                $jk = UniformExcelImportService::normalizeGender($row->jenis_kelamin);
                if (!isset($sheetCountsMap[$sName])) {
                    $sheetCountsMap[$sName] = ['total' => 0, 'pria' => 0, 'wanita' => 0];
                }
                $sheetCountsMap[$sName]['total'] += (int)$row->total;
                if ($jk === 'L') {
                    $sheetCountsMap[$sName]['pria'] += (int)$row->total;
                } elseif ($jk === 'P') {
                    $sheetCountsMap[$sName]['wanita'] += (int)$row->total;
                }
            }
        }

        // Fallback aggregation from activeRecap if matching batches query has no database rows
        if (empty($sheetCountsMap) && !empty($activeRecap['recapitulation'])) {
            foreach ($activeRecap['recapitulation'] as $sheetName => $sData) {
                $sheetCountsMap[$sheetName] = ['total' => 0, 'pria' => 0, 'wanita' => 0];
                foreach ($sData['data'] ?? [] as $r) {
                    $jk = UniformExcelImportService::normalizeGender($r['jenis_kelamin'] ?? null);
                    $sheetCountsMap[$sheetName]['total']++;
                    if ($jk === 'L') {
                        $sheetCountsMap[$sheetName]['pria']++;
                    } elseif ($jk === 'P') {
                        $sheetCountsMap[$sheetName]['wanita']++;
                    }
                }
            }
        }

        // Staff stats separated by exact sheet
        $staffStats = [
            'kepsek' => [
                'sheet' => 'KEPSEK',
                'label' => 'Kepala Sekolah',
                'badge' => 'KEPSEK',
                'sublabel' => 'Sheet KEPSEK',
                'color' => 'amber',
                'total' => $sheetCountsMap['KEPSEK']['total'] ?? (int)$statsBatchQuery->sum('total_kepsek'),
                'pria' => $sheetCountsMap['KEPSEK']['pria'] ?? 0,
                'wanita' => $sheetCountsMap['KEPSEK']['wanita'] ?? 0,
            ],
            'guru_lama' => [
                'sheet' => 'GURU LAMA',
                'label' => 'Guru Lama',
                'badge' => 'GURU LAMA',
                'sublabel' => 'Sheet GURU LAMA',
                'color' => 'sky',
                'total' => $sheetCountsMap['GURU LAMA']['total'] ?? (int)$statsBatchQuery->sum('total_guru_lama'),
                'pria' => $sheetCountsMap['GURU LAMA']['pria'] ?? 0,
                'wanita' => $sheetCountsMap['GURU LAMA']['wanita'] ?? 0,
            ],
            'guru_baru' => [
                'sheet' => 'GURU BARU',
                'label' => 'Guru Baru',
                'badge' => 'GURU BARU',
                'sublabel' => 'Sheet GURU / BARU',
                'color' => 'indigo',
                'total' => $sheetCountsMap['GURU BARU']['total'] ?? (int)$statsBatchQuery->sum('total_guru_baru'),
                'pria' => $sheetCountsMap['GURU BARU']['pria'] ?? 0,
                'wanita' => $sheetCountsMap['GURU BARU']['wanita'] ?? 0,
            ],
            'tendik_lama' => [
                'sheet' => 'TENDIK LAMA',
                'label' => 'Tendik Lama',
                'badge' => 'TENDIK LAMA',
                'sublabel' => 'Sheet TENDIK LAMA',
                'color' => 'emerald',
                'total' => $sheetCountsMap['TENDIK LAMA']['total'] ?? (int)$statsBatchQuery->sum('total_tendik_lama'),
                'pria' => $sheetCountsMap['TENDIK LAMA']['pria'] ?? 0,
                'wanita' => $sheetCountsMap['TENDIK LAMA']['wanita'] ?? 0,
            ],
            'tendik_baru' => [
                'sheet' => 'TENDIK BARU',
                'label' => 'Tendik Baru',
                'badge' => 'TENDIK BARU',
                'sublabel' => 'Sheet TENDIK / BARU',
                'color' => 'teal',
                'total' => $sheetCountsMap['TENDIK BARU']['total'] ?? (int)$statsBatchQuery->sum('total_tendik_baru'),
                'pria' => $sheetCountsMap['TENDIK BARU']['pria'] ?? 0,
                'wanita' => $sheetCountsMap['TENDIK BARU']['wanita'] ?? 0,
            ],
            'satpam' => [
                'sheet' => 'SATPAM',
                'label' => 'Satpam (PDH)',
                'badge' => 'SATPAM',
                'sublabel' => 'Sheet SATPAM (+PDH)',
                'color' => 'purple',
                'total' => $sheetCountsMap['SATPAM']['total'] ?? (int)$statsBatchQuery->sum('total_satpam'),
                'pria' => $sheetCountsMap['SATPAM']['pria'] ?? 0,
                'wanita' => $sheetCountsMap['SATPAM']['wanita'] ?? 0,
            ],
            'kebersihan' => [
                'sheet' => 'PETUGAS KEBERSIHAN',
                'label' => 'Petugas Kebersihan',
                'badge' => 'KEBERSIHAN',
                'sublabel' => 'Sheet KEBERSIHAN',
                'color' => 'rose',
                'total' => $sheetCountsMap['PETUGAS KEBERSIHAN']['total'] ?? (int)$statsBatchQuery->sum('total_kebersihan'),
                'pria' => $sheetCountsMap['PETUGAS KEBERSIHAN']['pria'] ?? 0,
                'wanita' => $sheetCountsMap['PETUGAS KEBERSIHAN']['wanita'] ?? 0,
            ],
            'dapur' => [
                'sheet' => 'PETUGAS DAPUR',
                'label' => 'Petugas Dapur',
                'badge' => 'DAPUR',
                'sublabel' => 'Sheet DAPUR',
                'color' => 'orange',
                'total' => $sheetCountsMap['PETUGAS DAPUR']['total'] ?? (int)$statsBatchQuery->sum('total_dapur'),
                'pria' => $sheetCountsMap['PETUGAS DAPUR']['pria'] ?? 0,
                'wanita' => $sheetCountsMap['PETUGAS DAPUR']['wanita'] ?? 0,
            ],
        ];

        // Determine active primary tab (recap | batches)
        $tab = $request->input('tab');
        if (!$tab) {
            if ($request->has('batch_page')) {
                $tab = 'batches';
            } else {
                $tab = 'recap';
            }
        }

        return Inertia::render('Uniforms/Index', [
            'initialRecap' => $activeRecap,
            'schema' => $schema,
            'picList' => $allPics,
            'schools' => $schools,
            'batches' => $batches,
            'stats' => [
                'total_penerima' => $totalPenerima,
                'total_siswa' => $totalSiswa,
                'total_guru_tendik' => $totalGuruTendik,
                'total_staff_khusus' => $totalStaffKhusus,
                'total_schools' => $totalSchools,
                'total_batches' => $totalBatches,
                'sheet_stats' => $sheetStats,
                'staff_stats' => $staffStats,
            ],
            'filters' => [
                'school_id' => $schoolId ? (int)$schoolId : null,
                'batch_id' => $batchId ? (int)$batchId : ($activeRecap['batch_id'] ?? null),
                'pic' => $pic,
                'jenjang' => $jenjang,
                'kategori' => $kategori,
                'gender' => $gender,
                'search' => $search,
                'tab' => $tab,
            ],
        ]);
    }

    /**
     * Handle Excel upload and import into database (supports multiple files and PIC selection).
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_files' => 'nullable|array',
            'excel_files.*' => 'file|max:51200',
            'excel_file' => 'nullable|file|max:51200',
            'file' => 'nullable|file|max:51200',
            'pic_nama' => 'nullable|string|max:100',
        ], [
            'excel_files.*.max' => 'Ukuran setiap berkas maksimal adalah 50MB.',
            'excel_file.max' => 'Ukuran berkas maksimal adalah 50MB.',
            'file.max' => 'Ukuran berkas maksimal adalah 50MB.',
        ]);

        $selectedPic = School::normalizePicName($request->input('pic_nama'));

        // Collect all uploaded files into an array
        $files = [];
        if ($request->hasFile('excel_files')) {
            $uploaded = $request->file('excel_files');
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
        } elseif ($request->hasFile('excel_file')) {
            $files = [$request->file('excel_file')];
        } elseif ($request->hasFile('file')) {
            $files = [$request->file('file')];
        }

        if (empty($files)) {
            return redirect()->route('uniforms.index')->with('error', 'Silakan pilih setidaknya satu berkas Excel untuk di-import.');
        }

        // Validate extensions
        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
                return redirect()->route('uniforms.index')->with('error', "Berkas '{$file->getClientOriginalName()}' harus berformat Excel (.xlsx atau .xls).");
            }
        }

        $importedSummaries = [];
        $totalImportedRows = 0;
        $failedFiles = [];
        $lastRecap = null;

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            try {
                $summary = $this->uniformService->import($file->getRealPath(), $originalName, auth()->id(), $selectedPic);
                $totalImportedRows += $summary['total_rows'];
                $importedSummaries[] = "{$summary['school_name']} ({$summary['total_rows']} penerima)";
                $lastRecap = $summary;
            } catch (\Throwable $e) {
                $failedFiles[] = "{$originalName}: " . $e->getMessage();
            }
        }

        // Prepare response
        if (!empty($importedSummaries) && empty($failedFiles)) {
            $countFiles = count($importedSummaries);
            $message = "Berhasil mengimpor {$countFiles} berkas sekolah ({$totalImportedRows} data ukuran seragam): " . implode(', ', $importedSummaries) . ".";
            
            $redirect = redirect()->route('uniforms.index', ['tab' => 'recap']);
            if ($lastRecap && isset($lastRecap['batch'])) {
                $redirect = redirect()->route('uniforms.index', ['batch_id' => $lastRecap['batch']->id, 'tab' => 'recap']);
            }
            return $redirect->with('success', $message);
        } elseif (!empty($importedSummaries) && !empty($failedFiles)) {
            $message = "Berhasil mengimpor " . count($importedSummaries) . " berkas (" . implode(', ', $importedSummaries) . "), namun " . count($failedFiles) . " berkas gagal: " . implode('; ', $failedFiles);
            return redirect()->route('uniforms.index', ['tab' => 'batches'])->with('success', $message);
        } else {
            return redirect()->route('uniforms.index')->with('error', 'Gagal memproses berkas Excel: ' . implode('; ', $failedFiles));
        }
    }

    /**
     * Parse endpoint for instant previews, testing, or API calls.
     */
    public function parse(Request $request)
    {
        return $this->import($request);
    }

    /**
     * Download original imported Excel file.
     */
    public function downloadBatch(UniformImportBatch $batch)
    {
        $filePath = $batch->file_path;

        if ($filePath && Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->download($filePath, $batch->file_name);
        }

        if ($filePath && file_exists($filePath)) {
            return response()->download($filePath, $batch->file_name);
        }

        $fallbacks = [
            storage_path('app/' . $filePath),
            storage_path('app/private/' . $filePath),
            storage_path('app/uniform_imports/' . basename($filePath)),
            storage_path('app/private/uniform_imports/' . basename($filePath)),
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
    public function destroyBatch(UniformImportBatch $batch)
    {
        $schoolName = $batch->school_name ?? $batch->school?->nama_sekolah ?? $batch->file_name;
        $batch->delete();

        return redirect()->route('uniforms.index', ['tab' => 'batches'])->with('success', "Batch import berkas {$schoolName} berhasil dihapus.");
    }

    /**
     * Re-synchronize all Excel files in storage into database.
     */
    public function syncStorage(Request $request)
    {
        $batches = UniformImportBatch::all();
        $processedCount = 0;
        $totalRows = 0;
        $errors = [];

        if ($batches->isNotEmpty()) {
            foreach ($batches as $batch) {
                $filePath = $batch->file_path;
                $candidates = [
                    $filePath,
                    Storage::disk('local')->path($filePath),
                    storage_path('app/' . $filePath),
                    storage_path('app/private/' . $filePath),
                    storage_path('app/uniform_imports/' . basename($filePath)),
                    storage_path('app/private/uniform_imports/' . basename($filePath)),
                ];

                $resolved = null;
                foreach ($candidates as $c) {
                    if ($c && file_exists($c)) {
                        $resolved = $c;
                        break;
                    }
                }

                if (!$resolved) {
                    $errors[] = "Berkas {$batch->file_name} tidak ditemukan di storage.";
                    continue;
                }

                try {
                    $summary = $this->uniformService->import($resolved, $batch->file_name, $batch->imported_by, $batch->pic_nama);
                    $totalRows += $summary['total_rows'];
                    $processedCount++;
                } catch (\Throwable $e) {
                    $errors[] = "Gagal memproses {$batch->file_name}: " . $e->getMessage();
                }
            }
        } else {
            $files = array_merge(
                Storage::disk('local')->files('uniform_imports'),
                Storage::disk('local')->files('private/uniform_imports')
            );

            foreach ($files as $rel) {
                $full = Storage::disk('local')->path($rel);
                $orig = basename($rel);
                try {
                    $summary = $this->uniformService->import($full, $orig, auth()->id());
                    $totalRows += $summary['total_rows'];
                    $processedCount++;
                } catch (\Throwable $e) {
                    $errors[] = "Gagal memproses {$orig}: " . $e->getMessage();
                }
            }
        }

        if ($processedCount === 0 && empty($errors)) {
            return redirect()->back()->with('error', 'Tidak ada berkas Excel seragam di storage yang dapat disinkronkan.');
        }

        $msg = "Sinkronisasi selesai: Berhasil memperbarui {$processedCount} berkas sekolah ({$totalRows} total data ukuran seragam).";
        if (!empty($errors)) {
            $msg .= " Catatan kendala: " . implode('; ', $errors);
        }

        return redirect()->route('uniforms.index', ['tab' => 'batches'])->with('success', $msg);
    }

    /**
     * Export uniform recapitulation to Excel.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $batchId = $request->query('batch_id') ? (int)$request->query('batch_id') : null;
        $schoolId = $request->query('school_id') ? (int)$request->query('school_id') : null;

        $spreadsheet = $this->exportService->generateRecapExcel($batchId, $schoolId);

        $schoolPrefix = 'Semua_Sekolah';
        if ($schoolId) {
            $school = School::find($schoolId);
            if ($school) {
                $schoolPrefix = str_replace(' ', '_', $school->nama_sekolah);
            }
        } elseif ($batchId) {
            $batch = UniformImportBatch::find($batchId);
            if ($batch) {
                $schoolPrefix = str_replace(' ', '_', $batch->school_name);
            }
        }

        $cleanSchool = preg_replace('/[^A-Za-z0-9_-]/', '_', $schoolPrefix);
        $fileName = "Rekap_Seragam_{$cleanSchool}_" . date('Ymd_His') . ".xlsx";

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
}
