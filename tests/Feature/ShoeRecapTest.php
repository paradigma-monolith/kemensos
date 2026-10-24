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

        // Check Rekap Ukuran Sepatu sheet: verify Ukuran Top is removed
        $rekapUkuranSheet = $spreadsheet->getSheetByName('Rekap Ukuran Sepatu');
        $this->assertNotNull($rekapUkuranSheet);
        $rekapUkuranValues = [];
        foreach ($rekapUkuranSheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $rekapUkuranValues[] = (string)$cell->getValue();
            }
        }
        $this->assertNotContains('Ukuran Top', $rekapUkuranValues);
        $this->assertContains('TABEL REKAPITULASI KEBUTUHAN SETIAP SEKOLAH', $rekapUkuranValues);

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

    public function test_authenticated_user_can_filter_shoes_and_dashboard_by_pic(): void
    {
        $user = User::factory()->create();
        $school1 = School::create([
            'nama_sekolah' => 'Sekolah Azriel',
            'pic_nama' => 'AZRIEL',
        ]);
        $school2 = School::create([
            'nama_sekolah' => 'Sekolah Wendy',
            'pic_nama' => 'WENDY',
        ]);

        $batch1 = ShoeImportBatch::create([
            'school_id' => $school1->id,
            'file_name' => 'azriel.xlsx',
            'file_path' => 'excel_imports/azriel.xlsx',
            'imported_by' => $user->id,
            'notes' => 'PIC: AZRIEL',
        ]);
        $batch2 = ShoeImportBatch::create([
            'school_id' => $school2->id,
            'file_name' => 'wendy.xlsx',
            'file_path' => 'excel_imports/wendy.xlsx',
            'imported_by' => $user->id,
            'notes' => 'PIC: WENDY',
        ]);

        ShoeRecipient::create([
            'school_id' => $school1->id,
            'batch_id' => $batch1->id,
            'nomor_urut' => 1,
            'nama_lengkap' => 'Siswa Azriel',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Siswa',
            'kategori' => 'siswa',
            'ukuran_sepatu' => 42,
        ]);
        ShoeRecipient::create([
            'school_id' => $school2->id,
            'batch_id' => $batch2->id,
            'nomor_urut' => 1,
            'nama_lengkap' => 'Siswa Wendy',
            'jenis_kelamin' => 'P',
            'jabatan' => 'Siswa',
            'kategori' => 'siswa',
            'ukuran_sepatu' => 38,
        ]);

        // Filter /shoes by PIC
        $shoesResponse = $this->actingAs($user)->get('/shoes?pic=AZRIEL');
        $shoesResponse->assertStatus(200);

        // Filter /dashboard by PIC
        $dashboardResponse = $this->actingAs($user)->get('/dashboard?pic=AZRIEL');
        $dashboardResponse->assertStatus(200);

        // Filter /api/shoes/dashboard-data by PIC
        $apiResponse = $this->actingAs($user)->get('/api/shoes/dashboard-data?pic=AZRIEL');
        $apiResponse->assertStatus(200);
        $data = $apiResponse->json();
        $this->assertEquals(1, $data['summary']['total_sekolah']);
        $this->assertEquals(1, $data['summary']['total_sepatu']);
        $this->assertEquals('AZRIEL', $data['filters']['pic']);
    }

    public function test_authenticated_user_can_update_school_pic(): void
    {
        $user = User::factory()->create();
        $school = School::create([
            'nama_sekolah' => 'Sekolah Baru',
            'pic_nama' => 'ADIL',
        ]);

        // Sending with or without PIC prefix automatically normalizes to person name
        $response = $this->actingAs($user)->patch("/shoes/schools/{$school->id}", [
            'pic_nama' => 'PIC RIVALDI',
            'kota' => 'Bandung',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'pic_nama' => 'RIVALDI',
            'kota' => 'Bandung',
        ]);
    }

    public function test_default_pics_list_matches_requirements(): void
    {
        $expectedPics = [
            'AZRIEL',
            'WENDY',
            'ADIL',
            'AYU',
            'DWI',
            'IKHSAN dan TRI',
            'INDRA',
            'RAHAYU',
            'MIFTAH',
            'RIVALDI',
            'YUPITA',
        ];

        $this->assertEquals($expectedPics, School::DEFAULT_PICS);
    }

    public function test_authenticated_user_can_download_original_batch_excel(): void
    {
        $user = User::factory()->create();
        $school = School::create([
            'nama_sekolah' => 'Sekolah Download',
            'pic_nama' => 'AZRIEL',
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'excel_test');
        file_put_contents($tempFile, 'dummy excel content');

        $batch = ShoeImportBatch::create([
            'school_id' => $school->id,
            'file_name' => 'sekolah_asli.xlsx',
            'file_path' => $tempFile,
            'imported_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/shoes/batches/{$batch->id}/download");
        $response->assertStatus(200);
        $this->assertStringContainsString('sekolah_asli.xlsx', (string) $response->headers->get('content-disposition'));

        @unlink($tempFile);
    }

    public function test_anomaly_detection_identifies_extreme_shoe_size_greater_than_100(): void
    {
        $user = User::factory()->create();
        $school = School::create(['nama_sekolah' => 'Sekolah Anomali']);
        $batch = ShoeImportBatch::create([
            'school_id' => $school->id,
            'file_name' => 'anomali.xlsx',
            'imported_by' => $user->id,
        ]);

        $normal = ShoeRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $batch->id,
            'nama_lengkap' => 'Normal User',
            'kategori' => 'siswa',
            'jenis_kelamin' => 'L',
            'ukuran_sepatu' => 38,
            'panjang_kaki' => 24.5,
        ]);

        $extreme = ShoeRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $batch->id,
            'nama_lengkap' => 'Extreme Anomaly User',
            'kategori' => 'siswa',
            'jenis_kelamin' => 'L',
            'ukuran_sepatu' => 250,
            'panjang_kaki' => 38,
        ]);

        $this->assertFalse($normal->is_anomaly);
        $this->assertTrue($extreme->is_anomaly);
        $this->assertStringContainsString('> 100', $extreme->anomaly_reason);

        // Test filtering by anomaly
        $response = $this->actingAs($user)->get('/shoes?anomaly=1');
        $response->assertStatus(200);

        // Test updating to fix anomaly
        $patchResponse = $this->actingAs($user)->patch("/shoes/recipients/{$extreme->id}", [
            'nama_lengkap' => 'Extreme Anomaly User',
            'jenis_kelamin' => 'L',
            'kategori' => 'siswa',
            'jabatan' => 'Siswa',
            'panjang_kaki' => 25.0,
            'ukuran_sepatu' => 38,
        ]);
        $patchResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shoe_recipients', [
            'id' => $extreme->id,
            'ukuran_sepatu' => 38,
            'panjang_kaki' => 25.0,
        ]);
    }
}

