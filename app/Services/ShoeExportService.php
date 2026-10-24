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
     * Generate an Excel spreadsheet with Recap Matrix and Detailed Recipient List.
     * Highly optimized for fast generation, low memory consumption, and formula-safe values.
     *
     * @param int|null $schoolId Filter by specific school or null for all schools
     * @return Spreadsheet
     */
    public function generateRecapExcel(?int $schoolId = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // ----------------------------------------------------
        // SHEET 1: REKAPITULASI UKURAN SEPATU (MATRIX VENDOR)
        // ----------------------------------------------------
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Ukuran Sepatu');

        $schoolName = 'SEMUA SEKOLAH RAKYAT';
        if ($schoolId) {
            $school = School::find($schoolId);
            if ($school) {
                $schoolName = strtoupper($school->nama_sekolah);
            }
        }

        // Title
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'REKAPITULASI KEBUTUHAN PENGADAAN SEPATU');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1E293B');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', $schoolName . ' — TAHUN ' . date('Y'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('475569');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Table
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Ukuran Sepatu');
        $sheet->setCellValue('C4', 'Siswa (L)');
        $sheet->setCellValue('D4', 'Siswa (P)');
        $sheet->setCellValue('E4', 'Guru & Tendik (L)');
        $sheet->setCellValue('F4', 'Guru & Tendik (P)');
        $sheet->setCellValue('G4', 'Total Pria');
        $sheet->setCellValue('H4', 'Total Wanita');
        $sheet->setCellValue('I4', 'TOTAL PASANG');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $sheet->getStyle('A4:I4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Fetch aggregated size breakdown
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

        $rows = [];
        $no = 1;
        $totSiswaL = 0; $totSiswaP = 0; $totStaffL = 0; $totStaffP = 0;
        $totPria = 0; $totWanita = 0; $grandTotal = 0;

        foreach ($matrix as $m) {
            $rows[] = [
                $no++,
                $m->ukuran_sepatu,
                $m->siswa_l,
                $m->siswa_p,
                $m->staff_l,
                $m->staff_p,
                $m->total_pria,
                $m->total_wanita,
                $m->total,
            ];

            $totSiswaL += $m->siswa_l;
            $totSiswaP += $m->siswa_p;
            $totStaffL += $m->staff_l;
            $totStaffP += $m->staff_p;
            $totPria += $m->total_pria;
            $totWanita += $m->total_wanita;
            $grandTotal += $m->total;
        }

        if (!empty($rows)) {
            $sheet->fromArray($rows, null, 'A5');
            $endRow = 4 + count($rows);
            $sheet->getStyle("A5:I{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A5:I{$endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $rowIdx = $endRow + 1;
        } else {
            $rowIdx = 5;
        }

        // Total Row
        $sheet->mergeCells("A{$rowIdx}:B{$rowIdx}");
        $sheet->setCellValue("A{$rowIdx}", 'TOTAL KESELURUHAN');
        $sheet->setCellValue("C{$rowIdx}", $totSiswaL);
        $sheet->setCellValue("D{$rowIdx}", $totSiswaP);
        $sheet->setCellValue("E{$rowIdx}", $totStaffL);
        $sheet->setCellValue("F{$rowIdx}", $totStaffP);
        $sheet->setCellValue("G{$rowIdx}", $totPria);
        $sheet->setCellValue("H{$rowIdx}", $totWanita);
        $sheet->setCellValue("I{$rowIdx}", $grandTotal);

        $totalStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        ];
        $sheet->getStyle("A{$rowIdx}:I{$rowIdx}")->applyFromArray($totalStyle);

        // Explicit Column Widths (Avoids extremely slow calculateColumnWidths / GD font metric inspection)
        $sheet1Widths = [
            'A' => 6,  // No
            'B' => 16, // Ukuran Sepatu
            'C' => 12, // Siswa (L)
            'D' => 12, // Siswa (P)
            'E' => 18, // Guru & Tendik (L)
            'F' => 18, // Guru & Tendik (P)
            'G' => 14, // Total Pria
            'H' => 14, // Total Wanita
            'I' => 16, // TOTAL PASANG
        ];
        foreach ($sheet1Widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ----------------------------------------------------
        // SHEET 2: DAFTAR LENGKAP PENERIMA
        // ----------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Daftar Penerima');

        $sheet2->setCellValue('A1', 'No');
        $sheet2->setCellValue('B1', 'Sekolah');
        $sheet2->setCellValue('C1', 'Nama Lengkap');
        $sheet2->setCellValue('D1', 'Jenis Kelamin');
        $sheet2->setCellValue('E1', 'Jabatan');
        $sheet2->setCellValue('F1', 'Kategori');
        $sheet2->setCellValue('G1', 'Panjang Kaki (cm)');
        $sheet2->setCellValue('H1', 'Ukuran Sepatu');

        $sheet2->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet2->getRowDimension(1)->setRowHeight(24);

        $recipientsQuery = ShoeRecipient::with('school')->orderBy('school_id')->orderBy('kategori')->orderBy('nomor_urut');
        if ($schoolId) {
            $recipientsQuery->where('school_id', $schoolId);
        }
        $recipients = $recipientsQuery->get();

        $recRows = [];
        $noRec = 1;
        foreach ($recipients as $r) {
            // Formula escaping: prepend single quote if text starts with formula prefix (=, +, -, @)
            $jabatan = (string)($r->jabatan ?? '-');
            if (isset($jabatan[0]) && in_array($jabatan[0], ['=', '+', '-', '@'])) {
                $jabatan = "'" . $jabatan;
            }

            $nama = (string)$r->nama_lengkap;
            if (isset($nama[0]) && in_array($nama[0], ['=', '+', '-', '@'])) {
                $nama = "'" . $nama;
            }

            $recRows[] = [
                $noRec++,
                $r->school?->nama_sekolah ?? '-',
                $nama,
                $r->jenis_kelamin,
                $jabatan,
                ucfirst($r->kategori),
                $r->panjang_kaki ?? '-',
                $r->ukuran_sepatu,
            ];
        }

        if (!empty($recRows)) {
            $sheet2->fromArray($recRows, null, 'A2');
            $endR2 = 1 + count($recRows);
            $sheet2->getStyle("A2:A{$endR2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("D2:D{$endR2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("F2:H{$endR2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("A2:H{$endR2}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
        }

        // Explicit Column Widths for Sheet 2
        $sheet2Widths = [
            'A' => 6,  // No
            'B' => 30, // Sekolah
            'C' => 32, // Nama Lengkap
            'D' => 14, // Jenis Kelamin
            'E' => 20, // Jabatan
            'F' => 14, // Kategori
            'G' => 18, // Panjang Kaki
            'H' => 16, // Ukuran Sepatu
        ];
        foreach ($sheet2Widths as $col => $w) {
            $sheet2->getColumnDimension($col)->setWidth($w);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }
}
