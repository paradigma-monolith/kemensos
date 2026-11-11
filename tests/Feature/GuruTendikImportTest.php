<?php

namespace Tests\Feature;

use App\Models\GuruTendikRecord;
use App\Models\User;
use App\Services\GuruTendikExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class GuruTendikImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_guru_tendik_index(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/guru-tendik');
        $response->assertStatus(200);
    }

    public function test_imports_format_rekap_total_revisi_excel_matching_schema(): void
    {
        $user = User::factory()->create();
        $spreadsheet = new Spreadsheet();

        // Sheet 0: GURU,TENDIK (LAKI LAKI) — Header Row 3, Start Data Row 4
        $sheetLaki = $spreadsheet->getActiveSheet();
        $sheetLaki->setTitle('GURU,TENDIK (LAKI LAKI)');
        $sheetLaki->fromArray(
            [['No', 'Nama Lengkap', 'Jenis Kelamin', 'Status', 'Jabatan', 'Sekolah']],
            null,
            'A3'
        );
        // Row 4: explicit gender, Row 5: blank gender (uses default_values "Laki-laki")
        $sheetLaki->fromArray([
            [1, 'Ahmad Fauzi', 'Laki-laki', 'PNS', 'Guru Matematika', 'SRMP 8 CIMAHI'],
            [2, 'Budi Raharjo', '', 'PPPK', 'Tenaga Administrasi', 'SRMP 1 BANDUNG'],
        ], null, 'A4');

        // Sheet 1: GURU,TENDIK (PEREMPUAN) — Header Row 3, Start Data Row 4
        $sheetPerempuan = $spreadsheet->createSheet();
        $sheetPerempuan->setTitle('GURU,TENDIK (PEREMPUAN)');
        $sheetPerempuan->fromArray(
            [['No', 'Nama Lengkap', 'Jenis Kelamin', 'Status', 'Jabatan', 'Sekolah']],
            null,
            'A3'
        );
        // Row 4: blank gender (uses default_values "Perempuan")
        $sheetPerempuan->fromArray([
            [1, 'Siti Rahmawati', '', 'PNS', 'Guru Bahasa Indonesia', 'SRMP 8 CIMAHI'],
        ], null, 'A4');

        $tempPath = storage_path('app/test_format_rekap_total_revisi.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $service = app(GuruTendikExcelService::class);
        $summary = $service->import($tempPath, 'FORMAT REKAP TOTAL REVISI.xlsx', $user->id, 'AZRIEL');

        $this->assertEquals(3, $summary['total_data']);
        $this->assertEquals(2, $summary['total_laki']);
        $this->assertEquals(1, $summary['total_perempuan']);
        $this->assertEquals(2, $summary['total_sekolah']);

        $this->assertDatabaseHas('guru_tendik_records', [
            'nama_lengkap' => 'Budi Raharjo',
            'jenis_kelamin' => 'Laki-laki',
            'status' => 'PPPK',
            'jabatan' => 'Tenaga Administrasi',
            'sekolah' => 'SRMP 1 BANDUNG',
            'sheet_name' => 'GURU,TENDIK (LAKI LAKI)',
        ]);

        $this->assertDatabaseHas('guru_tendik_records', [
            'nama_lengkap' => 'Siti Rahmawati',
            'jenis_kelamin' => 'Perempuan',
            'status' => 'PNS',
            'jabatan' => 'Guru Bahasa Indonesia',
            'sekolah' => 'SRMP 8 CIMAHI',
            'sheet_name' => 'GURU,TENDIK (PEREMPUAN)',
        ]);

        @unlink($tempPath);
    }

    public function test_export_excel_and_deduplicate_option_for_guru_tendik(): void
    {
        $user = User::factory()->create();
        $service = app(GuruTendikExcelService::class);

        $batch = \App\Models\GuruTendikBatch::create([
            'file_name' => 'FORMAT REKAP TOTAL REVISI.xlsx',
            'imported_by' => $user->id,
        ]);

        // Duplicate name + school
        GuruTendikRecord::create([
            'batch_id' => $batch->id,
            'no' => 1,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'Laki-laki',
            'status' => 'PNS',
            'jabatan' => 'Guru',
            'sekolah' => 'SRMP 8 CIMAHI',
            'sheet_name' => 'GURU,TENDIK (LAKI LAKI)',
        ]);
        GuruTendikRecord::create([
            'batch_id' => $batch->id,
            'no' => 2,
            'nama_lengkap' => '  AHMAD   FAUZI ',
            'jenis_kelamin' => 'Laki-laki',
            'status' => 'PNS',
            'jabatan' => 'Guru',
            'sekolah' => 'SRMP 8 CIMAHI',
            'sheet_name' => 'GURU,TENDIK (LAKI LAKI)',
        ]);

        $stdBook = $service->generateExportExcel([], false);
        $this->assertEquals(5, $stdBook->getSheetByName('GURU,TENDIK (LAKI LAKI)')->getHighestDataRow()); // Row 3 header + 2 rows (4, 5)

        $dedupBook = $service->generateExportExcel([], true);
        $this->assertEquals(4, $dedupBook->getSheetByName('GURU,TENDIK (LAKI LAKI)')->getHighestDataRow()); // Row 3 header + 1 row (4)

        $res = $this->actingAs($user)->get('/guru-tendik/export/excel?deduplicate=1');
        $res->assertStatus(200);

        $tplRes = $this->actingAs($user)->get('/guru-tendik/template');
        $tplRes->assertStatus(200);
    }
}

