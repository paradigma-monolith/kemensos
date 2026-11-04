<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\UniformImportBatch;
use App\Models\UniformRecipient;
use App\Services\UniformExcelImportService;
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
        protected UniformExcelImportService $uniformService
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

        // Jenjang stats for quick cards (SRD, SRMP, SRMA)
        $sheetStats = [
            'srd' => [
                'total' => (int)UniformImportBatch::where(function ($q) {
                    $q->where('school_name', 'like', '%SRD%')->orWhere('file_name', 'like', '%SRD%');
                })->sum('total_rows'),
                'pria' => UniformRecipient::whereHas('batch', fn($q) => $q->where('school_name', 'like', '%SRD%')->orWhere('file_name', 'like', '%SRD%'))->where('jenis_kelamin', 'L')->count(),
                'wanita' => UniformRecipient::whereHas('batch', fn($q) => $q->where('school_name', 'like', '%SRD%')->orWhere('file_name', 'like', '%SRD%'))->where('jenis_kelamin', 'P')->count(),
            ],
            'srmp' => [
                'total' => (int)UniformImportBatch::where(function ($q) {
                    $q->where('school_name', 'like', '%SRMP%')->orWhere('file_name', 'like', '%SRMP%');
                })->sum('total_rows'),
                'pria' => UniformRecipient::whereHas('batch', fn($q) => $q->where('school_name', 'like', '%SRMP%')->orWhere('file_name', 'like', '%SRMP%'))->where('jenis_kelamin', 'L')->count(),
                'wanita' => UniformRecipient::whereHas('batch', fn($q) => $q->where('school_name', 'like', '%SRMP%')->orWhere('file_name', 'like', '%SRMP%'))->where('jenis_kelamin', 'P')->count(),
            ],
            'srma' => [
                'total' => (int)UniformImportBatch::where(function ($q) {
                    $q->where('school_name', 'like', '%SRMA%')->orWhere('file_name', 'like', '%SRMA%');
                })->sum('total_rows'),
                'pria' => UniformRecipient::whereHas('batch', fn($q) => $q->where('school_name', 'like', '%SRMA%')->orWhere('file_name', 'like', '%SRMA%'))->where('jenis_kelamin', 'L')->count(),
                'wanita' => UniformRecipient::whereHas('batch', fn($q) => $q->where('school_name', 'like', '%SRMA%')->orWhere('file_name', 'like', '%SRMA%'))->where('jenis_kelamin', 'P')->count(),
            ],
        ];

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
            ],
            'filters' => [
                'school_id' => $schoolId ? (int)$schoolId : null,
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

        $batchId = $request->query('batch_id');
        $schoolId = $request->query('school_id');

        $batch = null;
        if ($batchId) {
            $batch = UniformImportBatch::find($batchId);
        } elseif ($schoolId) {
            $batch = UniformImportBatch::where('school_id', $schoolId)->latest()->first();
        } else {
            $batch = UniformImportBatch::latest()->first();
        }

        $recap = $batch?->recap_data['recapitulation'] ?? [];
        $schoolName = $batch?->school_name ?? 'Semua_Sekolah';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // remove default sheet

        $headerStyleIndigo = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $headerStyleAtasan = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $headerStyleBawahan = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $schema = UniformExcelImportService::SCHEMA;

        foreach ($schema as $sheetKey => $conf) {
            $sheetData = $recap[$sheetKey]['data'] ?? [];
            if (empty($sheetData) && count($spreadsheet->getAllSheets()) > 0) {
                continue;
            }

            $worksheet = $spreadsheet->createSheet();
            $worksheet->setTitle(substr($sheetKey, 0, 31));

            // Write row 1 group headers
            $worksheet->setCellValue('A1', 'INFORMASI DASAR');
            $worksheet->mergeCells('A1:D1');
            $worksheet->getStyle('A1:D1')->applyFromArray($headerStyleIndigo);

            $worksheet->setCellValue('E1', 'ATASAN');
            $worksheet->mergeCells('E1:F1');
            $worksheet->getStyle('E1:F1')->applyFromArray($headerStyleAtasan);

            $lastBawahanCol = ($sheetKey === 'SATPAM') ? 'J1' : 'I1';
            $worksheet->setCellValue('G1', 'BAWAHAN');
            $worksheet->mergeCells("G1:{$lastBawahanCol}");
            $worksheet->getStyle("G1:{$lastBawahanCol}")->applyFromArray($headerStyleBawahan);

            $vendorCol = ($sheetKey === 'SATPAM') ? 'K' : 'J';
            $worksheet->setCellValue("{$vendorCol}1", 'VENDOR');
            $worksheet->getStyle("{$vendorCol}1")->applyFromArray($headerStyleIndigo);

            // Write row 2 column headers
            $colHeaders = [
                'A' => 'No',
                'B' => 'Nama Lengkap',
                'C' => 'Jenis Kelamin',
                'D' => ($sheetKey === 'SISWA') ? 'Jenjang' : 'Status',
                'E' => 'Lingkar Badan (cm)',
                'F' => 'Panjang Badan (cm)',
                'G' => 'Lingkar Pinggang (cm)',
                'H' => 'Panjang Celana (cm)',
                'I' => 'Panjang Pesak (cm)',
            ];
            if ($sheetKey === 'SATPAM') {
                $colHeaders['J'] = 'Sepatu PDH';
                $colHeaders['K'] = 'Konversi Vendor';
            } else {
                $colHeaders['J'] = 'Konversi Vendor';
            }

            foreach ($colHeaders as $c => $lbl) {
                $worksheet->setCellValue("{$c}2", $lbl);
                $worksheet->getStyle("{$c}2")->applyFromArray($headerStyleIndigo);
            }

            // Write data rows starting at row 3
            $rNum = 3;
            foreach ($sheetData as $row) {
                $worksheet->setCellValue("A{$rNum}", $row['no'] ?? ($rNum - 2));
                $worksheet->setCellValue("B{$rNum}", $row['nama_lengkap'] ?? '');
                $worksheet->setCellValue("C{$rNum}", $row['jenis_kelamin'] ?? '');
                $worksheet->setCellValue("D{$rNum}", $row['jenjang'] ?? $row['status'] ?? '');
                $worksheet->setCellValue("E{$rNum}", $row['atasan']['lingkar_badan_cm'] ?? '');
                $worksheet->setCellValue("F{$rNum}", $row['atasan']['panjang_badan_cm'] ?? '');
                $worksheet->setCellValue("G{$rNum}", $row['bawahan']['lingkar_pinggang_cm'] ?? '');
                $worksheet->setCellValue("H{$rNum}", $row['bawahan']['panjang_celana_cm'] ?? '');
                $worksheet->setCellValue("I{$rNum}", $row['bawahan']['panjang_pesak_cm'] ?? '');

                if ($sheetKey === 'SATPAM') {
                    $worksheet->setCellValue("J{$rNum}", $row['bawahan']['ukuran_sepatu_pdh'] ?? '');
                    $worksheet->setCellValue("K{$rNum}", $row['konversi_vendor'] ?? '');
                } else {
                    $worksheet->setCellValue("J{$rNum}", $row['konversi_vendor'] ?? '');
                }
                $rNum++;
            }

            // Auto size columns
            foreach (range('A', $vendorCol) as $c) {
                $worksheet->getColumnDimension($c)->setAutoSize(true);
            }
        }

        if (count($spreadsheet->getAllSheets()) === 0) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('Kosong');
            $sheet->setCellValue('A1', 'Belum ada data ukuran seragam.');
        }

        $cleanSchool = preg_replace('/[^A-Za-z0-9_-]/', '_', $schoolName);
        $fileName = "Rekap_Seragam_{$cleanSchool}_" . date('Ymd_His') . ".xlsx";

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
}
