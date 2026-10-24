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

    public function test_authenticated_user_can_access_shoe_recap_dashboard(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
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

    public function test_authenticated_user_can_export_pdf(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/shoes/export/pdf');
        $response->assertStatus(200);
        $this->assertStringContainsString('Rekap_Sepatu_', (string) $response->headers->get('content-disposition'));
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
