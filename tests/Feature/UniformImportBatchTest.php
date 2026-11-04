<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\UniformImportBatch;
use App\Models\UniformRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UniformImportBatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function createSampleUniformExcel(string $schoolName = 'SRMA 13 Bekasi'): string
    {
        $spreadsheet = new Spreadsheet();

        // 1. Sheet SISWA
        $sheetSiswa = $spreadsheet->getActiveSheet();
        $sheetSiswa->setTitle('SISWA');
        $sheetSiswa->setCellValue('A1', 'INFORMASI DASAR');
        $sheetSiswa->setCellValue('E1', 'ATASAN');
        $sheetSiswa->setCellValue('G1', 'BAWAHAN');
        $sheetSiswa->setCellValue('J1', 'VENDOR');

        $headers = ['No', 'Nama Lengkap', 'Jenis Kelamin', 'Jenjang', 'Lingkar Badan', 'Panjang Badan', 'Lingkar Pinggang', 'Panjang Celana', 'Panjang Pesak', 'Konversi Vendor'];
        foreach ($headers as $idx => $h) {
            $col = chr(65 + $idx);
            $sheetSiswa->setCellValue("{$col}2", $h);
        }

        $sheetSiswa->setCellValue('A3', 1);
        $sheetSiswa->setCellValue('B3', 'Ahmad Dhani');
        $sheetSiswa->setCellValue('C3', 'L');
        $sheetSiswa->setCellValue('D3', 'SMA');
        $sheetSiswa->setCellValue('E3', 90);
        $sheetSiswa->setCellValue('F3', 70);
        $sheetSiswa->setCellValue('G3', 80);
        $sheetSiswa->setCellValue('H3', 95);
        $sheetSiswa->setCellValue('I3', 25);
        $sheetSiswa->setCellValue('J3', 'M');

        // 2. Sheet GURU (single GURU should route to GURU BARU)
        $sheetGuru = $spreadsheet->createSheet();
        $sheetGuru->setTitle('GURU');
        $sheetGuru->setCellValue('A3', 1);
        $sheetGuru->setCellValue('B3', 'Siti Rahma');
        $sheetGuru->setCellValue('C3', 'P');
        $sheetGuru->setCellValue('D3', 'ASN');
        $sheetGuru->setCellValue('E3', 95);
        $sheetGuru->setCellValue('F3', 72);
        $sheetGuru->setCellValue('G3', 85);
        $sheetGuru->setCellValue('H3', 98);
        $sheetGuru->setCellValue('I3', 27);
        $sheetGuru->setCellValue('J3', 'L');

        $filePath = storage_path('app/test_uniform_' . uniqid() . '.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    public function test_can_view_uniforms_index_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('uniforms.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Uniforms/Index'));
    }

    public function test_can_import_uniform_excel_and_creates_batch_and_recipients(): void
    {
        $filePath = $this->createSampleUniformExcel('SRMA 13 Bekasi');
        $fileName = 'Data Ukuran Seragam SRMA 13 Bekasi (OK).xlsx';

        $uploadedFile = new UploadedFile(
            $filePath,
            $fileName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->user)->post(route('uniforms.import'), [
            'excel_files' => [$uploadedFile],
            'pic_nama' => 'YULIA',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check School created
        $school = School::where('nama_sekolah', 'SRMA 13 Bekasi')->first();
        $this->assertNotNull($school);
        $this->assertEquals('YULIA', $school->pic_nama);

        // Check Batch created
        $batch = UniformImportBatch::where('school_id', $school->id)->first();
        $this->assertNotNull($batch);
        $this->assertEquals($fileName, $batch->file_name);
        $this->assertEquals('YULIA', $batch->pic_nama);
        $this->assertEquals(2, $batch->total_rows);
        $this->assertEquals(1, $batch->total_siswa);
        $this->assertEquals(1, $batch->total_guru_baru); // GURU routed to GURU BARU

        // Check Recipients created
        $this->assertDatabaseHas('uniform_recipients', [
            'batch_id' => $batch->id,
            'sheet_name' => 'SISWA',
            'nama_lengkap' => 'Ahmad Dhani',
            'konversi_vendor' => 'M',
        ]);

        $this->assertDatabaseHas('uniform_recipients', [
            'batch_id' => $batch->id,
            'sheet_name' => 'GURU BARU',
            'nama_lengkap' => 'Siti Rahma',
            'konversi_vendor' => 'L',
        ]);

        @unlink($filePath);
    }

    public function test_can_download_batch_excel_file(): void
    {
        $school = School::create(['nama_sekolah' => 'SRMP 2 Bogor', 'pic_nama' => 'AZRIEL']);
        $batch = UniformImportBatch::create([
            'school_id' => $school->id,
            'school_name' => 'SRMP 2 Bogor',
            'file_name' => 'SRMP_2_Bogor.xlsx',
            'file_path' => 'uniform_imports/fake_test.xlsx',
            'pic_nama' => 'AZRIEL',
            'total_rows' => 10,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put('uniform_imports/fake_test.xlsx', 'test content');

        $response = $this->actingAs($this->user)->get(route('uniforms.batch.download', $batch));
        $response->assertStatus(200);

        \Illuminate\Support\Facades\Storage::disk('local')->delete('uniform_imports/fake_test.xlsx');
    }

    public function test_can_delete_uniform_batch(): void
    {
        $school = School::create(['nama_sekolah' => 'SRD 01 Bandung', 'pic_nama' => 'WENDY']);
        $batch = UniformImportBatch::create([
            'school_id' => $school->id,
            'school_name' => 'SRD 01 Bandung',
            'file_name' => 'SRD_01_Bandung.xlsx',
            'total_rows' => 5,
        ]);

        UniformRecipient::create([
            'school_id' => $school->id,
            'batch_id' => $batch->id,
            'sheet_name' => 'SISWA',
            'nama_lengkap' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->actingAs($this->user)->delete(route('uniforms.batch.destroy', $batch));
        $response->assertRedirect(route('uniforms.index', ['tab' => 'batches']));

        $this->assertDatabaseMissing('uniform_import_batches', ['id' => $batch->id]);
        $this->assertDatabaseMissing('uniform_recipients', ['nama_lengkap' => 'Budi Santoso']);
    }

    public function test_can_export_uniform_recap_excel(): void
    {
        $school = School::create(['nama_sekolah' => 'SRMA 13 Bekasi', 'pic_nama' => 'YULIA']);
        $batch = UniformImportBatch::create([
            'school_id' => $school->id,
            'school_name' => 'SRMA 13 Bekasi',
            'file_name' => 'Data_Ukuran_Seragam_SRMA_13_Bekasi.xlsx',
            'pic_nama' => 'YULIA',
            'total_rows' => 3,
            'total_siswa' => 1,
            'total_satpam' => 1,
            'recap_data' => [
                'recapitulation' => [
                    'SISWA' => [
                        'count' => 1,
                        'data' => [
                            [
                                'no' => 1,
                                'nama_lengkap' => 'Ahmad Siswa',
                                'jenis_kelamin' => 'L',
                                'jenjang' => 'SMA',
                                'atasan' => ['lingkar_badan_cm' => 90, 'panjang_badan_cm' => 65],
                                'bawahan' => ['lingkar_pinggang_cm' => 75, 'panjang_celana_cm' => 95, 'panjang_pesak_cm' => 60],
                                'konversi_vendor' => 'L',
                            ]
                        ]
                    ],
                    'SATPAM' => [
                        'count' => 1,
                        'data' => [
                            [
                                'no' => 1,
                                'nama_lengkap' => 'Bambang Satpam',
                                'jenis_kelamin' => 'L',
                                'status' => 'ASN',
                                'atasan' => ['lingkar_badan_cm' => 100, 'panjang_badan_cm' => 70],
                                'bawahan' => ['lingkar_pinggang_cm' => 85, 'panjang_celana_cm' => 100, 'panjang_pesak_cm' => 65, 'ukuran_sepatu_pdh' => 42],
                                'konversi_vendor' => 'XL',
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($this->user)->get(route('uniforms.export.excel', ['batch_id' => $batch->id]));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'spreadsheetml'));

        // Capture streamed content and read with PhpSpreadsheet
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $tmpFile = storage_path('app/temp_export_test.xlsx');
        file_put_contents($tmpFile, $content);

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($tmpFile);

        // Check SUMMARY sheet exists and is index 0
        $this->assertEquals('SUMMARY', $spreadsheet->getSheet(0)->getTitle());

        $summarySheet = $spreadsheet->getSheetByName('SUMMARY');
        $this->assertNotNull($summarySheet);

        // Check Title and Meta
        $this->assertEquals('REKAPITULASI KEBUTUHAN DATA UKURAN SERAGAM SEKOLAH RAKYAT', $summarySheet->getCell('A2')->getValue());
        $this->assertEquals('SRMA 13 Bekasi', $summarySheet->getCell('B5')->getValue());
        $this->assertEquals('YULIA', $summarySheet->getCell('B6')->getValue());

        // Check that category sheets are also present
        $this->assertNotNull($spreadsheet->getSheetByName('SISWA'));
        $this->assertNotNull($spreadsheet->getSheetByName('SATPAM'));

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        @unlink($tmpFile);
    }
}
