<?php

namespace Tests\Unit;

use App\Services\UniformExcelImportService;
use Tests\TestCase;

class UniformExcelImportServiceTest extends TestCase
{
    protected UniformExcelImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UniformExcelImportService();
    }

    public function test_extracts_school_name_starting_from_srd_srmp_srma(): void
    {
        $this->assertEquals(
            'SRMA 13 Bekasi',
            $this->service->extractSchoolName('Data Ukuran Seragam SRMA 13 Bekasi (OK).xlsx')
        );

        $this->assertEquals(
            'SRMP 2 Bogor',
            $this->service->extractSchoolName('161._Sekolah_Rakyat_Terpadu_SRMP_2_Bogor.xlsx')
        );

        $this->assertEquals(
            'SRD 01 Bandung',
            $this->service->extractSchoolName('Data Ukuran Seragam SRD 01 Bandung.xlsx')
        );

        $this->assertEquals(
            'SRMP 8 CIMAHI',
            $this->service->extractSchoolName('KONFIRMASI SEPATU SRMP 8 CIMAHI (OK).xlsx')
        );
    }

    public function test_resolves_sheet_names_with_special_rules(): void
    {
        // GURU only -> GURU BARU
        $this->assertEquals('GURU BARU', $this->service->resolveTargetSheetName('GURU'));
        $this->assertEquals('GURU BARU', $this->service->resolveTargetSheetName('guru'));

        // TENDIK only -> TENDIK BARU
        $this->assertEquals('TENDIK BARU', $this->service->resolveTargetSheetName('TENDIK'));
        $this->assertEquals('TENDIK BARU', $this->service->resolveTargetSheetName('tendik'));

        // GURU LAMA / BARU
        $this->assertEquals('GURU LAMA', $this->service->resolveTargetSheetName('GURU LAMA'));
        $this->assertEquals('GURU BARU', $this->service->resolveTargetSheetName('GURU BARU'));

        // TENDIK LAMA / BARU
        $this->assertEquals('TENDIK LAMA', $this->service->resolveTargetSheetName('TENDIK LAMA'));
        $this->assertEquals('TENDIK BARU', $this->service->resolveTargetSheetName('TENDIK BARU'));

        // Standard sheets
        $this->assertEquals('SISWA', $this->service->resolveTargetSheetName('SISWA'));
        $this->assertEquals('KEPSEK', $this->service->resolveTargetSheetName('KEPSEK'));
        $this->assertEquals('SATPAM', $this->service->resolveTargetSheetName('SATPAM'));
        $this->assertEquals('PETUGAS KEBERSIHAN', $this->service->resolveTargetSheetName('PETUGAS KEBERSIHAN'));
        $this->assertEquals('PETUGAS DAPUR', $this->service->resolveTargetSheetName('PETUGAS DAPUR'));

        // Summary skipped
        $this->assertNull($this->service->resolveTargetSheetName('REKAP'));
        $this->assertNull($this->service->resolveTargetSheetName('REKAPITULASI'));
    }

    public function test_schema_contains_all_nine_target_sheets_with_correct_structure(): void
    {
        $schema = UniformExcelImportService::SCHEMA;
        $expectedSheets = [
            'SISWA',
            'KEPSEK',
            'GURU LAMA',
            'GURU BARU',
            'TENDIK LAMA',
            'TENDIK BARU',
            'SATPAM',
            'PETUGAS KEBERSIHAN',
            'PETUGAS DAPUR',
        ];

        foreach ($expectedSheets as $sheet) {
            $this->assertArrayHasKey($sheet, $schema);
            $this->assertArrayHasKey('structure', $schema[$sheet]);
            $this->assertArrayHasKey('columns', $schema[$sheet]);
        }

        // Check nested structure
        $this->assertArrayHasKey('atasan', $schema['SISWA']['structure']);
        $this->assertArrayHasKey('bawahan', $schema['SISWA']['structure']);
        $this->assertArrayHasKey('ukuran_sepatu_pdh', $schema['SATPAM']['structure']['bawahan']);
    }
}

