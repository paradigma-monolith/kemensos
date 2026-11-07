<?php

namespace App\Services;

use App\Models\GuruTendikBatch;
use App\Models\GuruTendikRecord;
use App\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuruTendikExcelService
{
    /**
     * Canonical schema definition for FORMAT REKAP TOTAL REVISI.xlsx
     */
    public const IMPORT_SCHEMA = [
        'file_name' => 'FORMAT REKAP TOTAL REVISI.xlsx',
        'sheets' => [
            [
                'sheet_index' => 0,
                'sheet_name' => 'GURU,TENDIK (LAKI LAKI)',
                'header_row' => 3,
                'start_data_row' => 4,
                'default_values' => [
                    'jenis_kelamin' => 'Laki-laki',
                ],
                'columns' => [
                    [
                        'column_letter' => 'A',
                        'column_index' => 0,
                        'header_name' => 'No',
                        'field_name' => 'no',
                        'data_type' => 'integer',
                        'required' => false,
                    ],
                    [
                        'column_letter' => 'B',
                        'column_index' => 1,
                        'header_name' => 'Nama Lengkap',
                        'field_name' => 'nama_lengkap',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                    [
                        'column_letter' => 'C',
                        'column_index' => 2,
                        'header_name' => 'Jenis Kelamin',
                        'field_name' => 'jenis_kelamin',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                    [
                        'column_letter' => 'D',
                        'column_index' => 3,
                        'header_name' => 'Status',
                        'field_name' => 'status',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                    [
                        'column_letter' => 'E',
                        'column_index' => 4,
                        'header_name' => 'Jabatan',
                        'field_name' => 'jabatan',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                    [
                        'column_letter' => 'F',
                        'column_index' => 5,
                        'header_name' => 'Sekolah',
                        'field_name' => 'sekolah',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                ],
            ],
            [
                'sheet_index' => 1,
                'sheet_name' => 'GURU,TENDIK (PEREMPUAN)',
                'header_row' => 3,
                'start_data_row' => 4,
                'default_values' => [
                    'jenis_kelamin' => 'Perempuan',
                ],
                'columns' => [
                    [
                        'column_letter' => 'A',
                        'column_index' => 0,
                        'header_name' => 'No',
                        'field_name' => 'no',
                        'data_type' => 'integer',
                        'required' => false,
                    ],
                    [
                        'column_letter' => 'B',
                        'column_index' => 1,
                        'header_name' => 'Nama Lengkap',
                        'field_name' => 'nama_lengkap',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                    [
                        'column_letter' => 'C',
                        'column_index' => 2,
                        'header_name' => 'Jenis Kelamin',
                        'field_name' => 'jenis_kelamin',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                    [
                        'column_letter' => 'D',
                        'column_index' => 3,
                        'header_name' => 'Status',
                        'field_name' => 'status',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                    [
                        'column_letter' => 'E',
                        'column_index' => 4,
                        'header_name' => 'Jabatan',
                        'field_name' => 'jabatan',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                    [
                        'column_letter' => 'F',
                        'column_index' => 5,
                        'header_name' => 'Sekolah',
                        'field_name' => 'sekolah',
                        'data_type' => 'string',
                        'required' => true,
                    ],
                ],
            ],
        ],
    ];

    /**
     * Import and process Guru & Tendik data from an Excel file path.
     *
     * @param string $filePath Absolute path to the Excel file
     * @param string $originalName Original uploaded file name
     * @param int|null $userId Authenticated user ID
     * @param string|null $selectedPic Optional PIC name
     * @return array Summary of import
     * @throws Exception
     */
    public function import(string $filePath, string $originalName, ?int $userId = null, ?string $selectedPic = null): array
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        if (!file_exists($filePath)) {
            throw new Exception("Berkas Excel tidak ditemukan pada path: {$filePath}");
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }
        $spreadsheet = $reader->load($filePath);
        $finalPic = School::normalizePicName($selectedPic);

        return DB::transaction(function () use ($spreadsheet, $filePath, $originalName, $userId, $finalPic) {
            $batchNotes = $finalPic ? "PIC: {$finalPic} | " : '';
            $batchNotes .= 'Import pada ' . now()->format('Y-m-d H:i:s');

            // Replace existing batch if re-uploading the exact same filename
            $existingBatch = GuruTendikBatch::where('file_name', $originalName)
                ->orWhere('file_name', basename($originalName))
                ->first();

            if ($existingBatch) {
                $existingBatch->records()->delete();
                $batch = $existingBatch;
                $batch->update([
                    'file_path' => $filePath,
                    'pic_nama' => $finalPic ?: $batch->pic_nama,
                    'imported_by' => $userId,
                    'notes' => $batchNotes,
                ]);
            } else {
                $batch = GuruTendikBatch::create([
                    'file_name' => $originalName,
                    'file_path' => $filePath,
                    'pic_nama' => $finalPic,
                    'imported_by' => $userId,
                    'notes' => $batchNotes,
                ]);
            }

            $records = [];
            $warnings = [];
            $totalLaki = 0;
            $totalPerempuan = 0;
            $uniqueSchools = [];

            $sheetNames = $spreadsheet->getSheetNames();
            $hasMultipleSheets = count($sheetNames) > 1;

            foreach ($sheetNames as $sheetIndex => $sheetName) {
                $trimmedTitle = trim($sheetName);

                // Skip pure summary sheets if workbook has multiple sheets
                if ($hasMultipleSheets && preg_match('/^(REKAP|REKAPITULASI|RINGKASAN|SUMMARY|GRAFIK|REKAP\s+PER\s+SEKOLAH)$/i', $trimmedTitle)) {
                    continue;
                }

                $sheetConfig = $this->resolveSheetConfig($sheetIndex, $trimmedTitle);
                $worksheet = $spreadsheet->getSheet($sheetIndex);

                $parsedRows = $this->parseSheet(
                    $worksheet,
                    $trimmedTitle,
                    $sheetConfig,
                    $batch->id,
                    $finalPic,
                    $warnings
                );

                foreach ($parsedRows as $row) {
                    $records[] = $row;
                    if ($row['jenis_kelamin'] === 'Perempuan') {
                        $totalPerempuan++;
                    } else {
                        $totalLaki++;
                    }

                    $schKey = mb_strtolower(trim($row['sekolah']));
                    if ($schKey !== '' && $schKey !== '-') {
                        $uniqueSchools[$schKey] = $row['sekolah'];
                    }
                }
            }

            if (!empty($records)) {
                foreach (array_chunk($records, 100) as $chunk) {
                    GuruTendikRecord::insert($chunk);
                }
            }

            $batch->update([
                'total_laki' => $totalLaki,
                'total_perempuan' => $totalPerempuan,
                'total_data' => count($records),
                'total_sekolah' => count($uniqueSchools),
            ]);

            return [
                'batch_id' => $batch->id,
                'file_name' => $originalName,
                'total_data' => count($records),
                'total_laki' => $totalLaki,
                'total_perempuan' => $totalPerempuan,
                'total_sekolah' => count($uniqueSchools),
                'warnings' => $warnings,
            ];
        });
    }

    /**
     * Resolve sheet configuration based on IMPORT_SCHEMA.
     */
    public function resolveSheetConfig(int $sheetIndex, string $sheetName): array
    {
        $upperName = strtoupper(trim($sheetName));

        // 1. Check gender keyword in sheet name
        if (str_contains($upperName, 'PEREMPUAN') || str_contains($upperName, 'WANITA') || preg_match('/\b(P|PR)\b/', $upperName)) {
            return self::IMPORT_SCHEMA['sheets'][1];
        }

        if (str_contains($upperName, 'LAKI') || str_contains($upperName, 'PRIA') || preg_match('/\b(L|LK)\b/', $upperName)) {
            return self::IMPORT_SCHEMA['sheets'][0];
        }

        // 2. Fallback to sheet_index in schema
        foreach (self::IMPORT_SCHEMA['sheets'] as $schemaSheet) {
            if ($schemaSheet['sheet_index'] === $sheetIndex) {
                return $schemaSheet;
            }
        }

        return self::IMPORT_SCHEMA['sheets'][0];
    }

    /**
     * Parse a single worksheet according to the schema definition (header_row: 3, start_data_row: 4).
     */
    protected function parseSheet(
        Worksheet $sheet,
        string $sheetName,
        array $sheetConfig,
        int $batchId,
        ?string $picNama,
        array &$warnings
    ): array {
        $highestRowB = $sheet->getHighestDataRow('B');
        $highestRowF = $sheet->getHighestDataRow('F');
        $highestRow = max($highestRowB, $highestRowF, min($sheet->getHighestDataRow(), 50000));
        $defaultGender = $sheetConfig['default_values']['jenis_kelamin'] ?? 'Laki-laki';
        $headerRow = (int) ($sheetConfig['header_row'] ?? 3);
        $startDataRow = (int) ($sheetConfig['start_data_row'] ?? 4);

        if ($highestRow < 1) {
            return [];
        }

        // Default column index mapping from schema (A=0, B=1, C=2, D=3, E=4, F=5)
        $colMap = [
            'no' => 0,
            'nama_lengkap' => 1,
            'jenis_kelamin' => 2,
            'status' => 3,
            'jabatan' => 4,
            'sekolah' => 5,
        ];

        // Detect header row if present around row 1..10 to support both strict Row 3 and slightly shifted files
        $detectedHeaderRow = $this->detectHeaderRow($sheet, min(10, $highestRow), $colMap);
        if ($detectedHeaderRow !== null) {
            $headerRow = $detectedHeaderRow['row'];
            $startDataRow = $headerRow + 1;
            $colMap = array_merge($colMap, $detectedHeaderRow['map']);
        }

        if ($highestRow < $startDataRow) {
            return [];
        }

        $rowsData = $sheet->rangeToArray(
            "A{$startDataRow}:G{$highestRow}",
            null,
            false,
            false,
            false
        );

        $results = [];
        $now = now();
        $autoNo = 1;
        $emptyStreak = 0;

        foreach ($rowsData as $offset => $rowCells) {
            $currentExcelRow = $startDataRow + $offset;

            $rawNo = $rowCells[$colMap['no']] ?? null;
            $rawNama = trim((string) ($rowCells[$colMap['nama_lengkap']] ?? ''));
            $rawGender = trim((string) ($rowCells[$colMap['jenis_kelamin']] ?? ''));
            $rawStatus = trim((string) ($rowCells[$colMap['status']] ?? ''));
            $rawJabatan = trim((string) ($rowCells[$colMap['jabatan']] ?? ''));
            $rawSekolah = trim((string) ($rowCells[$colMap['sekolah']] ?? ''));

            // Skip completely blank rows and stop early if trailing empty block is reached
            if ($rawNama === '' && $rawStatus === '' && $rawJabatan === '' && $rawSekolah === '') {
                $emptyStreak++;
                if ($emptyStreak > 100) {
                    break;
                }
                continue;
            }
            $emptyStreak = 0;

            // Skip repeated header rows or footer/summary/signature rows
            if ($rawNama === '' || $this->isIgnoredRow($rawNama)) {
                continue;
            }

            // Parse 'no' (integer, required: false)
            $noVal = is_numeric($rawNo) ? (int) $rawNo : $autoNo;
            $autoNo++;

            // Normalize 'jenis_kelamin' using sheet default_values when empty
            $jenisKelamin = GuruTendikRecord::normalizeGender($rawGender, $defaultGender);

            // Handle required fields (status, jabatan, sekolah)
            if ($rawStatus === '') {
                $rawStatus = '-';
                $warnings[] = "Sheet [{$sheetName}] Baris {$currentExcelRow} ({$rawNama}): Kolom Status kosong, diisi '-'.";
            }
            if ($rawJabatan === '') {
                $rawJabatan = 'Guru / Tendik';
                $warnings[] = "Sheet [{$sheetName}] Baris {$currentExcelRow} ({$rawNama}): Kolom Jabatan kosong, diisi default.";
            }
            if ($rawSekolah === '') {
                $rawSekolah = '-';
                $warnings[] = "Sheet [{$sheetName}] Baris {$currentExcelRow} ({$rawNama}): Kolom Sekolah kosong, diisi '-'.";
            }

            $results[] = [
                'batch_id' => $batchId,
                'no' => $noVal,
                'nama_lengkap' => $rawNama,
                'jenis_kelamin' => $jenisKelamin,
                'status' => $rawStatus,
                'jabatan' => $rawJabatan,
                'sekolah' => $rawSekolah,
                'sheet_name' => $sheetName,
                'pic_nama' => $picNama,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $results;
    }

    /**
     * Detect header row (prioritizing row 3 as specified in schema).
     */
    protected function detectHeaderRow(Worksheet $sheet, int $maxRow, array $defaultMap): ?array
    {
        $candidates = array_unique(array_merge([3], range(1, $maxRow)));

        foreach ($candidates as $r) {
            if ($r > $maxRow) {
                continue;
            }
            $cells = $sheet->rangeToArray("A{$r}:J{$r}", null, true, false, false)[0] ?? [];
            $map = [];
            $matchedCount = 0;

            foreach ($cells as $idx => $val) {
                $clean = strtolower(trim(preg_replace('/\s+/', ' ', (string) $val)));
                if ($clean === '') {
                    continue;
                }

                if ($clean === 'no' || $clean === 'no.' || $clean === 'nomor') {
                    $map['no'] = $idx;
                } elseif (str_contains($clean, 'nama lengkap') || $clean === 'nama') {
                    $map['nama_lengkap'] = $idx;
                    $matchedCount++;
                } elseif (str_contains($clean, 'jenis kelamin') || $clean === 'jk' || $clean === 'gender' || $clean === 'l/p') {
                    $map['jenis_kelamin'] = $idx;
                    $matchedCount++;
                } elseif ($clean === 'status' || str_contains($clean, 'status kepegawaian')) {
                    $map['status'] = $idx;
                    $matchedCount++;
                } elseif (str_contains($clean, 'jabatan') || str_contains($clean, 'posisi')) {
                    $map['jabatan'] = $idx;
                    $matchedCount++;
                } elseif (str_contains($clean, 'sekolah') || str_contains($clean, 'satuan pendidikan') || str_contains($clean, 'instansi')) {
                    $map['sekolah'] = $idx;
                    $matchedCount++;
                }
            }

            if ($matchedCount >= 2) {
                return [
                    'row' => $r,
                    'map' => array_merge($defaultMap, $map),
                ];
            }
        }

        return null;
    }

    /**
     * Check if a row is a repeated header, total row, or signature block.
     */
    protected function isIgnoredRow(string $nama): bool
    {
        $upper = strtoupper(trim($nama));
        if (in_array($upper, ['NAMA', 'NAMA LENGKAP', 'NO', 'JENIS KELAMIN', 'STATUS', 'JABATAN', 'SEKOLAH'], true)) {
            return true;
        }
        if (preg_match('/^(TOTAL|JUMLAH|SUB\s*TOTAL|GRAND\s*TOTAL|REKAP|REKAPITULASI|MENGETAHUI|MENGESAHKAN|CATATAN)\b/i', $upper)) {
            return true;
        }
        if (preg_match('/^(\.{3,}|_{3,}|-{3,})$/', $upper)) {
            return true;
        }
        return false;
    }

    /**
     * Generate a blank / sample template Excel matching FORMAT REKAP TOTAL REVISI.xlsx schema.
     */
    public function generateTemplateExcel(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        foreach (self::IMPORT_SCHEMA['sheets'] as $idx => $sheetSpec) {
            $sheet = $idx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($sheetSpec['sheet_name']);
            $sheet->setShowGridlines(true);

            // Title Row 1
            $sheet->mergeCells('A1:F1');
            $sheet->setCellValue('A1', 'FORMAT REKAP TOTAL REVISI — ' . $sheetSpec['sheet_name']);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F172A');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Header Row 3
            $headerRow = $sheetSpec['header_row'];
            foreach ($sheetSpec['columns'] as $col) {
                $cellCoord = $col['column_letter'] . $headerRow;
                $sheet->setCellValue($cellCoord, $col['header_name']);
            }
            $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray($headerStyle);
            $sheet->getRowDimension($headerRow)->setRowHeight(24);

            // Sample Data Row 4
            $startRow = $sheetSpec['start_data_row'];
            $defaultGender = $sheetSpec['default_values']['jenis_kelamin'];
            $sampleName = $defaultGender === 'Laki-laki' ? 'Ahmad Fauzi, S.Pd.' : 'Siti Rahmawati, S.Pd.';

            $sheet->fromArray([
                [1, $sampleName, $defaultGender, 'PNS', 'Guru Kelas', 'SRMP 8 CIMAHI'],
            ], null, "A{$startRow}");

            $sheet->getStyle("A{$startRow}:F{$startRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $sheet->getStyle("A{$startRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$startRow}:D{$startRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $widths = ['A' => 8, 'B' => 34, 'C' => 18, 'D' => 18, 'E' => 26, 'F' => 32];
            foreach ($widths as $colLet => $w) {
                $sheet->getColumnDimension($colLet)->setWidth($w);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    /**
     * Export Guru & Tendik database records to Excel matching FORMAT REKAP TOTAL REVISI.xlsx structure
     * plus a Summary Per School sheet, with optional duplicate removal (same Nama Lengkap & Sekolah).
     */
    public function generateExportExcel(array $filters = [], bool $deduplicate = false): Spreadsheet
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '300');

        $query = GuruTendikRecord::query()
            ->when(!empty($filters['sekolah']), fn($q) => $q->where('sekolah', $filters['sekolah']))
            ->when(!empty($filters['jenis_kelamin']), fn($q) => $q->where('jenis_kelamin', $filters['jenis_kelamin']))
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['jabatan']), fn($q) => $q->where('jabatan', $filters['jabatan']))
            ->when(!empty($filters['pic']), fn($q) => $q->where('pic_nama', $filters['pic']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $s = $filters['search'];
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama_lengkap', 'like', "%{$s}%")
                        ->orWhere('status', 'like', "%{$s}%")
                        ->orWhere('jabatan', 'like', "%{$s}%")
                        ->orWhere('sekolah', 'like', "%{$s}%");
                });
            })
            ->orderBy('sekolah')
            ->orderBy('no')
            ->orderBy('id');

        $records = $query->get();

        // Remove duplicates by (normalized sekolah + normalized nama_lengkap) if requested
        if ($deduplicate) {
            $seenKeys = [];
            $records = $records->filter(function ($r) use (&$seenKeys) {
                $normSchool = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $r->sekolah)));
                $normName = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $r->nama_lengkap)));
                $key = $normSchool . '|' . $normName;
                if (isset($seenKeys[$key])) {
                    return false;
                }
                $seenKeys[$key] = true;
                return true;
            })->values();
        }

        $lakiRecords = $records->filter(fn($r) => $r->jenis_kelamin === 'Laki-laki')->values();
        $perempuanRecords = $records->filter(fn($r) => $r->jenis_kelamin === 'Perempuan')->values();

        $spreadsheet = new Spreadsheet();

        $headerStyleBlue = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $headerStylePink = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BE185D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $headerStyleIndigo = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $totalRowStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        ];

        // Sheet 0: GURU,TENDIK (LAKI LAKI) — Header Row 3, Start Data Row 4
        $sheetLaki = $spreadsheet->getActiveSheet();
        $this->populateGenderSheet(
            $sheetLaki,
            'GURU,TENDIK (LAKI LAKI)',
            $lakiRecords,
            $headerStyleBlue
        );

        // Sheet 1: GURU,TENDIK (PEREMPUAN) — Header Row 3, Start Data Row 4
        $sheetPerempuan = $spreadsheet->createSheet();
        $this->populateGenderSheet(
            $sheetPerempuan,
            'GURU,TENDIK (PEREMPUAN)',
            $perempuanRecords,
            $headerStylePink
        );

        // Sheet 2: REKAP PER SEKOLAH
        $sheetRekap = $spreadsheet->createSheet();
        $sheetRekap->setTitle('REKAP PER SEKOLAH');
        $sheetRekap->setShowGridlines(true);

        $sheetRekap->mergeCells('A1:E1');
        $sheetRekap->setCellValue('A1', 'REKAPITULASI DATA GURU & TENDIK PER SEKOLAH');
        $sheetRekap->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $sheetRekap->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheetRekap->fromArray([
            ['No', 'Sekolah', 'Guru/Tendik Laki-laki', 'Guru/Tendik Perempuan', 'Total Guru & Tendik']
        ], null, 'A3');
        $sheetRekap->getStyle('A3:E3')->applyFromArray($headerStyleIndigo);
        $sheetRekap->getRowDimension(3)->setRowHeight(25);

        $bySchool = [];
        foreach ($records as $r) {
            $sch = $r->sekolah ?: '-';
            if (!isset($bySchool[$sch])) {
                $bySchool[$sch] = ['laki' => 0, 'perempuan' => 0, 'total' => 0];
            }
            if ($r->jenis_kelamin === 'Perempuan') {
                $bySchool[$sch]['perempuan']++;
            } else {
                $bySchool[$sch]['laki']++;
            }
            $bySchool[$sch]['total']++;
        }
        ksort($bySchool);

        $rekapRows = [];
        $noS = 1;
        $sumL = 0;
        $sumP = 0;
        $sumAll = 0;

        foreach ($bySchool as $schName => $st) {
            $rekapRows[] = [
                $noS++,
                $this->sanitizeCellValue($schName),
                $st['laki'],
                $st['perempuan'],
                $st['total'],
            ];
            $sumL += $st['laki'];
            $sumP += $st['perempuan'];
            $sumAll += $st['total'];
        }

        $totRowIdx = 4;
        if (!empty($rekapRows)) {
            $sheetRekap->fromArray($rekapRows, null, 'A4');
            $endR = 3 + count($rekapRows);
            $sheetRekap->getStyle("A4:A{$endR}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetRekap->getStyle("C4:E{$endR}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetRekap->getStyle("A4:E{$endR}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $totRowIdx = $endR + 1;
        }

        $sheetRekap->mergeCells("A{$totRowIdx}:B{$totRowIdx}");
        $sheetRekap->setCellValue("A{$totRowIdx}", 'TOTAL KESELURUHAN');
        $sheetRekap->setCellValue("C{$totRowIdx}", $sumL);
        $sheetRekap->setCellValue("D{$totRowIdx}", $sumP);
        $sheetRekap->setCellValue("E{$totRowIdx}", $sumAll);
        $sheetRekap->getStyle("A{$totRowIdx}:E{$totRowIdx}")->applyFromArray($totalRowStyle);
        $sheetRekap->getRowDimension($totRowIdx)->setRowHeight(24);

        foreach (['A' => 8, 'B' => 38, 'C' => 24, 'D' => 24, 'E' => 24] as $col => $w) {
            $sheetRekap->getColumnDimension($col)->setWidth($w);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    /**
     * Populate a gender worksheet following the exact schema (header_row: 3, start_data_row: 4).
     */
    protected function populateGenderSheet(Worksheet $sheet, string $title, $records, array $headerStyle): void
    {
        $sheet->setTitle($title);
        $sheet->setShowGridlines(true);

        // Title at Row 1
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'DATA REKAPITULASI ' . $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F172A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header at Row 3 (Columns A-F)
        $headers = ['No', 'Nama Lengkap', 'Jenis Kelamin', 'Status', 'Jabatan', 'Sekolah'];
        $sheet->fromArray([$headers], null, 'A3');
        $sheet->getStyle('A3:F3')->applyFromArray($headerStyle);
        $sheet->getRowDimension(3)->setRowHeight(25);

        // Data starting at Row 4
        $dataRows = [];
        $no = 1;
        foreach ($records as $r) {
            $dataRows[] = [
                $no++,
                $this->sanitizeCellValue($r->nama_lengkap),
                $this->sanitizeCellValue($r->jenis_kelamin),
                $this->sanitizeCellValue($r->status),
                $this->sanitizeCellValue($r->jabatan),
                $this->sanitizeCellValue($r->sekolah),
            ];
        }

        if (!empty($dataRows)) {
            $sheet->fromArray($dataRows, null, 'A4');
            $endRow = 3 + count($dataRows);
            $sheet->getStyle("A4:A{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C4:D{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if (count($dataRows) <= 2500) {
                $sheet->getStyle("A4:F{$endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            } else {
                $sheet->getStyle("A4:F{$endRow}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            }
        }

        foreach (['A' => 8, 'B' => 34, 'C' => 18, 'D' => 18, 'E' => 26, 'F' => 34] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }

    /**
     * Sanitize cell value against formula injection.
     */
    protected function sanitizeCellValue($value): string
    {
        $str = (string) ($value ?? '');
        if (isset($str[0]) && in_array($str[0], ['=', '+', '-', '@'], true)) {
            return "'" . $str;
        }
        return $str;
    }
}

