<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\ShoeImportBatch;
use App\Models\ShoeRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShoeRecapTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_interactive_dashboard(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);

        // Also test root landing
        $homeResponse = $this->actingAs($user)->get('/');
        $homeResponse->assertStatus(200);
    }

    public function test_authenticated_user_can_access_shoes_data_index(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/shoes');
        $response->assertStatus(200);
    }

    public function test_dashboard_api_returns_comparative_data(): void
    {
        $user = User::factory()->create();
        $school = School::create([
            'nama_sekolah' => 'Sekolah Rakyat Cimahi',
            'kota' => 'Cimahi',
        ]);
        $batch = ShoeImportBatch::create([
            'school_id' => $school->id,
            'file_name' => 'test.xlsx',
            'file_path' => 'excel_imports/test.xlsx',
            'imported_by' => $user->id,
        ]);
        ShoeRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $batch->id,
            'nomor_urut' => 1,
            'nama_lengkap' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Siswa',
            'kategori' => 'siswa',
            'ukuran_sepatu' => 40,
        ]);

        $response = $this->actingAs($user)->get('/api/shoes/dashboard-data');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => [
                'total_sepatu',
                'total_sekolah',
                'total_siswa',
                'total_guru',
                'total_tendik',
                'total_pria',
                'total_wanita',
                'top_size',
            ],
            'size_distribution',
            'size_groups',
            'category_distribution',
            'gender_distribution',
            'schools',
            'comparator',
            'filters',
        ]);
    }

    public function test_authenticated_user_can_export_excel_fast(): void
    {
        $user = User::factory()->create();
        $school = School::create([
            'nama_sekolah' => 'Sekolah Rakyat Cimahi',
            'kota' => 'Cimahi',
        ]);
        $batch = ShoeImportBatch::create([
            'school_id' => $school->id,
            'file_name' => 'test.xlsx',
            'file_path' => 'excel_imports/test.xlsx',
            'imported_by' => $user->id,
        ]);
        ShoeRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $batch->id,
            'nomor_urut' => 1,
            'nama_lengkap' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Siswa',
            'kategori' => 'siswa',
            'ukuran_sepatu' => 40,
        ]);

        $startTime = microtime(true);
        $response = $this->actingAs($user)->get('/shoes/export/excel');
        $duration = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $duration, 'Excel export took more than 2 seconds!');
        $this->assertStringContainsString('Rekap_Sepatu_', (string) $response->headers->get('content-disposition'));
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_excel_export_contains_per_school_tables_and_sheets(): void
    {
        $school1 = School::create([
            'nama_sekolah' => 'SR Cimahi',
            'kota' => 'Cimahi',
            'kepala_sekolah_nama' => 'Dr. Ikhsan',
            'pic_nama' => 'Heni',
        ]);
        $school2 = School::create([
            'nama_sekolah' => 'SR Rembang',
            'kota' => 'Rembang',
            'kepala_sekolah_nama' => 'Bambang, M.Pd.',
            'pic_nama' => 'Siti',
        ]);

        $user = User::factory()->create();
        $batch = ShoeImportBatch::create([
            'school_id' => $school1->id,
            'file_name' => 'test.xlsx',
            'file_path' => 'excel_imports/test.xlsx',
            'imported_by' => $user->id,
        ]);

        ShoeRecipient::create([
            'school_id' => $school1->id,
            'batch_id' => $batch->id,
            'nomor_urut' => 1,
            'nama_lengkap' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Siswa',
            'kategori' => 'siswa',
            'ukuran_sepatu' => 40,
        ]);
        ShoeRecipient::create([
            'school_id' => $school2->id,
            'batch_id' => $batch->id,
            'nomor_urut' => 1,
            'nama_lengkap' => 'Ani Wijaya',
            'jenis_kelamin' => 'P',
            'jabatan' => 'Guru',
            'kategori' => 'guru',
            'ukuran_sepatu' => 38,
        ]);

        $exportService = app(\App\Services\ShoeExportService::class);
        $spreadsheet = $exportService->generateRecapExcel();

        $sheetNames = $spreadsheet->getSheetNames();
        $this->assertContains('Rekap Ukuran Sepatu', $sheetNames);
        $this->assertContains('Rekap Per Sekolah', $sheetNames);
        $this->assertContains('SR Cimahi', $sheetNames);
        $this->assertContains('SR Rembang', $sheetNames);
        $this->assertContains('Daftar Penerima', $sheetNames);

        // Check Rekap Per Sekolah content
        $rekapPerSchoolSheet = $spreadsheet->getSheetByName('Rekap Per Sekolah');
        $this->assertNotNull($rekapPerSchoolSheet);
        $this->assertEquals('REKAPITULASI KEBUTUHAN PENGADAAN SEPATU PER SEKOLAH', $rekapPerSchoolSheet->getCell('A1')->getValue());
        $this->assertEquals('SR Cimahi', $rekapPerSchoolSheet->getCell('B5')->getValue());
        $this->assertEquals('SR Rembang', $rekapPerSchoolSheet->getCell('B6')->getValue());

        // Check School 1 sheet content
        $cimahiSheet = $spreadsheet->getSheetByName('SR Cimahi');
        $this->assertNotNull($cimahiSheet);
        $this->assertEquals('DATA REKAPITULASI & PENERIMA SEPATU', $cimahiSheet->getCell('A1')->getValue());
        $this->assertEquals('Nama Lengkap', $cimahiSheet->getCell('B15')->getValue()); // table header
        $this->assertEquals('Budi Santoso', $cimahiSheet->getCell('B16')->getValue()); // recipient row data
    }

    public function test_authenticated_user_can_export_pdf(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/shoes/export/pdf');
        $response->assertStatus(200);
        $this->assertStringContainsString('Rekap_Sepatu_', (string) $response->headers->get('content-disposition'));
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
