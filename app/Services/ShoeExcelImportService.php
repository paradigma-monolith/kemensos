<?php

namespace App\Services;

use App\Models\School;
use App\Models\ShoeImportBatch;
use App\Models\ShoeRecipient;
use Exception;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ShoeExcelImportService
{
    /**
     * Import and process shoe data from an Excel file path.
     *
     * @param string $filePath Absolute file path to .xlsx
     * @param string $originalName Original uploaded file name
     * @param int|null $userId User ID who performed import
     * @return array Summary of import operation
     * @throws Exception
     */
    public function import(string $filePath, string $originalName, ?int $userId = null, ?string $selectedPic = null): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File Excel tidak ditemukan pada path: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $schoolName = $this->extractSchoolName($spreadsheet, $originalName);
        $metadata = $this->extractMetadata($spreadsheet);

        // Prioritize manually selected PIC, then fallback to metadata from file
        $finalPic = !empty($selectedPic) ? trim($selectedPic) : ($metadata['pic_nama'] ?? null);

        return DB::transaction(function () use ($spreadsheet, $schoolName, $metadata, $originalName, $filePath, $userId, $finalPic) {
            // 1. Create or update school
            $school = School::firstOrCreate(
                ['nama_sekolah' => $schoolName],
                [
                    'kota' => $metadata['kota'] ?? null,
                    'pic_nama' => $finalPic,
                    'kepala_sekolah_nama' => $metadata['kepala_sekolah_nama'] ?? null,
                    'kepala_sekolah_nip' => $metadata['kepala_sekolah_nip'] ?? null,
                    'tanggal_konfirmasi' => $metadata['tanggal_konfirmasi'] ?? null,
                    'status_konfirmasi' => 'terkonfirmasi',
                ]
            );

            // Update existing school metadata
            $updateData = array_filter([
                'kota' => $metadata['kota'] ?? $school->kota,
                'kepala_sekolah_nama' => $metadata['kepala_sekolah_nama'] ?? $school->kepala_sekolah_nama,
                'kepala_sekolah_nip' => $metadata['kepala_sekolah_nip'] ?? $school->kepala_sekolah_nip,
                'tanggal_konfirmasi' => $metadata['tanggal_konfirmasi'] ?? $school->tanggal_konfirmasi,
            ]);
            if ($finalPic) {
                $updateData['pic_nama'] = $finalPic;
            }
            if (!empty($updateData)) {
                $school->update($updateData);
            }

            // Prevent duplicate double-counting on re-import of the same school
            $school->recipients()->delete();
            $school->importBatches()->delete();

            // 2. Create import batch
            $batchNotes = $finalPic ? "PIC: {$finalPic} | " : "";
            $batchNotes .= "Import otomatis pada " . now()->format('Y-m-d H:i:s');

            $batch = ShoeImportBatch::create([
                'school_id' => $school->id,
                'file_name' => $originalName,
                'file_path' => $filePath,
                'imported_by' => $userId,
                'notes' => $batchNotes,
            ]);

            $recipients = [];
            $counts = [
                'siswa' => 0,
                'guru' => 0,
                'tendik' => 0,
                'total' => 0,
            ];
            $warnings = [];

            // 3. Process each sheet dynamically (skip recap / summary sheets)
            foreach ($spreadsheet->getSheetNames() as $sheetIndex => $sheetName) {
                if (preg_match('/^(REKAP|REKAPITULASI|RINGKASAN|SUMMARY|GRAFIK)/i', trim($sheetName))) {
                    continue;
                }
                $sheet = $spreadsheet->getSheet($sheetIndex);
                $sheetRecipients = $this->parseDynamicSheet($sheet, $sheetName, $school->id, $batch->id, $warnings);

                foreach ($sheetRecipients as $rec) {
                    $recipients[] = $rec;
                    $kategori = $rec['kategori'];
                    if (isset($counts[$kategori])) {
                        $counts[$kategori]++;
                    }
                    $counts['total']++;
                }
            }

            // 4. Batch insert recipients for high efficiency
            if (!empty($recipients)) {
                foreach (array_chunk($recipients, 250) as $chunk) {
                    ShoeRecipient::insert($chunk);
                }
            }

            // 5. Update batch totals
            $batch->update([
                'total_siswa' => $counts['siswa'],
                'total_guru' => $counts['guru'],
                'total_tendik' => $counts['tendik'],
                'total_sepatu' => $counts['total'],
            ]);

            return [
                'batch_id' => $batch->id,
                'school_id' => $school->id,
                'school_name' => $school->nama_sekolah,
                'total_sepatu' => $counts['total'],
                'total_siswa' => $counts['siswa'],
                'total_guru' => $counts['guru'],
                'total_tendik' => $counts['tendik'],
                'metadata' => $metadata,
                'warnings' => $warnings,
            ];
        });
    }

    /**
     * Extract school name from sheets or fallback to clean file name.
     */
    protected function extractSchoolName(Spreadsheet $spreadsheet, string $originalName): string
    {
        // 1. Check cells A1, B1, C1, A2, B2 across all sheets
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach (['A1', 'B1', 'C1', 'A2', 'B2', 'A3', 'B3'] as $coord) {
                $val = trim((string)$sheet->getCell($coord)->getValue());
                if (!empty($val) && preg_match('/(SEKOLAH|SRMP|SMP|SD|SMA|SMK|MADRASAH|SR\s*\d|SMPN|SDN|SMAN)/i', $val)) {
                    return $val;
                }
            }
        }

        // 2. Check any non-empty cell in row 1 of the first sheet
        try {
            $firstSheet = $spreadsheet->getSheet(0);
            for ($col = 1; $col <= 5; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $val = trim((string)$firstSheet->getCell("{$colLetter}1")->getValue());
                if (!empty($val) && strlen($val) > 4 && !is_numeric($val)) {
                    return $val;
                }
            }
        } catch (\Throwable $e) {}

        // 3. Fallback: clean filename
        $clean = pathinfo($originalName, PATHINFO_FILENAME);
        $clean = preg_replace('/^(KONFIRMASI(\s+SEPATU)?|\(OK\)|OK)/i', '', $clean);
        $clean = trim($clean, " _-()");

        return !empty($clean) ? $clean : pathinfo($originalName, PATHINFO_FILENAME);
    }

    /**
     * Extract metadata (Kota, PIC, Kepala Sekolah, NIP, Tanggal) from sheets.
     */
    protected function extractMetadata(Spreadsheet $spreadsheet): array
    {
        $meta = [
            'kota' => null,
            'tanggal_konfirmasi' => null,
            'pic_nama' => null,
            'kepala_sekolah_nama' => null,
            'kepala_sekolah_nip' => null,
        ];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $highestRow = min(50, $sheet->getHighestRow());

            for ($r = 10; $r <= $highestRow; $r++) {
                $rowVals = $sheet->rangeToArray("A{$r}:J{$r}")[0];

                foreach ($rowVals as $colIdx => $rawVal) {
                    $val = trim((string)$rawVal);
                    if (empty($val)) continue;

                    // Match date format like "Cimahi, 23 Oktober 2026" or "Bandung, 10 November 2026"
                    if (preg_match('/^([A-Za-z\s]+),\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4})/i', $val, $matches)) {
                        $meta['kota'] = trim($matches[1]);
                        $meta['tanggal_konfirmasi'] = $val;
                    }

                    // Match NIP
                    if (str_contains($val, 'NIP.')) {
                        $meta['kepala_sekolah_nip'] = trim($val);
                        $aboveRow = $r - 1;
                        $aboveColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                        $candidateName = trim((string)$sheet->getCell("{$aboveColLetter}{$aboveRow}")->getValue());
                        if (!empty($candidateName) && !str_contains($candidateName, 'Mengetahui')) {
                            $meta['kepala_sekolah_nama'] = $candidateName;
                        }
                    }

                    // Match PIC Name
                    if (str_contains($val, 'Petugas Ukur') || str_contains($val, 'PIC')) {
                        $aboveRow = $r - 1;
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                        $candidatePic = trim((string)$sheet->getCell("{$colLetter}{$aboveRow}")->getValue());
                        if (!empty($candidatePic)) {
                            $meta['pic_nama'] = $candidatePic;
                        }
                    }
                }
            }

            // Check standard signature cells B24 / D24 if not yet found
            if (empty($meta['pic_nama'])) {
                $b24 = trim((string)$sheet->getCell('B24')->getValue());
                if (!empty($b24) && !str_contains($b24, '(') && !str_contains($b24, 'Mengetahui')) {
                    $meta['pic_nama'] = $b24;
                }
            }
            if (empty($meta['kepala_sekolah_nama'])) {
                $d24 = trim((string)$sheet->getCell('D24')->getValue());
                if (!empty($d24) && !str_contains($d24, '(') && !str_contains($d24, 'Mengetahui')) {
                    $meta['kepala_sekolah_nama'] = $d24;
                }
            }
        }

        return $meta;
    }

    /**
     * Dynamically detect columns from header row.
     */
    protected function detectSheetStructure($sheet): array
    {
        $highestRow = min(15, $sheet->getHighestRow());
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($r = 1; $r <= $highestRow; $r++) {
            $cols = [
                'header_row' => $r,
                'no' => null,
                'nama' => null,
                'jk' => null,
                'jabatan' => null,
                'panjang' => null,
                'ukuran' => null,
            ];

            for ($c = 1; $c <= min(20, $highestColIndex); $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $val = strtolower(trim((string)$sheet->getCell("{$colLetter}{$r}")->getValue()));
                if (empty($val)) continue;

                if ($val === 'no' || $val === 'no.' || str_contains($val, 'nomor urut') || $val === 'nomor') {
                    $cols['no'] = $colLetter;
                } elseif (str_contains($val, 'nama')) {
                    $cols['nama'] = $colLetter;
                } elseif (str_contains($val, 'jenis kelamin') || $val === 'jk' || $val === 'l/p' || $val === 'gender') {
                    $cols['jk'] = $colLetter;
                } elseif (str_contains($val, 'jabatan') || str_contains($val, 'profesi') || str_contains($val, 'kategori')) {
                    $cols['jabatan'] = $colLetter;
                } elseif (str_contains($val, 'panjang') || str_contains($val, 'telapak')) {
                    $cols['panjang'] = $colLetter;
                } elseif (str_contains($val, 'ukuran') || str_contains($val, 'size') || str_contains($val, 'sepatu')) {
                    $cols['ukuran'] = $colLetter;
                }
            }

            if ($cols['nama'] && $cols['ukuran']) {
                return $cols;
            }
        }

        // Generic fallback if headers were unlabelled
        return [
            'header_row' => 4,
            'no' => 'A',
            'nama' => 'B',
            'jk' => 'C',
            'jabatan' => 'D',
            'panjang' => 'E',
            'ukuran' => 'F',
        ];
    }

    /**
     * Parse any sheet dynamically using detected column positions.
     */
    protected function parseDynamicSheet($sheet, string $sheetName, int $schoolId, int $batchId, array &$warnings): array
    {
        $recipients = [];
        $highestRow = $sheet->getHighestRow();
        $cols = $this->detectSheetStructure($sheet);
        $headerRow = $cols['header_row'];

        $normalizedSheetName = strtoupper(trim($sheetName));

        // Determine default gender and category from sheet name
        $defaultGender = str_contains($normalizedSheetName, 'PEREMPUAN') ? 'P' : 'L';
        $defaultCategory = 'siswa';
        if (str_contains($normalizedSheetName, 'GURU')) {
            $defaultCategory = 'guru';
        } elseif (str_contains($normalizedSheetName, 'TENDIK')) {
            $defaultCategory = 'tendik';
        }

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $namaCol = $cols['nama'] ?: 'B';
            $nama = trim((string)$sheet->getCell("{$namaCol}{$r}")->getValue());

            if (empty($nama) || is_numeric($nama) || str_starts_with($nama, '=') || $this->isFooterRow($nama)) {
                continue;
            }

            // Extract Number
            $noCol = $cols['no'] ?: 'A';
            $no = (int)$sheet->getCell("{$noCol}{$r}")->getValue();

            // Extract Gender
            $jk = $defaultGender;
            if ($cols['jk']) {
                $rawJk = strtoupper(trim((string)$sheet->getCell("{$cols['jk']}{$r}")->getValue()));
                if ($rawJk === 'L' || $rawJk === 'P') {
                    $jk = $rawJk;
                }
            }

            // Extract Jabatan & Kategori
            $jabatan = '';
            $kategori = $defaultCategory;
            if ($cols['jabatan']) {
                $rawJabatan = trim((string)$sheet->getCell("{$cols['jabatan']}{$r}")->getValue());
                if (!empty($rawJabatan) && !str_starts_with($rawJabatan, '=')) {
                    $jabatan = $rawJabatan;
                    $lowerJabatan = strtolower($rawJabatan);
                    if (str_contains($lowerJabatan, 'tendik') || str_contains($lowerJabatan, 'tata usaha') || str_contains($lowerJabatan, 'staf')) {
                        $kategori = 'tendik';
                    } elseif (str_contains($lowerJabatan, 'guru') || str_contains($lowerJabatan, 'pengajar')) {
                        $kategori = 'guru';
                    } elseif (str_contains($lowerJabatan, 'siswa') || str_contains($lowerJabatan, 'murid')) {
                        $kategori = 'siswa';
                    }
                }
            }
            if (empty($jabatan)) {
                $jabatan = ucfirst($kategori);
            }

            // Extract Foot Length
            $panjangKaki = null;
            if ($cols['panjang']) {
                $rawPanjang = trim((string)$sheet->getCell("{$cols['panjang']}{$r}")->getValue());
                $panjangKaki = $this->normalizeFootLength($rawPanjang, $nama, $warnings);
            }

            // Extract Shoe Size
            $ukuran = 0;
            if ($cols['ukuran']) {
                $rawUkuran = trim((string)$sheet->getCell("{$cols['ukuran']}{$r}")->getValue());
                $ukuran = $this->normalizeShoeSize($rawUkuran, $nama, $warnings);
            }

            // If shoe size is not found in designated column, search row cells for valid size number
            if ($ukuran === 0) {
                foreach (['D', 'E', 'F', 'G'] as $tryCol) {
                    $val = (int)$sheet->getCell("{$tryCol}{$r}")->getValue();
                    if ($val >= 25 && $val <= 55) {
                        $ukuran = $val;
                        break;
                    }
                }
            }

            if ($ukuran > 0) {
                $recipients[] = [
                    'school_id' => $schoolId,
                    'batch_id' => $batchId,
                    'kategori' => $kategori,
                    'nomor_urut' => $no > 0 ? $no : (count($recipients) + 1),
                    'nama_lengkap' => $nama,
                    'jenis_kelamin' => $jk,
                    'jabatan' => $jabatan,
                    'panjang_kaki' => $panjangKaki,
                    'ukuran_sepatu' => $ukuran,
                    'sheet_source' => $sheetName,
                    'catatan' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $recipients;
    }

    /**
     * Detect footer rows that should not be treated as people.
     */
    protected function isFooterRow(string $text): bool
    {
        $lower = strtolower($text);
        $footerKeywords = [
            'mengetahui',
            'petugas ukur',
            'kepala sekolah',
            'catatan',
            'pastikan data',
            'oktober',
            'november',
            'desember',
            'januari',
            'februari',
            'maret',
            'nip.',
            'tanda tangan',
            'total',
            'jumlah',
            'rekap',
            'grand total',
        ];

        foreach ($footerKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize foot length (handles millimeter inputs like 300 mm -> 30.0 cm).
     */
    protected function normalizeFootLength(string $raw, string $nama, array &$warnings): ?float
    {
        if (empty($raw)) {
            return null;
        }

        $clean = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $raw));
        $val = (float)$clean;

        if ($val <= 0) {
            return null;
        }

        // If user entered millimeter (e.g. 245 or 300 mm instead of 24.5 cm or 30 cm)
        if ($val > 100) {
            $converted = round($val / 10, 1);
            $warnings[] = "Panjang kaki untuk {$nama} bernilai {$val} mm dikonversi otomatis menjadi {$converted} cm.";
            return $converted;
        }

        return round($val, 2);
    }

    /**
     * Normalize shoe size integer.
     */
    protected function normalizeShoeSize(string $raw, string $nama, array &$warnings): int
    {
        $clean = preg_replace('/[^0-9]/', '', $raw);
        $val = (int)$clean;

        if ($val < 25 || $val > 55) {
            if ($val > 0) {
                $warnings[] = "Ukuran sepatu {$val} untuk {$nama} berada di luar standar ukuran umum (30-50).";
            }
        }

        return $val;
    }
}
