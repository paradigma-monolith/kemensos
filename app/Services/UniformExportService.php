<?php

namespace App\Services;

use App\Models\School;
use App\Models\UniformImportBatch;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UniformExportService
{
    /**
     * Standard vendor size ordering.
     */
    protected const STANDARD_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'];

    /**
     * Generate an Excel spreadsheet with Recap Matrix, Category Breakdown,
     * Jenjang SR Matrix, and Detailed Recipient Lists.
     *
     * Modeled strictly after ShoeExportService for typography, layout,
     * aesthetics, and data clarity.
     *
     * @param int|null $batchId Filter by specific import batch
     * @param int|null $schoolId Filter by specific school
     * @return Spreadsheet
     */
    public function generateRecapExcel(?int $batchId = null, ?int $schoolId = null): Spreadsheet
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Remove default blank sheet
        $usedSheetTitles = [];

        // 1. Resolve Batch & Metadata
        $batch = null;
        if ($batchId) {
            $batch = UniformImportBatch::find($batchId);
        } elseif ($schoolId) {
            $batch = UniformImportBatch::where('school_id', $schoolId)->latest()->first();
        } else {
            $batch = UniformImportBatch::latest()->first();
        }

        $sessionRecap = session('uniform_recap');
        $recap = $batch?->recap_data['recapitulation'] ?? $sessionRecap['recapitulation'] ?? [];
        $rawSchoolName = $batch?->school_name ?? $sessionRecap['school_name'] ?? 'SEKOLAH RAKYAT';
        $scopeName = strtoupper($rawSchoolName);
        $picNama = $batch?->pic_nama ?? $sessionRecap['pic_nama'] ?? 'Belum Ditentukan';

        // Retrieve linked school information if available
        $school = null;
        if ($batch?->school_id) {
            $school = School::find($batch->school_id);
        } elseif ($schoolId) {
            $school = School::find($schoolId);
        }
        $kota = $school?->kota ?? 'Indonesia';
        $ksName = $school?->kepala_sekolah_nama ?? '(............................................)';
        if ($picNama === 'Belum Ditentukan' && $school?->pic_nama) {
            $picNama = $school->pic_nama;
        }

        // 2. Shared Palettes & Typography (Matching ShoeExportService)
        $headerStyleBlue = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']], // Blue 700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $headerStyleIndigo = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']], // Indigo 700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $headerStyleEmerald = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']], // Emerald 700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $subHeaderDark = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']], // Slate 800
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $totalRowStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']], // Slate 200
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        ];

        // 3. Pre-aggregate Statistics
        $schema = UniformExcelImportService::SCHEMA;
        $statsBySize = [];
        $statsBySheet = [];
        $satpamPdhDist = [];
        $statsByJenjang = [
            'SRD' => ['l' => 0, 'p' => 0, 'total' => 0, 'sizes' => []],
            'SRMP' => ['l' => 0, 'p' => 0, 'total' => 0, 'sizes' => []],
            'SRMA' => ['l' => 0, 'p' => 0, 'total' => 0, 'sizes' => []],
            'STAFF' => ['l' => 0, 'p' => 0, 'total' => 0, 'sizes' => []],
        ];

        $allRecipientsMaster = [];
        $overallTotal = 0;
        $overallSiswaL = 0; $overallSiswaP = 0;
        $overallStaffL = 0; $overallStaffP = 0;

        foreach ($schema as $sheetKey => $conf) {
            $sheetData = $recap[$sheetKey]['data'] ?? [];
            $sL = 0;
            $sP = 0;
            $isSiswa = ($sheetKey === 'SISWA');

            foreach ($sheetData as $row) {
                $jk = strtoupper(trim((string)($row['jenis_kelamin'] ?? '')));
                $isL = ($jk === 'L');

                if ($isL) {
                    $sL++;
                    if ($isSiswa) $overallSiswaL++; else $overallStaffL++;
                } else {
                    $sP++;
                    if ($isSiswa) $overallSiswaP++; else $overallStaffP++;
                }

                // Vendor Size Normalization
                $vSizeRaw = strtoupper(trim((string)($row['konversi_vendor'] ?? '')));
                if ($vSizeRaw === '' || $vSizeRaw === '-' || $vSizeRaw === '0') {
                    $vSize = 'BELUM DIKONVERSI';
                } else {
                    $vSize = match ($vSizeRaw) {
                        '2XL' => 'XXL',
                        'XXXL' => '3XL',
                        default => $vSizeRaw,
                    };
                }

                if (!isset($statsBySize[$vSize])) {
                    $statsBySize[$vSize] = [
                        'siswa_l' => 0, 'siswa_p' => 0,
                        'staff_l' => 0, 'staff_p' => 0,
                        'total_pria' => 0, 'total_wanita' => 0,
                        'total' => 0,
                    ];
                }

                if ($isSiswa) {
                    if ($isL) $statsBySize[$vSize]['siswa_l']++; else $statsBySize[$vSize]['siswa_p']++;
                } else {
                    if ($isL) $statsBySize[$vSize]['staff_l']++; else $statsBySize[$vSize]['staff_p']++;
                }

                if ($isL) {
                    $statsBySize[$vSize]['total_pria']++;
                } else {
                    $statsBySize[$vSize]['total_wanita']++;
                }
                $statsBySize[$vSize]['total']++;

                // Jenjang Tracking (SRD, SRMP, SRMA, STAFF)
                $jCode = 'STAFF';
                if ($isSiswa) {
                    $jVal = strtoupper(trim((string)($row['jenjang'] ?? '')));
                    if (str_contains($jVal, 'SD') || str_contains($jVal, 'SRD')) {
                        $jCode = 'SRD';
                    } elseif (str_contains($jVal, 'SMP') || str_contains($jVal, 'SRMP')) {
                        $jCode = 'SRMP';
                    } else {
                        $jCode = 'SRMA';
                    }
                }

                if (!isset($statsByJenjang[$jCode]['sizes'][$vSize])) {
                    $statsByJenjang[$jCode]['sizes'][$vSize] = ['l' => 0, 'p' => 0, 'total' => 0];
                }
                if ($isL) {
                    $statsByJenjang[$jCode]['l']++;
                    $statsByJenjang[$jCode]['sizes'][$vSize]['l']++;
                } else {
                    $statsByJenjang[$jCode]['p']++;
                    $statsByJenjang[$jCode]['sizes'][$vSize]['p']++;
                }
                $statsByJenjang[$jCode]['total']++;
                $statsByJenjang[$jCode]['sizes'][$vSize]['total']++;

                // SATPAM PDH Shoes
                if ($sheetKey === 'SATPAM') {
                    $pdh = (int)($row['bawahan']['ukuran_sepatu_pdh'] ?? 0);
                    if ($pdh > 0) {
                        $satpamPdhDist[$pdh] = ($satpamPdhDist[$pdh] ?? 0) + 1;
                    }
                }

                $allRecipientsMaster[] = [
                    'sheet' => $sheetKey,
                    'no' => $row['no'] ?? count($allRecipientsMaster) + 1,
                    'nama' => $row['nama_lengkap'] ?? '',
                    'jk' => $jk,
                    'jenjang_status' => $isSiswa ? ($row['jenjang'] ?? '-') : ($row['status'] ?? '-'),
                    'lingkar_badan' => $row['atasan']['lingkar_badan_cm'] ?? '-',
                    'panjang_badan' => $row['atasan']['panjang_badan_cm'] ?? '-',
                    'lingkar_pinggang' => $row['bawahan']['lingkar_pinggang_cm'] ?? '-',
                    'panjang_celana' => $row['bawahan']['panjang_celana_cm'] ?? '-',
                    'panjang_pesak' => $row['bawahan']['panjang_pesak_cm'] ?? '-',
                    'sepatu_pdh' => $row['bawahan']['ukuran_sepatu_pdh'] ?? '-',
                    'konversi_vendor' => $vSizeRaw ?: '-',
                ];
            }

            $statsBySheet[$sheetKey] = [
                'l' => $sL,
                'p' => $sP,
                'total' => count($sheetData),
            ];
            $overallTotal += count($sheetData);
        }

        ksort($satpamPdhDist);

        // Sort vendor sizes according to standard sizing order
        $sizeOrderMap = [];
        foreach (self::STANDARD_SIZES as $idx => $sVal) {
            $sizeOrderMap[$sVal] = $idx + 1;
        }

        $allDiscoveredSizes = array_unique(array_merge(self::STANDARD_SIZES, array_keys($statsBySize)));
        usort($allDiscoveredSizes, function ($a, $b) use ($sizeOrderMap) {
            $rankA = $sizeOrderMap[$a] ?? (str_contains($a, 'XL') ? 20 : 99);
            $rankB = $sizeOrderMap[$b] ?? (str_contains($b, 'XL') ? 20 : 99);
            if ($rankA === $rankB) {
                return strcmp($a, $b);
            }
            return $rankA <=> $rankB;
        });

        // =============================================================
        // SHEET 1: REKAP UKURAN SERAGAM (GLOBAL SIZE BREAKDOWN)
        // =============================================================
        $sheet1 = $spreadsheet->createSheet(0);
        $sheet1->setTitle('Rekap Ukuran Seragam');
        $sheet1->setShowGridlines(true);
        $usedSheetTitles[] = 'rekap ukuran seragam';

        // Title Banner (Row 1-2)
        $sheet1->mergeCells('A1:I1');
        $sheet1->setCellValue('A1', 'REKAPITULASI KEBUTUHAN PENGADAAN SERAGAM');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('1E293B');
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->mergeCells('A2:I2');
        $sheet1->setCellValue('A2', $scopeName . ' — TAHUN ' . date('Y'));
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
        $sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Section A Header (Row 4)
        $sheet1->setCellValue('A4', 'No');
        $sheet1->setCellValue('B4', 'Ukuran Seragam (Vendor)');
        $sheet1->setCellValue('C4', 'Siswa (L)');
        $sheet1->setCellValue('D4', 'Siswa (P)');
        $sheet1->setCellValue('E4', 'Guru & Staff (L)');
        $sheet1->setCellValue('F4', 'Guru & Staff (P)');
        $sheet1->setCellValue('G4', 'Total Pria');
        $sheet1->setCellValue('H4', 'Total Wanita');
        $sheet1->setCellValue('I4', 'TOTAL STEL');

        $sheet1->getStyle('A4:I4')->applyFromArray($headerStyleBlue);
        $sheet1->getRowDimension(4)->setRowHeight(26);

        // Size Rows
        $sizeRows = [];
        $noSize = 1;
        $totSiswaL = 0; $totSiswaP = 0;
        $totStaffL = 0; $totStaffP = 0;
        $totPria = 0; $totWanita = 0;
        $grandTotal = 0;

        foreach ($allDiscoveredSizes as $sz) {
            $data = $statsBySize[$sz] ?? [
                'siswa_l' => 0, 'siswa_p' => 0,
                'staff_l' => 0, 'staff_p' => 0,
                'total_pria' => 0, 'total_wanita' => 0,
                'total' => 0,
            ];
            $cntTotal = $data['total'];

            // Skip zero custom sizes outside standard XS-5XL
            if ($cntTotal === 0 && !in_array($sz, self::STANDARD_SIZES, true)) {
                continue;
            }

            $sizeRows[] = [
                $noSize++,
                $sz,
                $data['siswa_l'],
                $data['siswa_p'],
                $data['staff_l'],
                $data['staff_p'],
                $data['total_pria'],
                $data['total_wanita'],
                $cntTotal,
            ];

            $totSiswaL += $data['siswa_l'];
            $totSiswaP += $data['siswa_p'];
            $totStaffL += $data['staff_l'];
            $totStaffP += $data['staff_p'];
            $totPria += $data['total_pria'];
            $totWanita += $data['total_wanita'];
            $grandTotal += $cntTotal;
        }

        $rowIdx = 5;
        if (!empty($sizeRows)) {
            $sheet1->fromArray($sizeRows, null, 'A5');
            $endR1 = 4 + count($sizeRows);
            $sheet1->getStyle("A5:I{$endR1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("A5:I{$endR1}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $rowIdx = $endR1 + 1;
        }

        // Total Row for Size Matrix
        $sheet1->mergeCells("A{$rowIdx}:B{$rowIdx}");
        $sheet1->setCellValue("A{$rowIdx}", 'TOTAL KESELURUHAN');
        $sheet1->setCellValue("C{$rowIdx}", $totSiswaL);
        $sheet1->setCellValue("D{$rowIdx}", $totSiswaP);
        $sheet1->setCellValue("E{$rowIdx}", $totStaffL);
        $sheet1->setCellValue("F{$rowIdx}", $totStaffP);
        $sheet1->setCellValue("G{$rowIdx}", $totPria);
        $sheet1->setCellValue("H{$rowIdx}", $totWanita);
        $sheet1->setCellValue("I{$rowIdx}", $grandTotal);
        $sheet1->getStyle("A{$rowIdx}:I{$rowIdx}")->applyFromArray($totalRowStyle);
        $sheet1->getRowDimension($rowIdx)->setRowHeight(24);

        // Section B: Category / Sheet Breakdown (Aligned identically on columns A..I)
        $rowIdx += 3;
        $sheet1->mergeCells("A{$rowIdx}:I{$rowIdx}");
        $sheet1->setCellValue("A{$rowIdx}", 'TABEL REKAPITULASI KEBUTUHAN SETIAP KATEGORI / SHEET');
        $sheet1->getStyle("A{$rowIdx}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E293B');

        $rowIdx++;
        $sheet1->setCellValue("A{$rowIdx}", 'No');
        $sheet1->setCellValue("B{$rowIdx}", 'Kategori / Sheet Penerima');
        $sheet1->setCellValue("C{$rowIdx}", 'Siswa (L)');
        $sheet1->setCellValue("D{$rowIdx}", 'Siswa (P)');
        $sheet1->setCellValue("E{$rowIdx}", 'Guru & Staff (L)');
        $sheet1->setCellValue("F{$rowIdx}", 'Guru & Staff (P)');
        $sheet1->setCellValue("G{$rowIdx}", 'Total Pria');
        $sheet1->setCellValue("H{$rowIdx}", 'Total Wanita');
        $sheet1->setCellValue("I{$rowIdx}", 'TOTAL PENERIMA');
        $sheet1->getStyle("A{$rowIdx}:I{$rowIdx}")->applyFromArray($headerStyleIndigo);
        $sheet1->getRowDimension($rowIdx)->setRowHeight(26);

        $catRows = [];
        $noCat = 1;
        foreach ($schema as $catKey => $conf) {
            $st = $statsBySheet[$catKey] ?? ['l' => 0, 'p' => 0, 'total' => 0];
            $isS = ($catKey === 'SISWA');

            $cSL = $isS ? $st['l'] : 0;
            $cSP = $isS ? $st['p'] : 0;
            $cTL = $isS ? 0 : $st['l'];
            $cTP = $isS ? 0 : $st['p'];
            $cPria = $st['l'];
            $cWanita = $st['p'];
            $cTotal = $st['total'];

            $catRows[] = [
                $noCat++,
                $catKey,
                $cSL,
                $cSP,
                $cTL,
                $cTP,
                $cPria,
                $cWanita,
                $cTotal,
            ];
        }

        $catStartRow = $rowIdx + 1;
        $sheet1->fromArray($catRows, null, "A{$catStartRow}");
        $catEndRow = $catStartRow + count($catRows) - 1;
        $sheet1->getStyle("A{$catStartRow}:I{$catEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("A{$catStartRow}:I{$catEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        // Total Row for Category Table
        $totCatRow = $catEndRow + 1;
        $sheet1->mergeCells("A{$totCatRow}:B{$totCatRow}");
        $sheet1->setCellValue("A{$totCatRow}", 'TOTAL KESELURUHAN KATEGORI');
        $sheet1->setCellValue("C{$totCatRow}", $totSiswaL);
        $sheet1->setCellValue("D{$totCatRow}", $totSiswaP);
        $sheet1->setCellValue("E{$totCatRow}", $totStaffL);
        $sheet1->setCellValue("F{$totCatRow}", $totStaffP);
        $sheet1->setCellValue("G{$totCatRow}", $totPria);
        $sheet1->setCellValue("H{$totCatRow}", $totWanita);
        $sheet1->setCellValue("I{$totCatRow}", $grandTotal);
        $sheet1->getStyle("A{$totCatRow}:I{$totCatRow}")->applyFromArray($totalRowStyle);
        $sheet1->getRowDimension($totCatRow)->setRowHeight(24);

        // Section C: Rekapitulasi Sepatu PDH Satpam (If present)
        if (!empty($satpamPdhDist)) {
            $pRowIdx = $totCatRow + 3;
            $sheet1->mergeCells("A{$pRowIdx}:D{$pRowIdx}");
            $sheet1->setCellValue("A{$pRowIdx}", 'REKAPITULASI UKURAN SEPATU PDH SATPAM');
            $sheet1->getStyle("A{$pRowIdx}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E293B');

            $pRowIdx++;
            $sheet1->setCellValue("A{$pRowIdx}", 'No');
            $sheet1->setCellValue("B{$pRowIdx}", 'Ukuran Sepatu PDH');
            $sheet1->setCellValue("C{$pRowIdx}", 'Jumlah Personel (Pasang)');
            $sheet1->setCellValue("D{$pRowIdx}", 'Persentase (%)');
            $sheet1->getStyle("A{$pRowIdx}:D{$pRowIdx}")->applyFromArray($headerStyleEmerald);
            $sheet1->getRowDimension($pRowIdx)->setRowHeight(24);

            $pRows = [];
            $noP = 1;
            $totPdh = array_sum($satpamPdhDist);
            foreach ($satpamPdhDist as $sSize => $sQty) {
                $pct = $totPdh > 0 ? ($sQty / $totPdh) : 0;
                $pRows[] = [
                    $noP++,
                    "Ukuran {$sSize}",
                    $sQty,
                    $pct,
                ];
            }

            $pStart = $pRowIdx + 1;
            $sheet1->fromArray($pRows, null, "A{$pStart}");
            $pEnd = $pStart + count($pRows) - 1;
            $sheet1->getStyle("A{$pStart}:C{$pEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("D{$pStart}:D{$pEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet1->getStyle("D{$pStart}:D{$pEnd}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet1->getStyle("A{$pStart}:D{$pEnd}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

            $pTotRow = $pEnd + 1;
            $sheet1->mergeCells("A{$pTotRow}:B{$pTotRow}");
            $sheet1->setCellValue("A{$pTotRow}", 'TOTAL SEPATU PDH');
            $sheet1->setCellValue("C{$pTotRow}", $totPdh);
            $sheet1->setCellValue("D{$pTotRow}", 1.0);
            $sheet1->getStyle("A{$pTotRow}:D{$pTotRow}")->applyFromArray($totalRowStyle);
            $sheet1->getStyle("D{$pTotRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet1->getStyle("D{$pTotRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet1->getRowDimension($pTotRow)->setRowHeight(24);
        }

        // Explicit Column Widths for Sheet 1
        $sheet1Widths = [
            'A' => 6,
            'B' => 32,
            'C' => 14,
            'D' => 14,
            'E' => 16,
            'F' => 16,
            'G' => 14,
            'H' => 14,
            'I' => 16,
        ];
        foreach ($sheet1Widths as $col => $w) {
            $sheet1->getColumnDimension($col)->setWidth($w);
        }

        // =============================================================
        // SHEET 2: REKAP JENJANG SR (SRD, SRMP, SRMA & STAFF)
        // =============================================================
        $sheetJenjang = $spreadsheet->createSheet();
        $sheetJenjang->setTitle('Rekap Jenjang SR');
        $sheetJenjang->setShowGridlines(true);
        $usedSheetTitles[] = 'rekap jenjang sr';

        $sheetJenjang->mergeCells('A1:Q1');
        $sheetJenjang->setCellValue('A1', 'REKAPITULASI KEBUTUHAN PENGADAAN SERAGAM BERDASARKAN JENJANG SEKOLAH');
        $sheetJenjang->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E293B');
        $sheetJenjang->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheetJenjang->mergeCells('A2:Q2');
        $sheetJenjang->setCellValue('A2', 'JENJANG SHEET: SRD (SD), SRMP (SMP), SRMA (SMA) & GURU/STAFF — ' . $scopeName);
        $sheetJenjang->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
        $sheetJenjang->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $jHeaders = [
            'No', 'Ukuran Seragam',
            'SRD (L)', 'SRD (P)', 'Total SRD',
            'SRMP (L)', 'SRMP (P)', 'Total SRMP',
            'SRMA (L)', 'SRMA (P)', 'Total SRMA',
            'Guru/Staff (L)', 'Guru/Staff (P)', 'Total Staff',
            'Total Pria', 'Total Wanita', 'TOTAL STEL'
        ];
        $sheetJenjang->fromArray([$jHeaders], null, 'A4');
        $sheetJenjang->getStyle('A4:Q4')->applyFromArray($headerStyleIndigo);
        $sheetJenjang->getRowDimension(4)->setRowHeight(26);

        $jRows = [];
        $noJ = 1;
        $totSrdL = 0; $totSrdP = 0; $totSrdAll = 0;
        $totSrmpL = 0; $totSrmpP = 0; $totSrmpAll = 0;
        $totSrmaL = 0; $totSrmaP = 0; $totSrmaAll = 0;
        $totStaffJ_L = 0; $totStaffJ_P = 0; $totStaffJ_All = 0;

        foreach ($allDiscoveredSizes as $sz) {
            $srdSz = $statsByJenjang['SRD']['sizes'][$sz] ?? ['l' => 0, 'p' => 0, 'total' => 0];
            $srmpSz = $statsByJenjang['SRMP']['sizes'][$sz] ?? ['l' => 0, 'p' => 0, 'total' => 0];
            $srmaSz = $statsByJenjang['SRMA']['sizes'][$sz] ?? ['l' => 0, 'p' => 0, 'total' => 0];
            $staffSz = $statsByJenjang['STAFF']['sizes'][$sz] ?? ['l' => 0, 'p' => 0, 'total' => 0];

            $rowTotalPria = $srdSz['l'] + $srmpSz['l'] + $srmaSz['l'] + $staffSz['l'];
            $rowTotalWanita = $srdSz['p'] + $srmpSz['p'] + $srmaSz['p'] + $staffSz['p'];
            $rowTotalAll = $rowTotalPria + $rowTotalWanita;

            if ($rowTotalAll === 0 && !in_array($sz, self::STANDARD_SIZES, true)) {
                continue;
            }

            $jRows[] = [
                $noJ++,
                $sz,
                $srdSz['l'], $srdSz['p'], $srdSz['total'],
                $srmpSz['l'], $srmpSz['p'], $srmpSz['total'],
                $srmaSz['l'], $srmaSz['p'], $srmaSz['total'],
                $staffSz['l'], $staffSz['p'], $staffSz['total'],
                $rowTotalPria, $rowTotalWanita, $rowTotalAll,
            ];

            $totSrdL += $srdSz['l']; $totSrdP += $srdSz['p']; $totSrdAll += $srdSz['total'];
            $totSrmpL += $srmpSz['l']; $totSrmpP += $srmpSz['p']; $totSrmpAll += $srmpSz['total'];
            $totSrmaL += $srmaSz['l']; $totSrmaP += $srmaSz['p']; $totSrmaAll += $srmaSz['total'];
            $totStaffJ_L += $staffSz['l']; $totStaffJ_P += $staffSz['p']; $totStaffJ_All += $staffSz['total'];
        }

        $jStartRow = 5;
        if (!empty($jRows)) {
            $sheetJenjang->fromArray($jRows, null, "A{$jStartRow}");
            $jEndRow = $jStartRow + count($jRows) - 1;
            $sheetJenjang->getStyle("A{$jStartRow}:Q{$jEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetJenjang->getStyle("A{$jStartRow}:Q{$jEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $jTotRow = $jEndRow + 1;
        } else {
            $jTotRow = $jStartRow;
        }

        // Total Row Jenjang
        $sheetJenjang->mergeCells("A{$jTotRow}:B{$jTotRow}");
        $sheetJenjang->setCellValue("A{$jTotRow}", 'TOTAL KESELURUHAN');
        $sheetJenjang->setCellValue("C{$jTotRow}", $totSrdL);
        $sheetJenjang->setCellValue("D{$jTotRow}", $totSrdP);
        $sheetJenjang->setCellValue("E{$jTotRow}", $totSrdAll);
        $sheetJenjang->setCellValue("F{$jTotRow}", $totSrmpL);
        $sheetJenjang->setCellValue("G{$jTotRow}", $totSrmpP);
        $sheetJenjang->setCellValue("H{$jTotRow}", $totSrmpAll);
        $sheetJenjang->setCellValue("I{$jTotRow}", $totSrmaL);
        $sheetJenjang->setCellValue("J{$jTotRow}", $totSrmaP);
        $sheetJenjang->setCellValue("K{$jTotRow}", $totSrmaAll);
        $sheetJenjang->setCellValue("L{$jTotRow}", $totStaffJ_L);
        $sheetJenjang->setCellValue("M{$jTotRow}", $totStaffJ_P);
        $sheetJenjang->setCellValue("N{$jTotRow}", $totStaffJ_All);
        $sheetJenjang->setCellValue("O{$jTotRow}", $totPria);
        $sheetJenjang->setCellValue("P{$jTotRow}", $totWanita);
        $sheetJenjang->setCellValue("Q{$jTotRow}", $grandTotal);
        $sheetJenjang->getStyle("A{$jTotRow}:Q{$jTotRow}")->applyFromArray($totalRowStyle);
        $sheetJenjang->getRowDimension($jTotRow)->setRowHeight(24);

        $jColWidths = [
            'A' => 6, 'B' => 16,
            'C' => 10, 'D' => 10, 'E' => 12,
            'F' => 10, 'G' => 10, 'H' => 12,
            'I' => 10, 'J' => 10, 'K' => 12,
            'L' => 14, 'M' => 14, 'N' => 14,
            'O' => 12, 'P' => 12, 'Q' => 16,
        ];
        foreach ($jColWidths as $col => $w) {
            $sheetJenjang->getColumnDimension($col)->setWidth($w);
        }

        // =============================================================
        // SHEETS 3..11: DEDICATED SHEETS FOR EACH CATEGORY
        // =============================================================
        foreach ($schema as $sheetKey => $conf) {
            $sheetData = $recap[$sheetKey]['data'] ?? [];
            if (empty($sheetData) && count($spreadsheet->getAllSheets()) > 2) {
                continue;
            }

            $sheetTitle = $this->sanitizeSheetTitle($sheetKey, $usedSheetTitles);
            $catSheet = $spreadsheet->createSheet();
            $catSheet->setTitle($sheetTitle);
            $catSheet->setShowGridlines(true);

            $lastCol = ($sheetKey === 'SATPAM') ? 'K' : 'J';

            // Title Banner
            $catSheet->mergeCells("A1:{$lastCol}1");
            $catSheet->setCellValue('A1', 'DATA REKAPITULASI & PENERIMA SERAGAM');
            $catSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E293B');
            $catSheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $catSheet->mergeCells("A2:{$lastCol}2");
            $catSheet->setCellValue('A2', "KATEGORI: {$sheetKey} — {$scopeName} — TAHUN " . date('Y'));
            $catSheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
            $catSheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Profile Box (Row 4-6)
            $catSheet->setCellValue('A4', 'Satuan Pendidikan:');
            $catSheet->setCellValue('B4', $this->sanitizeCellValue($rawSchoolName));
            $catSheet->setCellValue('A5', 'Kategori / Sheet:');
            $catSheet->setCellValue('B5', $sheetKey);
            $catSheet->setCellValue('A6', 'Petugas Ukur / PIC:');
            $catSheet->setCellValue('B6', $this->sanitizeCellValue($picNama));

            $stL = $statsBySheet[$sheetKey]['l'] ?? 0;
            $stP = $statsBySheet[$sheetKey]['p'] ?? 0;
            $stTot = $statsBySheet[$sheetKey]['total'] ?? count($sheetData);

            $infoColLabel = ($sheetKey === 'SATPAM') ? 'G' : 'F';
            $infoColVal = ($sheetKey === 'SATPAM') ? 'H' : 'G';

            $catSheet->setCellValue("{$infoColLabel}4", 'Total Personel:');
            $catSheet->setCellValue("{$infoColVal}4", "{$stTot} Orang");
            $catSheet->setCellValue("{$infoColLabel}5", 'Laki-laki / Perempuan:');
            $catSheet->setCellValue("{$infoColVal}5", "{$stL} L / {$stP} P");
            $catSheet->setCellValue("{$infoColLabel}6", 'Total Kebutuhan:');
            $catSheet->setCellValue("{$infoColVal}6", "{$stTot} Stel");

            $catSheet->getStyle('A4:A6')->getFont()->setBold(true);
            $catSheet->getStyle("{$infoColLabel}4:{$infoColLabel}6")->getFont()->setBold(true);
            $catSheet->getStyle("{$infoColVal}4:{$infoColVal}6")->getFont()->setBold(true);

            // Group Headers (Row 8)
            $catSheet->setCellValue('A8', 'INFORMASI DASAR');
            $catSheet->mergeCells('A8:D8');
            $catSheet->getStyle('A8:D8')->applyFromArray($headerStyleIndigo);

            $catSheet->setCellValue('E8', 'UKURAN ATASAN');
            $catSheet->mergeCells('E8:F8');
            $catSheet->getStyle('E8:F8')->applyFromArray($headerStyleBlue);

            $lastBawahan = ($sheetKey === 'SATPAM') ? 'J8' : 'I8';
            $catSheet->setCellValue('G8', 'UKURAN BAWAHAN');
            $catSheet->mergeCells("G8:{$lastBawahan}");
            $catSheet->getStyle("G8:{$lastBawahan}")->applyFromArray($headerStyleEmerald);

            $catSheet->setCellValue("{$lastCol}8", 'VENDOR');
            $catSheet->getStyle("{$lastCol}8")->applyFromArray($headerStyleIndigo);
            $catSheet->getRowDimension(8)->setRowHeight(22);

            // Column Headers (Row 9)
            $catCols = [
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
                $catCols['J'] = 'Sepatu PDH';
                $catCols['K'] = 'Konversi Vendor';
            } else {
                $catCols['J'] = 'Konversi Vendor';
            }

            foreach ($catCols as $c => $lbl) {
                $catSheet->setCellValue("{$c}9", $lbl);
                $catSheet->getStyle("{$c}9")->applyFromArray($subHeaderDark);
            }
            $catSheet->getRowDimension(9)->setRowHeight(24);

            // Data Rows (Starting at Row 10)
            $detailRows = [];
            $noD = 1;
            foreach ($sheetData as $row) {
                $vSizeRaw = strtoupper(trim((string)($row['konversi_vendor'] ?? '')));
                $item = [
                    $row['no'] ?? $noD++,
                    $this->sanitizeCellValue($row['nama_lengkap'] ?? ''),
                    $row['jenis_kelamin'] ?? '',
                    $this->sanitizeCellValue(($sheetKey === 'SISWA') ? ($row['jenjang'] ?? '-') : ($row['status'] ?? '-')),
                    $row['atasan']['lingkar_badan_cm'] ?? '-',
                    $row['atasan']['panjang_badan_cm'] ?? '-',
                    $row['bawahan']['lingkar_pinggang_cm'] ?? '-',
                    $row['bawahan']['panjang_celana_cm'] ?? '-',
                    $row['bawahan']['panjang_pesak_cm'] ?? '-',
                ];
                if ($sheetKey === 'SATPAM') {
                    $item[] = $row['bawahan']['ukuran_sepatu_pdh'] ?? '-';
                }
                $item[] = $vSizeRaw ?: '-';
                $detailRows[] = $item;
            }

            $dStart = 10;
            $dEnd = $dStart;
            if (!empty($detailRows)) {
                $catSheet->fromArray($detailRows, null, "A{$dStart}");
                $dEnd = $dStart + count($detailRows) - 1;

                $catSheet->getStyle("A{$dStart}:A{$dEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $catSheet->getStyle("C{$dStart}:C{$dEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $catSheet->getStyle("D{$dStart}:D{$dEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $catSheet->getStyle("E{$dStart}:{$lastCol}{$dEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $catSheet->getStyle("{$lastCol}{$dStart}:{$lastCol}{$dEnd}")->getFont()->setBold(true);

                $catSheet->getStyle("A{$dStart}:{$lastCol}{$dEnd}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                // Zebra striping
                for ($r = $dStart; $r <= $dEnd; $r++) {
                    $catSheet->getRowDimension($r)->setRowHeight(20);
                    if ($r % 2 === 1) {
                        $catSheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                    }
                }
            }

            // Signatures Section (Mengetahui / Mengesahkan)
            $sigRow = max($dEnd, 9) + 3;
            $kotaDate = ($kota ?? 'Indonesia') . ', ' . date('d F Y');
            $sigCol = ($sheetKey === 'SATPAM') ? 'G' : 'F';

            $catSheet->setCellValue("{$sigCol}{$sigRow}", $kotaDate);
            $catSheet->setCellValue("B" . ($sigRow + 1), 'Mengetahui,');
            $catSheet->setCellValue("{$sigCol}" . ($sigRow + 1), 'Mengesahkan,');
            $catSheet->setCellValue("B" . ($sigRow + 2), 'Petugas Ukur / PIC');
            $catSheet->setCellValue("{$sigCol}" . ($sigRow + 2), 'Kepala Sekolah');

            $catSheet->setCellValue("B" . ($sigRow + 6), strtoupper($picNama));
            $catSheet->setCellValue("{$sigCol}" . ($sigRow + 6), strtoupper($ksName));
            $catSheet->getStyle("B" . ($sigRow + 6))->getFont()->setBold(true)->setUnderline(true);
            $catSheet->getStyle("{$sigCol}" . ($sigRow + 6))->getFont()->setBold(true)->setUnderline(true);

            // Column Dimensions
            $catWidths = [
                'A' => 6,
                'B' => 32,
                'C' => 14,
                'D' => 16,
                'E' => 18,
                'F' => 18,
                'G' => 18,
                'H' => 18,
                'I' => 18,
                'J' => 16,
            ];
            if ($sheetKey === 'SATPAM') {
                $catWidths['J'] = 14;
                $catWidths['K'] = 16;
            }
            foreach ($catWidths as $col => $w) {
                $catSheet->getColumnDimension($col)->setWidth($w);
            }
        }

        // =============================================================
        // SHEET TERAKHIR: DAFTAR SEMUA PENERIMA (MASTER LIST)
        // =============================================================
        $sheetMaster = $spreadsheet->createSheet();
        $sheetMaster->setTitle('Daftar Penerima');
        $sheetMaster->setShowGridlines(true);

        $mHeaders = [
            'No',
            'Kategori / Sheet',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Jenjang / Status',
            'Lingkar Badan (cm)',
            'Panjang Badan (cm)',
            'Lingkar Pinggang (cm)',
            'Panjang Celana (cm)',
            'Panjang Pesak (cm)',
            'Sepatu PDH',
            'Konversi Vendor'
        ];
        $sheetMaster->fromArray([$mHeaders], null, 'A1');
        $sheetMaster->getStyle('A1:L1')->applyFromArray($headerStyleEmerald);
        $sheetMaster->getRowDimension(1)->setRowHeight(26);

        $mRows = [];
        $noM = 1;
        foreach ($allRecipientsMaster as $rc) {
            $mRows[] = [
                $noM++,
                $rc['sheet'],
                $this->sanitizeCellValue($rc['nama']),
                $rc['jk'],
                $this->sanitizeCellValue($rc['jenjang_status']),
                $rc['lingkar_badan'],
                $rc['panjang_badan'],
                $rc['lingkar_pinggang'],
                $rc['panjang_celana'],
                $rc['panjang_pesak'],
                $rc['sepatu_pdh'],
                $rc['konversi_vendor'],
            ];
        }

        if (!empty($mRows)) {
            $sheetMaster->fromArray($mRows, null, 'A2');
            $endMR = 1 + count($mRows);
            $sheetMaster->getStyle("A2:A{$endMR}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetMaster->getStyle("B2:B{$endMR}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetMaster->getStyle("D2:E{$endMR}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetMaster->getStyle("F2:L{$endMR}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetMaster->getStyle("L2:L{$endMR}")->getFont()->setBold(true);

            if (count($mRows) <= 2500) {
                $sheetMaster->getStyle("A2:L{$endMR}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            } else {
                $sheetMaster->getStyle("A2:L{$endMR}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            }
        }

        $masterWidths = [
            'A' => 6,
            'B' => 20,
            'C' => 32,
            'D' => 14,
            'E' => 16,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 18,
            'K' => 14,
            'L' => 18,
        ];
        foreach ($masterWidths as $col => $w) {
            $sheetMaster->getColumnDimension($col)->setWidth($w);
        }

        // Set active sheet back to Sheet 0 (Rekap Ukuran Seragam)
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Sanitize cell text value from potential formula injection.
     */
    protected function sanitizeCellValue($value): string
    {
        $str = (string)($value ?? '');
        if (isset($str[0]) && in_array($str[0], ['=', '+', '-', '@'])) {
            return "'" . $str;
        }
        return $str;
    }

    /**
     * Sanitize and format sheet title to obey Excel 31-character and character restrictions.
     */
    protected function sanitizeSheetTitle(string $title, array &$usedTitles): string
    {
        $clean = preg_replace('/[\\\\\/\\?\\*\\:\\[\\]]/', '', $title);
        $clean = trim($clean);
        if ($clean === '') {
            $clean = 'Kategori';
        }

        $base = mb_substr($clean, 0, 26);
        $finalTitle = $base;
        $counter = 1;

        while (in_array(strtolower($finalTitle), array_map('strtolower', $usedTitles), true)) {
            $counter++;
            $finalTitle = mb_substr($base, 0, 24) . ' (' . $counter . ')';
        }

        $usedTitles[] = $finalTitle;
        return $finalTitle;
    }
}
