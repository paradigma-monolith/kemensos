<?php

namespace App\Services;

use App\Models\School;
use App\Models\ShoeRecipient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ShoeExportService
{
    /**
     * Generate an Excel spreadsheet with Recap Matrix, Per-School Tables,
     * and Detailed Recipient Lists.
     *
     * Highly optimized for large scale data:
     * - Elevates memory_limit and max_execution_time to prevent script timeouts.
     * - Single O(N) pre-aggregation pass eliminates thousands of collection filter passes.
     * - Selects only necessary columns and maps schools by ID (no heavy Eloquent hydration).
     * - Memory-safe styling avoiding Coordinate reference explosion on massive rows.
     *
     * @param int|null $schoolId Filter by specific school or null for all schools
     * @return Spreadsheet
     */
    public function generateRecapExcel(?int $schoolId = null): Spreadsheet
    {
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $spreadsheet = new Spreadsheet();
        $usedSheetTitles = [];

        // Determine scope label
        $scopeName = 'SEMUA SEKOLAH RAKYAT';
        $filteredSchool = null;
        if ($schoolId) {
            $filteredSchool = School::find($schoolId);
            if ($filteredSchool) {
                $scopeName = strtoupper($filteredSchool->nama_sekolah);
            }
        }

        // Shared Header and Total styles
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

        $totalRowStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        ];

        // Fetch distinct available sizes
        $availableSizes = ShoeRecipient::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->distinct()
            ->orderBy('ukuran_sepatu')
            ->pluck('ukuran_sepatu')
            ->toArray();
        $sizes = array_unique(array_merge(range(26, 46), array_map('intval', $availableSizes)));
        sort($sizes);

        // Fetch schools and create fast ID lookup map
        $schoolsQuery = School::query();
        if ($schoolId) {
            $schoolsQuery->where('id', $schoolId);
        }
        $schools = $schoolsQuery->orderBy('nama_sekolah')->get();
        $schoolsMap = $schools->keyBy('id');

        // Fetch recipients with only necessary fields to minimize Eloquent hydration overhead
        $allRecipientsQuery = ShoeRecipient::query()
            ->select(['id', 'school_id', 'nomor_urut', 'nama_lengkap', 'jenis_kelamin', 'jabatan', 'kategori', 'panjang_kaki', 'ukuran_sepatu'])
            ->orderBy('school_id')
            ->orderBy('kategori')
            ->orderBy('nomor_urut');

        if ($schoolId) {
            $allRecipientsQuery->where('school_id', $schoolId);
        }
        $allRecipients = $allRecipientsQuery->get();

        // Single-pass O(N) aggregation: pre-computes size, school, and cross-tab stats
        $statsBySize = [];
        $statsBySchool = [];
        $recipientsBySchool = [];

        foreach ($allRecipients as $r) {
            $sz = (int)$r->ukuran_sepatu;
            $schId = (int)$r->school_id;
            $isL = ($r->jenis_kelamin === 'L');
            $isSiswa = ($r->kategori === 'siswa');
            $isStaff = in_array($r->kategori, ['guru', 'tendik'], true);

            if (!isset($statsBySize[$sz])) {
                $statsBySize[$sz] = [
                    'siswa_l' => 0, 'siswa_p' => 0,
                    'staff_l' => 0, 'staff_p' => 0,
                    'total_pria' => 0, 'total_wanita' => 0,
                    'total' => 0,
                ];
            }

            if (!isset($statsBySchool[$schId])) {
                $statsBySchool[$schId] = [
                    'siswa_l' => 0, 'siswa_p' => 0,
                    'staff_l' => 0, 'staff_p' => 0,
                    'pria' => 0, 'wanita' => 0,
                    'total' => 0,
                    'sizes' => [],
                    'size_counts' => [],
                ];
                $recipientsBySchool[$schId] = [];
            }

            if (!isset($statsBySchool[$schId]['sizes'][$sz])) {
                $statsBySchool[$schId]['sizes'][$sz] = [
                    'siswa_l' => 0, 'siswa_p' => 0,
                    'staff_l' => 0, 'staff_p' => 0,
                    'total' => 0,
                ];
                $statsBySchool[$schId]['size_counts'][$sz] = 0;
            }

            if ($isSiswa) {
                if ($isL) {
                    $statsBySize[$sz]['siswa_l']++;
                    $statsBySchool[$schId]['siswa_l']++;
                    $statsBySchool[$schId]['sizes'][$sz]['siswa_l']++;
                } else {
                    $statsBySize[$sz]['siswa_p']++;
                    $statsBySchool[$schId]['siswa_p']++;
                    $statsBySchool[$schId]['sizes'][$sz]['siswa_p']++;
                }
            } elseif ($isStaff) {
                if ($isL) {
                    $statsBySize[$sz]['staff_l']++;
                    $statsBySchool[$schId]['staff_l']++;
                    $statsBySchool[$schId]['sizes'][$sz]['staff_l']++;
                } else {
                    $statsBySize[$sz]['staff_p']++;
                    $statsBySchool[$schId]['staff_p']++;
                    $statsBySchool[$schId]['sizes'][$sz]['staff_p']++;
                }
            }

            if ($isL) {
                $statsBySize[$sz]['total_pria']++;
                $statsBySchool[$schId]['pria']++;
            } else {
                $statsBySize[$sz]['total_wanita']++;
                $statsBySchool[$schId]['wanita']++;
            }

            $statsBySize[$sz]['total']++;
            $statsBySchool[$schId]['total']++;
            $statsBySchool[$schId]['sizes'][$sz]['total']++;
            $statsBySchool[$schId]['size_counts'][$sz]++;

            $recipientsBySchool[$schId][] = $r;
        }

        // ====================================================
        // SHEET 1: REKAP UKURAN SEPATU (GLOBAL SIZE BREAKDOWN)
        // ====================================================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Rekap Ukuran Sepatu');
        $sheet1->setShowGridlines(true);
        $usedSheetTitles[] = 'rekap ukuran sepatu';

        // Title
        $sheet1->mergeCells('A1:I1');
        $sheet1->setCellValue('A1', 'REKAPITULASI KEBUTUHAN PENGADAAN SEPATU');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('1E293B');
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->mergeCells('A2:I2');
        $sheet1->setCellValue('A2', $scopeName . ' — TAHUN ' . date('Y'));
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
        $sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Section A Header
        $sheet1->setCellValue('A4', 'No');
        $sheet1->setCellValue('B4', 'Ukuran Sepatu');
        $sheet1->setCellValue('C4', 'Siswa (L)');
        $sheet1->setCellValue('D4', 'Siswa (P)');
        $sheet1->setCellValue('E4', 'Guru & Tendik (L)');
        $sheet1->setCellValue('F4', 'Guru & Tendik (P)');
        $sheet1->setCellValue('G4', 'Total Pria');
        $sheet1->setCellValue('H4', 'Total Wanita');
        $sheet1->setCellValue('I4', 'TOTAL PASANG');

        $sheet1->getStyle('A4:I4')->applyFromArray($headerStyleBlue);
        $sheet1->getRowDimension(4)->setRowHeight(26);

        // Populate Size Distribution Matrix
        $sizeRows = [];
        $noSize = 1;
        $totSiswaL = 0; $totSiswaP = 0; $totStaffL = 0; $totStaffP = 0;
        $totPria = 0; $totWanita = 0; $grandTotal = 0;

        foreach ($sizes as $s) {
            $data = $statsBySize[$s] ?? [
                'siswa_l' => 0, 'siswa_p' => 0,
                'staff_l' => 0, 'staff_p' => 0,
                'total_pria' => 0, 'total_wanita' => 0,
                'total' => 0,
            ];
            $cntTotal = $data['total'];

            // Skip zero rows if total is 0 and size is outside standard 26-46
            if ($cntTotal === 0 && ($s < 26 || $s > 46)) {
                continue;
            }

            $sizeRows[] = [
                $noSize++,
                $s,
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

        // If exporting all schools, also attach School Summary table below the size matrix in Sheet 1
        if (!$schoolId && $schools->isNotEmpty()) {
            $rowIdx += 3;
            $sheet1->mergeCells("A{$rowIdx}:H{$rowIdx}");
            $sheet1->setCellValue("A{$rowIdx}", 'TABEL REKAPITULASI KEBUTUHAN SETIAP SEKOLAH');
            $sheet1->getStyle("A{$rowIdx}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E293B');

            $rowIdx++;
            $sheet1->setCellValue("A{$rowIdx}", 'No');
            $sheet1->setCellValue("B{$rowIdx}", 'Nama Sekolah');
            $sheet1->setCellValue("C{$rowIdx}", 'Kota');
            $sheet1->setCellValue("D{$rowIdx}", 'Siswa (L)');
            $sheet1->setCellValue("E{$rowIdx}", 'Siswa (P)');
            $sheet1->setCellValue("F{$rowIdx}", 'Guru/Tendik (L)');
            $sheet1->setCellValue("G{$rowIdx}", 'Guru/Tendik (P)');
            $sheet1->setCellValue("H{$rowIdx}", 'TOTAL PASANG');
            $sheet1->getStyle("A{$rowIdx}:H{$rowIdx}")->applyFromArray($headerStyleIndigo);
            $sheet1->getRowDimension($rowIdx)->setRowHeight(24);

            $schRows = [];
            $noSch = 1;
            foreach ($schools as $sch) {
                $st = $statsBySchool[$sch->id] ?? [
                    'siswa_l' => 0, 'siswa_p' => 0,
                    'staff_l' => 0, 'staff_p' => 0,
                    'total' => 0,
                ];

                $schRows[] = [
                    $noSch++,
                    $this->sanitizeCellValue($sch->nama_sekolah),
                    $this->sanitizeCellValue($sch->kota ?? 'Indonesia'),
                    $st['siswa_l'],
                    $st['siswa_p'],
                    $st['staff_l'],
                    $st['staff_p'],
                    $st['total'],
                ];
            }

            $schStartRow = $rowIdx + 1;
            $sheet1->fromArray($schRows, null, "A{$schStartRow}");
            $schEndRow = $schStartRow + count($schRows) - 1;
            $sheet1->getStyle("A{$schStartRow}:A{$schEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("C{$schStartRow}:H{$schEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("A{$schStartRow}:H{$schEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

            // Total row for per-school summary
            $totSchRow = $schEndRow + 1;
            $sheet1->mergeCells("A{$totSchRow}:C{$totSchRow}");
            $sheet1->setCellValue("A{$totSchRow}", 'TOTAL KESELURUHAN SEKOLAH');
            $sheet1->setCellValue("D{$totSchRow}", $totSiswaL);
            $sheet1->setCellValue("E{$totSchRow}", $totSiswaP);
            $sheet1->setCellValue("F{$totSchRow}", $totStaffL);
            $sheet1->setCellValue("G{$totSchRow}", $totStaffP);
            $sheet1->setCellValue("H{$totSchRow}", $grandTotal);
            $sheet1->getStyle("A{$totSchRow}:H{$totSchRow}")->applyFromArray($totalRowStyle);
            $sheet1->getRowDimension($totSchRow)->setRowHeight(24);
        }

        // Set explicit column widths for Sheet 1
        $sheet1Widths = [
            'A' => 6,
            'B' => 32,
            'C' => 16,
            'D' => 12,
            'E' => 12,
            'F' => 16,
            'G' => 16,
            'H' => 16,
            'I' => 16,
        ];
        foreach ($sheet1Widths as $col => $w) {
            $sheet1->getColumnDimension($col)->setWidth($w);
        }

        // ====================================================
        // SHEET 2: REKAP PER SEKOLAH (COMPREHENSIVE MATRIX)
        // ====================================================
        if (!$schoolId && $schools->isNotEmpty()) {
            $sheetPerSchool = $spreadsheet->createSheet();
            $sheetPerSchool->setTitle('Rekap Per Sekolah');
            $sheetPerSchool->setShowGridlines(true);
            $usedSheetTitles[] = 'rekap per sekolah';

            // Title
            $sheetPerSchool->mergeCells('A1:M1');
            $sheetPerSchool->setCellValue('A1', 'REKAPITULASI KEBUTUHAN PENGADAAN SEPATU PER SEKOLAH');
            $sheetPerSchool->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('1E293B');
            $sheetPerSchool->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheetPerSchool->mergeCells('A2:M2');
            $sheetPerSchool->setCellValue('A2', 'KEMENTERIAN SOSIAL RI — TAHUN ' . date('Y'));
            $sheetPerSchool->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
            $sheetPerSchool->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Table 1: Ringkasan Data Setiap Sekolah (Tanpa Kolom Kepala Sekolah & Ukuran Dominan)
            $sheetPerSchool->setCellValue('A4', 'No');
            $sheetPerSchool->setCellValue('B4', 'Nama Sekolah');
            $sheetPerSchool->setCellValue('C4', 'Kota / Wilayah');
            $sheetPerSchool->setCellValue('D4', 'Petugas Ukur / PIC');
            $sheetPerSchool->setCellValue('E4', 'Siswa (L)');
            $sheetPerSchool->setCellValue('F4', 'Siswa (P)');
            $sheetPerSchool->setCellValue('G4', 'Guru & Tendik (L)');
            $sheetPerSchool->setCellValue('H4', 'Guru & Tendik (P)');
            $sheetPerSchool->setCellValue('I4', 'Total Siswa');
            $sheetPerSchool->setCellValue('J4', 'Total Staff');
            $sheetPerSchool->setCellValue('K4', 'Total Pria');
            $sheetPerSchool->setCellValue('L4', 'Total Wanita');
            $sheetPerSchool->setCellValue('M4', 'TOTAL PASANG');

            $sheetPerSchool->getStyle('A4:M4')->applyFromArray($headerStyleIndigo);
            $sheetPerSchool->getRowDimension(4)->setRowHeight(26);

            $tbl1Rows = [];
            $noTbl1 = 1;
            foreach ($schools as $sch) {
                $st = $statsBySchool[$sch->id] ?? [
                    'siswa_l' => 0, 'siswa_p' => 0,
                    'staff_l' => 0, 'staff_p' => 0,
                    'pria' => 0, 'wanita' => 0,
                    'total' => 0,
                    'size_counts' => [],
                ];
                $sL = $st['siswa_l'];
                $sP = $st['siswa_p'];
                $stL = $st['staff_l'];
                $stP = $st['staff_p'];
                $totSiswa = $sL + $sP;
                $totStaff = $stL + $stP;
                $pria = $st['pria'];
                $wanita = $st['wanita'];
                $totalPasang = $st['total'];

                $tbl1Rows[] = [
                    $noTbl1++,
                    $this->sanitizeCellValue($sch->nama_sekolah),
                    $this->sanitizeCellValue($sch->kota ?? 'Indonesia'),
                    $this->sanitizeCellValue($sch->pic_nama ?? '-'),
                    $sL,
                    $sP,
                    $stL,
                    $stP,
                    $totSiswa,
                    $totStaff,
                    $pria,
                    $wanita,
                    $totalPasang,
                ];
            }

            $sheetPerSchool->fromArray($tbl1Rows, null, 'A5');
            $endTbl1 = 4 + count($tbl1Rows);
            $sheetPerSchool->getStyle("A5:A{$endTbl1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetPerSchool->getStyle("E5:M{$endTbl1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetPerSchool->getStyle("A5:M{$endTbl1}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

            // Total row Table 1
            $totRowTbl1 = $endTbl1 + 1;
            $sheetPerSchool->mergeCells("A{$totRowTbl1}:D{$totRowTbl1}");
            $sheetPerSchool->setCellValue("A{$totRowTbl1}", 'TOTAL KESELURUHAN');
            $sheetPerSchool->setCellValue("E{$totRowTbl1}", $totSiswaL);
            $sheetPerSchool->setCellValue("F{$totRowTbl1}", $totSiswaP);
            $sheetPerSchool->setCellValue("G{$totRowTbl1}", $totStaffL);
            $sheetPerSchool->setCellValue("H{$totRowTbl1}", $totStaffP);
            $sheetPerSchool->setCellValue("I{$totRowTbl1}", $totSiswaL + $totSiswaP);
            $sheetPerSchool->setCellValue("J{$totRowTbl1}", $totStaffL + $totStaffP);
            $sheetPerSchool->setCellValue("K{$totRowTbl1}", $totPria);
            $sheetPerSchool->setCellValue("L{$totRowTbl1}", $totWanita);
            $sheetPerSchool->setCellValue("M{$totRowTbl1}", $grandTotal);
            $sheetPerSchool->getStyle("A{$totRowTbl1}:M{$totRowTbl1}")->applyFromArray($totalRowStyle);
            $sheetPerSchool->getRowDimension($totRowTbl1)->setRowHeight(24);

            // Table 2: Matriks Distribusi Ukuran Sepatu Setiap Sekolah (Cross-tab)
            $matrixStartRow = $totRowTbl1 + 3;
            $sheetPerSchool->setCellValue("A{$matrixStartRow}", 'MATRIKS DISTRIBUSI UKURAN SEPATU SETIAP SEKOLAH');
            $sheetPerSchool->getStyle("A{$matrixStartRow}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E293B');

            $mHeaderRow = $matrixStartRow + 1;
            $mHeaders = ['No', 'Nama Sekolah', 'Kota / Wilayah'];
            foreach ($sizes as $s) {
                $mHeaders[] = (string)$s;
            }
            $mHeaders[] = 'TOTAL PASANG';

            $sheetPerSchool->fromArray([$mHeaders], null, "A{$mHeaderRow}");
            $lastColLetter = $this->getColumnLetter(count($mHeaders));
            $sheetPerSchool->getStyle("A{$mHeaderRow}:{$lastColLetter}{$mHeaderRow}")->applyFromArray($headerStyleBlue);
            $sheetPerSchool->getRowDimension($mHeaderRow)->setRowHeight(24);

            $mRows = [];
            $noM = 1;
            $colSums = array_fill_keys($sizes, 0);

            foreach ($schools as $sch) {
                $schSizes = $statsBySchool[$sch->id]['sizes'] ?? [];
                $rowItem = [
                    $noM++,
                    $this->sanitizeCellValue($sch->nama_sekolah),
                    $this->sanitizeCellValue($sch->kota ?? 'Indonesia'),
                ];

                $schTotal = 0;
                foreach ($sizes as $s) {
                    $cnt = $schSizes[$s]['total'] ?? 0;
                    $rowItem[] = $cnt;
                    $colSums[$s] += $cnt;
                    $schTotal += $cnt;
                }
                $rowItem[] = $schTotal;
                $mRows[] = $rowItem;
            }

            $mDataStartRow = $mHeaderRow + 1;
            $sheetPerSchool->fromArray($mRows, null, "A{$mDataStartRow}");
            $mDataEndRow = $mDataStartRow + count($mRows) - 1;
            $sheetPerSchool->getStyle("A{$mDataStartRow}:A{$mDataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetPerSchool->getStyle("D{$mDataStartRow}:{$lastColLetter}{$mDataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetPerSchool->getStyle("A{$mDataStartRow}:{$lastColLetter}{$mDataEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

            // Total Row for Cross-tab Matrix
            $mTotalRow = $mDataEndRow + 1;
            $mTotalCells = ['TOTAL', '', ''];
            foreach ($sizes as $s) {
                $mTotalCells[] = $colSums[$s];
            }
            $mTotalCells[] = $grandTotal;

            $sheetPerSchool->fromArray([$mTotalCells], null, "A{$mTotalRow}");
            $sheetPerSchool->mergeCells("A{$mTotalRow}:C{$mTotalRow}");
            $sheetPerSchool->setCellValue("A{$mTotalRow}", 'TOTAL KESELURUHAN');
            $sheetPerSchool->getStyle("A{$mTotalRow}:{$lastColLetter}{$mTotalRow}")->applyFromArray($totalRowStyle);
            $sheetPerSchool->getRowDimension($mTotalRow)->setRowHeight(24);

            // Set column widths for Sheet 2
            $sheetPerSchool->getColumnDimension('A')->setWidth(6);
            $sheetPerSchool->getColumnDimension('B')->setWidth(34);
            $sheetPerSchool->getColumnDimension('C')->setWidth(20);
            $sheetPerSchool->getColumnDimension('D')->setWidth(22); // Petugas Ukur / PIC
            $sheetPerSchool->getColumnDimension('E')->setWidth(12); // Siswa (L)
            $sheetPerSchool->getColumnDimension('F')->setWidth(12); // Siswa (P)
            $sheetPerSchool->getColumnDimension('G')->setWidth(18); // Guru & Tendik (L)
            $sheetPerSchool->getColumnDimension('H')->setWidth(18); // Guru & Tendik (P)
            $sheetPerSchool->getColumnDimension('I')->setWidth(14); // Total Siswa
            $sheetPerSchool->getColumnDimension('J')->setWidth(14); // Total Staff
            $sheetPerSchool->getColumnDimension('K')->setWidth(14); // Total Pria
            $sheetPerSchool->getColumnDimension('L')->setWidth(14); // Total Wanita
            $sheetPerSchool->getColumnDimension('M')->setWidth(16); // TOTAL PASANG

            for ($c = 4; $c <= 3 + count($sizes); $c++) {
                $colLet = $this->getColumnLetter($c);
                if ($sheetPerSchool->getColumnDimension($colLet)->getWidth() <= 0) {
                    $sheetPerSchool->getColumnDimension($colLet)->setWidth(8);
                }
            }
            $sheetPerSchool->getColumnDimension($lastColLetter)->setWidth(16);
        }

        // ====================================================
        // DEDICATED SHEETS FOR EACH SCHOOL (INDIVIDUAL DATA)
        // ====================================================
        // Generate separate sheets for up to 30 schools if total records are within safe memory limits
        $allowSchoolSheets = ($schoolId !== null) || ($schools->count() <= 30 && count($allRecipients) <= 4000);
        if ($allowSchoolSheets) {
            foreach ($schools as $sch) {
                $schRecs = $recipientsBySchool[$sch->id] ?? [];
                if (empty($schRecs) && $schools->count() > 1) {
                    continue;
                }

                $sheetTitle = $this->sanitizeSheetTitle($sch->nama_sekolah, $usedSheetTitles);
                $schSheet = $spreadsheet->createSheet();
                $schSheet->setTitle($sheetTitle);
                $schSheet->setShowGridlines(true);

                // Title
                $schSheet->mergeCells('A1:G1');
                $schSheet->setCellValue('A1', 'DATA REKAPITULASI & PENERIMA SEPATU');
                $schSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E293B');
                $schSheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $schSheet->mergeCells('A2:G2');
                $schSheet->setCellValue('A2', strtoupper($sch->nama_sekolah) . ' — TAHUN ' . date('Y'));
                $schSheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
                $schSheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Profile Box
                $schSheet->setCellValue('A4', 'Kota / Wilayah:');
                $schSheet->setCellValue('B4', $this->sanitizeCellValue($sch->kota ?? 'Indonesia'));
                $schSheet->setCellValue('A5', 'Kepala Sekolah:');
                $schSheet->setCellValue('B5', $this->sanitizeCellValue($sch->kepala_sekolah_nama ?? '-'));
                $schSheet->setCellValue('A6', 'Petugas Ukur / PIC:');
                $schSheet->setCellValue('B6', $this->sanitizeCellValue($sch->pic_nama ?? '-'));

                $sTotalSiswa = ($statsBySchool[$sch->id]['siswa_l'] ?? 0) + ($statsBySchool[$sch->id]['siswa_p'] ?? 0);
                $sTotalStaff = ($statsBySchool[$sch->id]['staff_l'] ?? 0) + ($statsBySchool[$sch->id]['staff_p'] ?? 0);

                $schSheet->setCellValue('E4', 'Total Siswa:');
                $schSheet->setCellValue('F4', $sTotalSiswa . ' Orang');
                $schSheet->setCellValue('E5', 'Total Guru & Tendik:');
                $schSheet->setCellValue('F5', $sTotalStaff . ' Orang');
                $schSheet->setCellValue('E6', 'Total Kebutuhan:');
                $schSheet->setCellValue('F6', count($schRecs) . ' Pasang');

                $schSheet->getStyle('A4:A6')->getFont()->setBold(true);
                $schSheet->getStyle('E4:E6')->getFont()->setBold(true);
                $schSheet->getStyle('F4:F6')->getFont()->setBold(true);

                // Section 1: Mini Size Matrix for this school
                $schSheet->setCellValue('A8', 'A. REKAPITULASI KEBUTUHAN MENURUT UKURAN');
                $schSheet->getStyle('A8')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E293B');

                $schSheet->setCellValue('A9', 'No');
                $schSheet->setCellValue('B9', 'Ukuran Sepatu');
                $schSheet->setCellValue('C9', 'Siswa (L)');
                $schSheet->setCellValue('D9', 'Siswa (P)');
                $schSheet->setCellValue('E9', 'Guru & Tendik (L)');
                $schSheet->setCellValue('F9', 'Guru & Tendik (P)');
                $schSheet->setCellValue('G9', 'TOTAL PASANG');
                $schSheet->getStyle('A9:G9')->applyFromArray($headerStyleBlue);
                $schSheet->getRowDimension(9)->setRowHeight(22);

                $schSizesData = $statsBySchool[$sch->id]['sizes'] ?? [];
                ksort($schSizesData);
                $schSizeRows = [];
                $noSS = 1;
                $totSL = 0; $totSP = 0; $totTL = 0; $totTP = 0; $totSchPsg = 0;

                foreach ($schSizesData as $szVal => $szSt) {
                    if ($szSt['total'] === 0) continue;
                    $cSL = $szSt['siswa_l'];
                    $cSP = $szSt['siswa_p'];
                    $cTL = $szSt['staff_l'];
                    $cTP = $szSt['staff_p'];
                    $cTot = $szSt['total'];

                    $schSizeRows[] = [$noSS++, $szVal, $cSL, $cSP, $cTL, $cTP, $cTot];
                    $totSL += $cSL; $totSP += $cSP; $totTL += $cTL; $totTP += $cTP; $totSchPsg += $cTot;
                }

                $curR = 10;
                if (!empty($schSizeRows)) {
                    $schSheet->fromArray($schSizeRows, null, 'A10');
                    $endSS = 9 + count($schSizeRows);
                    $schSheet->getStyle("A10:G{$endSS}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $schSheet->getStyle("A10:G{$endSS}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    $curR = $endSS + 1;
                }

                // Total Row for Mini Size Matrix
                $schSheet->mergeCells("A{$curR}:B{$curR}");
                $schSheet->setCellValue("A{$curR}", 'TOTAL');
                $schSheet->setCellValue("C{$curR}", $totSL);
                $schSheet->setCellValue("D{$curR}", $totSP);
                $schSheet->setCellValue("E{$curR}", $totTL);
                $schSheet->setCellValue("F{$curR}", $totTP);
                $schSheet->setCellValue("G{$curR}", $totSchPsg);
                $schSheet->getStyle("A{$curR}:G{$curR}")->applyFromArray($totalRowStyle);
                $schSheet->getRowDimension($curR)->setRowHeight(22);

                // Section 2: Table of Recipients for this School
                $recHeaderRow = $curR + 3;
                $schSheet->setCellValue("A{$recHeaderRow}", 'B. DAFTAR LENGKAP PENERIMA SEPATU');
                $schSheet->getStyle("A{$recHeaderRow}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E293B');

                $rTblHead = $recHeaderRow + 1;
                $schSheet->setCellValue('A' . $rTblHead, 'No');
                $schSheet->setCellValue('B' . $rTblHead, 'Nama Lengkap');
                $schSheet->setCellValue('C' . $rTblHead, 'Jenis Kelamin');
                $schSheet->setCellValue('D' . $rTblHead, 'Jabatan');
                $schSheet->setCellValue('E' . $rTblHead, 'Kategori');
                $schSheet->setCellValue('F' . $rTblHead, 'Panjang Kaki (cm)');
                $schSheet->setCellValue('G' . $rTblHead, 'Ukuran Sepatu');
                $schSheet->getStyle("A{$rTblHead}:G{$rTblHead}")->applyFromArray($headerStyleEmerald);
                $schSheet->getRowDimension($rTblHead)->setRowHeight(24);

                $recDataRows = [];
                $noRec = 1;
                foreach ($schRecs as $rc) {
                    $recDataRows[] = [
                        $noRec++,
                        $this->sanitizeCellValue($rc->nama_lengkap),
                        $rc->jenis_kelamin,
                        $this->sanitizeCellValue($rc->jabatan ?? '-'),
                        ucfirst($rc->kategori),
                        $rc->panjang_kaki ?? '-',
                        $rc->ukuran_sepatu,
                    ];
                }

                $rDataStart = $rTblHead + 1;
                $rDataEnd = $rDataStart + count($recDataRows) - 1;

                if (!empty($recDataRows)) {
                    $schSheet->fromArray($recDataRows, null, "A{$rDataStart}");
                    $schSheet->getStyle("A{$rDataStart}:A{$rDataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $schSheet->getStyle("C{$rDataStart}:C{$rDataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $schSheet->getStyle("E{$rDataStart}:G{$rDataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $schSheet->getStyle("A{$rDataStart}:G{$rDataEnd}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
                }

                // Signatures Section (Mengetahui / Mengesahkan)
                $sigRow = max($rDataEnd, $rTblHead) + 3;
                $kotaDate = ($sch->kota ?? 'Indonesia') . ', ' . date('d F Y');
                $schSheet->setCellValue("E{$sigRow}", $kotaDate);
                $schSheet->setCellValue("B" . ($sigRow + 1), 'Mengetahui,');
                $schSheet->setCellValue("E" . ($sigRow + 1), 'Mengesahkan,');
                $schSheet->setCellValue("B" . ($sigRow + 2), 'Petugas Ukur / PIC');
                $schSheet->setCellValue("E" . ($sigRow + 2), 'Kepala Sekolah');

                $picName = $sch->pic_nama ? strtoupper($sch->pic_nama) : '(............................................)';
                $ksName = $sch->kepala_sekolah_nama ? strtoupper($sch->kepala_sekolah_nama) : '(............................................)';

                $schSheet->setCellValue("B" . ($sigRow + 6), $picName);
                $schSheet->setCellValue("E" . ($sigRow + 6), $ksName);
                $schSheet->getStyle("B" . ($sigRow + 6))->getFont()->setBold(true)->setUnderline(true);
                $schSheet->getStyle("E" . ($sigRow + 6))->getFont()->setBold(true)->setUnderline(true);

                // Column dimensions for School Sheet
                $schSheet->getColumnDimension('A')->setWidth(6);
                $schSheet->getColumnDimension('B')->setWidth(34);
                $schSheet->getColumnDimension('C')->setWidth(14);
                $schSheet->getColumnDimension('D')->setWidth(20);
                $schSheet->getColumnDimension('E')->setWidth(18);
                $schSheet->getColumnDimension('F')->setWidth(18);
                $schSheet->getColumnDimension('G')->setWidth(16);
            }
        }

        // ====================================================
        // SHEET TERAKHIR: DAFTAR SEMUA PENERIMA (MASTER LIST)
        // ====================================================
        $sheetMaster = $spreadsheet->createSheet();
        $sheetMaster->setTitle('Daftar Penerima');
        $sheetMaster->setShowGridlines(true);

        $sheetMaster->setCellValue('A1', 'No');
        $sheetMaster->setCellValue('B1', 'Sekolah');
        $sheetMaster->setCellValue('C1', 'Nama Lengkap');
        $sheetMaster->setCellValue('D1', 'Jenis Kelamin');
        $sheetMaster->setCellValue('E1', 'Jabatan');
        $sheetMaster->setCellValue('F1', 'Kategori');
        $sheetMaster->setCellValue('G1', 'Panjang Kaki (cm)');
        $sheetMaster->setCellValue('H1', 'Ukuran Sepatu');

        $sheetMaster->getStyle('A1:H1')->applyFromArray($headerStyleEmerald);
        $sheetMaster->getRowDimension(1)->setRowHeight(24);

        $recRows = [];
        $noRec = 1;
        foreach ($allRecipients as $r) {
            $schName = $schoolsMap[$r->school_id]->nama_sekolah ?? '-';
            $recRows[] = [
                $noRec++,
                $this->sanitizeCellValue($schName),
                $this->sanitizeCellValue($r->nama_lengkap),
                $r->jenis_kelamin,
                $this->sanitizeCellValue($r->jabatan ?? '-'),
                ucfirst($r->kategori),
                $r->panjang_kaki ?? '-',
                $r->ukuran_sepatu,
            ];
        }

        if (!empty($recRows)) {
            $sheetMaster->fromArray($recRows, null, 'A2');
            $endR2 = 1 + count($recRows);
            $sheetMaster->getStyle("A2:A{$endR2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetMaster->getStyle("D2:D{$endR2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetMaster->getStyle("F2:H{$endR2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Memory-safe styling: apply cell borders if under 2500 rows, otherwise apply clean outline border
            if (count($recRows) <= 2500) {
                $sheetMaster->getStyle("A2:H{$endR2}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            } else {
                $sheetMaster->getStyle("A2:H{$endR2}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            }
        }

        // Explicit Column Widths for Sheet Master
        $sheetMasterWidths = [
            'A' => 6,  // No
            'B' => 32, // Sekolah
            'C' => 32, // Nama Lengkap
            'D' => 14, // Jenis Kelamin
            'E' => 20, // Jabatan
            'F' => 14, // Kategori
            'G' => 18, // Panjang Kaki
            'H' => 16, // Ukuran Sepatu
        ];
        foreach ($sheetMasterWidths as $col => $w) {
            $sheetMaster->getColumnDimension($col)->setWidth($w);
        }

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
            $clean = 'Sekolah';
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

    /**
     * Convert 1-indexed column number to Excel column letter (1 -> A, 27 -> AA, etc.).
     */
    protected function getColumnLetter(int $colNumber): string
    {
        $letter = '';
        while ($colNumber > 0) {
            $remainder = ($colNumber - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $colNumber = (int)(($colNumber - $remainder) / 26);
        }
        return $letter;
    }
}
