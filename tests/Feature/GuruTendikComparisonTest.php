<?php

namespace Tests\Feature;

use App\Models\GuruTendikBatch;
use App\Models\GuruTendikRecord;
use App\Models\School;
use App\Models\ShoeImportBatch;
use App\Models\ShoeRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruTendikComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparison_page_compares_guru_tendik_shoe_recap_with_master_database(): void
    {
        $user = User::factory()->create();

        // 1. Seed Master Guru & Tendik (using abbreviated school name "SRMP 8 CIMAHI")
        $gtBatch = GuruTendikBatch::create([
            'file_name' => 'FORMAT REKAP TOTAL REVISI.xlsx',
            'total_laki' => 2,
            'total_perempuan' => 1,
            'total_data' => 3,
            'imported_by' => $user->id,
        ]);

        // Person 1: Exact match
        GuruTendikRecord::create([
            'batch_id' => $gtBatch->id,
            'sheet_name' => 'GURU,TENDIK (LAKI LAKI)',
            'no' => 1,
            'nama_lengkap' => 'Ananda Nur Syamsi, S.Pd.',
            'jenis_kelamin' => 'Laki-laki',
            'status' => 'ASN',
            'jabatan' => 'Guru',
            'sekolah' => 'SRMP 8 CIMAHI',
        ]);

        // Person 2: Similar/Prefix match ("Sopyan" vs "Sopyan Iskandar")
        GuruTendikRecord::create([
            'batch_id' => $gtBatch->id,
            'sheet_name' => 'GURU,TENDIK (LAKI LAKI)',
            'no' => 2,
            'nama_lengkap' => 'Sopyan',
            'jenis_kelamin' => 'Laki-laki',
            'status' => 'PPPK',
            'jabatan' => 'Tendik',
            'sekolah' => 'SRMP 8 CIMAHI',
        ]);

        // Person 3: Missing shoe data (Belum Ada Data Sepatu)
        GuruTendikRecord::create([
            'batch_id' => $gtBatch->id,
            'sheet_name' => 'GURU,TENDIK (PEREMPUAN)',
            'no' => 1,
            'nama_lengkap' => 'Siti Belum Ukur',
            'jenis_kelamin' => 'Perempuan',
            'status' => 'PNS',
            'jabatan' => 'Guru',
            'sekolah' => 'SRMP 8 CIMAHI',
        ]);

        // 2. Seed Shoe Recap (using full school name "SEKOLAH RAKYAT MENENGAH PERTAMA 8 CIMAHI")
        $school = School::create([
            'nama_sekolah' => 'SEKOLAH RAKYAT MENENGAH PERTAMA 8 CIMAHI',
            'kota' => 'Cimahi',
            'pic_nama' => 'HENI',
            'status_konfirmasi' => 'terkonfirmasi',
        ]);

        $shoeBatch = ShoeImportBatch::create([
            'school_id' => $school->id,
            'file_name' => 'KONFIRMASI SEPATU SRMP 8 CIMAHI.xlsx',
            'imported_by' => $user->id,
            'total_siswa' => 1,
            'total_guru' => 1,
            'total_tendik' => 2,
            'total_sepatu' => 4,
        ]);

        // Match 1 (Guru)
        ShoeRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $shoeBatch->id,
            'kategori' => 'guru',
            'nomor_urut' => 1,
            'nama_lengkap' => 'Ananda Nur Syamsi',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Guru',
            'panjang_kaki' => 25.0,
            'ukuran_sepatu' => 39,
            'sheet_source' => 'GURU,TENDIK (LAKI LAKI)',
        ]);

        // Match 2 (Tendik - prefix match)
        ShoeRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $shoeBatch->id,
            'kategori' => 'tendik',
            'nomor_urut' => 2,
            'nama_lengkap' => 'Sopyan Iskandar',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Tendik',
            'panjang_kaki' => 27.0,
            'ukuran_sepatu' => 42,
            'sheet_source' => 'GURU,TENDIK (LAKI LAKI)',
        ]);

        // Only in Shoe Recap (Hanya di Rekap Sepatu)
        ShoeRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $shoeBatch->id,
            'kategori' => 'tendik',
            'nomor_urut' => 3,
            'nama_lengkap' => 'Budi Tambahan Baru',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Tendik',
            'panjang_kaki' => 26.5,
            'ukuran_sepatu' => 41,
            'sheet_source' => 'GURU,TENDIK (LAKI LAKI)',
        ]);

        // Student record (MUST be ignored in Guru & Tendik comparison)
        ShoeRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $shoeBatch->id,
            'kategori' => 'siswa',
            'nomor_urut' => 1,
            'nama_lengkap' => 'Ahmad Siswa',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Siswa',
            'panjang_kaki' => 23.0,
            'ukuran_sepatu' => 37,
            'sheet_source' => 'SRMP (LAKI LAKI)',
        ]);

        $response = $this->actingAs($user)->get('/guru-tendik-comparison');
        $response->assertStatus(200);

        $pageProps = $response->viewData('page')['props'];
        $summary = $pageProps['summary'];

        $this->assertEquals(3, $summary['total_master']);
        $this->assertEquals(3, $summary['total_shoe_recap']); // 3 staff, 1 student excluded
        $this->assertEquals(2, $summary['sudah_ada_data']);
        $this->assertEquals(1, $summary['belum_ada_data']);
        $this->assertEquals(1, $summary['hanya_di_sepatu']);

        // Verify export excel endpoint works
        $exportResponse = $this->actingAs($user)->get('/guru-tendik-comparison/export/excel');
        $exportResponse->assertStatus(200);
    }
}

