<?php

namespace App\Services;

use Exception;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UniformExcelImportService
{
    /**
     * Default schema definition matching the specification:
     * - header_rows: [1, 2]
     * - data_start_row: 3
     * - 9 target sheets with columns and nested structures (atasan, bawahan, etc.)
     */
    public const SCHEMA = [
        'SISWA' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'jenjang' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'jenjang', 'label' => 'Jenjang (SD/SMP/SMA)', 'type' => 'string'],
                ['col' => 'E', 'index' => 4, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm) (ditambahkan 1 cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm) (ditambahkan 1 cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm) (ditambahkan 1 cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm) (ditambahkan 2 cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm) (ditambahkan 1 cm)', 'type' => 'number'],
                ['col' => 'J', 'index' => 9, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
        'KEPSEK' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'E', 'index' => 4, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
        'GURU LAMA' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'status' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'status', 'label' => 'Status (ASN / OUTSOURCHING / Tamu)', 'type' => 'string'],
                ['col' => 'E', 'index' => 4, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'J', 'index' => 9, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
        'GURU BARU' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'status' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'status', 'label' => 'Status (ASN / OUTSOURCHING / Tamu)', 'type' => 'string'],
                ['col' => 'E', 'index' => 4, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'J', 'index' => 9, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
        'TENDIK LAMA' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'status' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'status', 'label' => 'Status (ASN / OUTSOURCHING / Tamu)', 'type' => 'string'],
                ['col' => 'E', 'index' => 4, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'J', 'index' => 9, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
        'TENDIK BARU' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'status' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'status', 'label' => 'Status (ASN / OUTSOURCHING / Tamu)', 'type' => 'string'],
                ['col' => 'E', 'index' => 4, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'J', 'index' => 9, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
        'SATPAM' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'status' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                    'ukuran_sepatu_pdh' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'status', 'label' => 'Status (ASN / OUTSOURCHING)', 'type' => 'string'],
                ['col' => 'E', 'index' => 4, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'J', 'index' => 9, 'key' => 'ukuran_sepatu_pdh', 'group' => 'bawahan', 'label' => 'Ukuran Sepatu PDH', 'type' => 'integer'],
                ['col' => 'K', 'index' => 10, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
        'PETUGAS KEBERSIHAN' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'status' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'status', 'label' => 'Status (ASN / OUTSOURCHING)', 'type' => 'string'],
                ['col' => 'E', 'index' => 4, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'J', 'index' => 9, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
        'PETUGAS DAPUR' => [
            'structure' => [
                'no' => null,
                'nama_lengkap' => null,
                'jenis_kelamin' => null,
                'status' => null,
                'atasan' => [
                    'lingkar_badan_cm' => null,
                    'panjang_badan_cm' => null,
                ],
                'bawahan' => [
                    'lingkar_pinggang_cm' => null,
                    'panjang_celana_cm' => null,
                    'panjang_pesak_cm' => null,
                ],
                'konversi_vendor' => null,
            ],
            'columns' => [
                ['col' => 'A', 'index' => 0, 'key' => 'no', 'label' => 'No', 'type' => 'integer'],
                ['col' => 'B', 'index' => 1, 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'string'],
                ['col' => 'C', 'index' => 2, 'key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'string'],
                ['col' => 'D', 'index' => 3, 'key' => 'status', 'label' => 'Status (ASN / OUTSOURCHING)', 'type' => 'string'],
                ['col' => 'E', 'index' => 4, 'key' => 'lingkar_badan_cm', 'group' => 'atasan', 'label' => 'Lingkar Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'F', 'index' => 5, 'key' => 'panjang_badan_cm', 'group' => 'atasan', 'label' => 'Panjang Badan (Dalam Cm)', 'type' => 'number'],
                ['col' => 'G', 'index' => 6, 'key' => 'lingkar_pinggang_cm', 'group' => 'bawahan', 'label' => 'Lingkar Pinggang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'H', 'index' => 7, 'key' => 'panjang_celana_cm', 'group' => 'bawahan', 'label' => 'Panjang Celana (Dalam Cm)', 'type' => 'number'],
                ['col' => 'I', 'index' => 8, 'key' => 'panjang_pesak_cm', 'group' => 'bawahan', 'label' => '(Panjang Pesak) Selangkang (Dalam Cm)', 'type' => 'number'],
                ['col' => 'J', 'index' => 9, 'key' => 'konversi_vendor', 'label' => 'Konversi Vendor', 'type' => 'string'],
            ],
        ],
    ];

    /**
     * Import and parse Excel file returning the exact JSON structure specified:
     * {
     *   "SISWA": { "structure": {...}, "data": [...] },
     *   "KEPSEK": { "structure": {...}, "data": [...] },
     *   ...
     * }
     *
     * @param string $filePath Absolute or relative path to the Excel file
     * @param string|null $originalName Original file name (used for school name extraction)
     * @return array
     * @throws Exception
     */
    public function parse(string $filePath, ?string $originalName = null): array
    {
        $realPath = $this->resolvePath($filePath);

        $spreadsheet = IOFactory::load($realPath);
        $result = $this->initializeEmptyResult();

        $sheetNames = $spreadsheet->getSheetNames();

        foreach ($sheetNames as $sheetIndex => $sheetName) {
            $targetSheet = $this->resolveTargetSheetName($sheetName);
            if (!$targetSheet || !isset(self::SCHEMA[$targetSheet])) {
                continue;
            }

            $worksheet = $spreadsheet->getSheet($sheetIndex);
            $parsedRows = $this->parseWorksheet($worksheet, $targetSheet);

            foreach ($parsedRows as $row) {
                $result[$targetSheet]['data'][] = $row;
            }
        }

        return $result;
    }

    /**
     * Return recapitulation result formatted directly as a JSON string.
     *
     * @param string $filePath
     * @param string|null $originalName
     * @param int $flags
     * @return string
     */
    public function parseToJson(string $filePath, ?string $originalName = null, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->parse($filePath, $originalName), $flags);
    }

    /**
     * Parse Excel file and return metadata (school name, filename, summary counts) + full recapitulation.
     *
     * @param string $filePath
     * @param string|null $originalName
     * @return array
     */
    public function parseWithMetadata(string $filePath, ?string $originalName = null): array
    {
        $effectiveName = $originalName ?? basename($filePath);
        $schoolName = $this->extractSchoolName($effectiveName);
        $recap = $this->parse($filePath, $effectiveName);

        $totalRows = 0;
        $sheetCounts = [];
        foreach ($recap as $key => $sheet) {
            $count = count($sheet['data']);
            $sheetCounts[$key] = $count;
            $totalRows += $count;
        }

        return [
            'school_name' => $schoolName,
            'file_name' => $effectiveName,
            'header_rows' => [1, 2],
            'data_start_row' => 3,
            'total_rows' => $totalRows,
            'sheet_counts' => $sheetCounts,
            'recapitulation' => $recap,
        ];
    }

    /**
     * Extract school name based on the file name starting from the word SRD, SRMP, or SRMA.
     *
     * Rule:
     * "untuk nama sekolah berdasarkan nama file dimulai dari kata SRD, SRMP dan SRMA"
     *
     * Examples:
     * - "Data Ukuran Seragam SRMA 13 Bekasi (OK).xlsx" -> "SRMA 13 Bekasi"
     * - "161._Sekolah_Rakyat_Terpadu_SRMP_2_Bogor.xlsx" -> "SRMP 2 Bogor"
     * - "SRD 01 Bandung.xlsx"                          -> "SRD 01 Bandung"
     *
     * @param string $fileName
     * @return string
     */
    public function extractSchoolName(string $fileName): string
    {
        $clean = pathinfo($fileName, PATHINFO_FILENAME);

        // Normalize separators like underscores and multiple hyphens
        $clean = str_replace('_', ' ', $clean);

        // Match starting from SRD, SRMP, or SRMA (case-insensitive)
        if (preg_match('/\b(SRD|SRMP|SRMA)\b.*$/i', $clean, $matches)) {
            $school = $matches[0];
        } else {
            $school = $clean;
        }

        // Remove trailing tags such as (OK), (FINAL), (UPDATE), (REVISI), [OK], etc.
        $school = preg_replace('/\s*[\(\[]?(OK|FINAL|UPDATE|REVISI|VERIFIKASI|ACC)[\)\]]?/i', '', $school);
        // Remove trailing duplicate indicators like (1), (2)
        $school = preg_replace('/\s*\(\d+\)$/', '', $school);
        // Clean up redundant whitespace
        $school = trim(preg_replace('/\s+/', ' ', $school));

        return $school;
    }

    /**
     * Map Excel worksheet title to the 9 target recapitulation categories.
     *
     * Rule:
     * "catatan : jika nama sheetnya hanya GURU saja, maka data masuk ke sheet GURU BARU
     *  dan jika TENDIK saja maka masuk ke sheet TENDIK BARU"
     *
     * @param string $sheetName
     * @return string|null
     */
    public function resolveTargetSheetName(string $sheetName): ?string
    {
        $name = strtoupper(trim($sheetName));

        // Skip non-data recap/summary/chart sheets
        if (preg_match('/^(REKAP|REKAPITULASI|RINGKASAN|SUMMARY|GRAFIK)$/i', $name)) {
            return null;
        }

        // Exact match rules from specification
        if ($name === 'GURU') {
            return 'GURU BARU';
        }
        if ($name === 'TENDIK') {
            return 'TENDIK BARU';
        }

        // Variations containing GURU
        if (str_contains($name, 'GURU')) {
            if (str_contains($name, 'LAMA')) {
                return 'GURU LAMA';
            }
            if (str_contains($name, 'BARU')) {
                return 'GURU BARU';
            }
            // If only GURU or no qualifier, default to GURU BARU
            return 'GURU BARU';
        }

        // Variations containing TENDIK
        if (str_contains($name, 'TENDIK')) {
            if (str_contains($name, 'LAMA')) {
                return 'TENDIK LAMA';
            }
            if (str_contains($name, 'BARU')) {
                return 'TENDIK BARU';
            }
            // If only TENDIK or no qualifier, default to TENDIK BARU
            return 'TENDIK BARU';
        }

        // Kepala Sekolah
        if (str_contains($name, 'KEPSEK') || str_contains($name, 'KEPALA SEKOLAH')) {
            return 'KEPSEK';
        }

        // Siswa
        if (str_contains($name, 'SISWA') || str_contains($name, 'MURID') || str_contains($name, 'PESERTA DIDIK')) {
            return 'SISWA';
        }

        // Satpam / Security
        if (str_contains($name, 'SATPAM') || str_contains($name, 'SECURITY') || str_contains($name, 'PENGAMAN')) {
            return 'SATPAM';
        }

        // Petugas Kebersihan / Cleaning Service
        if (str_contains($name, 'KEBERSIHAN') || str_contains($name, 'CLEANING') || str_contains($name, 'OB')) {
            return 'PETUGAS KEBERSIHAN';
        }

        // Petugas Dapur / Chef
        if (str_contains($name, 'DAPUR') || str_contains($name, 'COOK') || str_contains($name, 'CHEF')) {
            return 'PETUGAS DAPUR';
        }

        return null;
    }

    /**
     * Initialize the blank recapitulation container with all 9 sheets and empty data arrays.
     */
    protected function initializeEmptyResult(): array
    {
        $result = [];
        foreach (self::SCHEMA as $sheetName => $config) {
            $result[$sheetName] = [
                'structure' => $config['structure'],
                'data' => [],
            ];
        }
        return $result;
    }

    /**
     * Parse rows of a worksheet dynamically or via configured column coordinates.
     */
    protected function parseWorksheet(Worksheet $worksheet, string $targetSheet): array
    {
        $config = self::SCHEMA[$targetSheet];
        $columns = $this->resolveColumnMap($worksheet, $config['columns']);
        $highestRow = $worksheet->getHighestRow();
        $startRow = 3; // data_start_row: 3

        $rows = [];
        $consecutiveEmpty = 0;

        for ($r = $startRow; $r <= $highestRow; $r++) {
            $record = $config['structure'];
            $hasData = false;

            foreach ($columns as $colDef) {
                $colLetter = $colDef['col'];
                $key = $colDef['key'];
                $type = $colDef['type'];
                $group = $colDef['group'] ?? null;

                $rawVal = $worksheet->getCell("{$colLetter}{$r}")->getValue();
                $val = $this->sanitizeValue($rawVal, $type);

                if ($group) {
                    $record[$group][$key] = $val;
                } else {
                    $record[$key] = $val;
                }

                if ($val !== null && $val !== '') {
                    $hasData = true;
                }
            }

            $nama = $record['nama_lengkap'] ?? null;

            // Skip empty rows or footer rows
            if (!$hasData || empty($nama) || $this->isFooterRow($nama)) {
                $consecutiveEmpty++;
                if ($consecutiveEmpty >= 10) {
                    break;
                }
                continue;
            }

            $consecutiveEmpty = 0;
            $rows[] = $record;
        }

        return $rows;
    }

    /**
     * Dynamically verify or adjust column mapping by inspecting header row (row 2).
     * Falls back to standard configured column letters if header labels are absent.
     */
    protected function resolveColumnMap(Worksheet $worksheet, array $defaultColumns): array
    {
        $highestCol = $worksheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestCol);

        $detectedLetters = [];

        // Scan row 2 (column header labels)
        for ($c = 1; $c <= min(25, $highestColIndex); $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $cellVal = strtolower(trim((string)$worksheet->getCell("{$colLetter}2")->getValue()));
            if (empty($cellVal)) {
                continue;
            }

            if (preg_match('/^(no|no\.|nomor)$/i', $cellVal)) {
                $detectedLetters['no'] = $colLetter;
            } elseif (str_contains($cellVal, 'nama')) {
                $detectedLetters['nama_lengkap'] = $colLetter;
            } elseif (str_contains($cellVal, 'kelamin') || $cellVal === 'jk' || $cellVal === 'l/p' || $cellVal === 'gender') {
                $detectedLetters['jenis_kelamin'] = $colLetter;
            } elseif (str_contains($cellVal, 'jenjang')) {
                $detectedLetters['jenjang'] = $colLetter;
            } elseif (str_contains($cellVal, 'status')) {
                $detectedLetters['status'] = $colLetter;
            } elseif (str_contains($cellVal, 'lingkar badan') || str_contains($cellVal, 'lingkar dada')) {
                $detectedLetters['lingkar_badan_cm'] = $colLetter;
            } elseif (str_contains($cellVal, 'panjang badan')) {
                $detectedLetters['panjang_badan_cm'] = $colLetter;
            } elseif (str_contains($cellVal, 'lingkar pinggang')) {
                $detectedLetters['lingkar_pinggang_cm'] = $colLetter;
            } elseif (str_contains($cellVal, 'panjang celana') || str_contains($cellVal, 'panjang rok')) {
                $detectedLetters['panjang_celana_cm'] = $colLetter;
            } elseif (str_contains($cellVal, 'pesak') || str_contains($cellVal, 'selangkang')) {
                $detectedLetters['panjang_pesak_cm'] = $colLetter;
            } elseif (str_contains($cellVal, 'sepatu') || str_contains($cellVal, 'pdh')) {
                $detectedLetters['ukuran_sepatu_pdh'] = $colLetter;
            } elseif (str_contains($cellVal, 'vendor') || str_contains($cellVal, 'konversi')) {
                $detectedLetters['konversi_vendor'] = $colLetter;
            }
        }

        // Apply detected letters where found, otherwise retain standard config
        $resolved = [];
        foreach ($defaultColumns as $colDef) {
            $key = $colDef['key'];
            if (isset($detectedLetters[$key])) {
                $colDef['col'] = $detectedLetters[$key];
            }
            $resolved[] = $colDef;
        }

        return $resolved;
    }

    /**
     * Clean and type-cast cell values based on type specification.
     */
    protected function sanitizeValue($val, string $type)
    {
        if ($val === null || $val === '') {
            return null;
        }

        if (is_string($val)) {
            $val = trim($val);
            if ($val === '' || $val === '-' || $val === 'N/A' || $val === 'null' || $val === 'None') {
                return null;
            }
        }

        switch ($type) {
            case 'integer':
                if (is_numeric($val)) {
                    return (int)$val;
                }
                if (preg_match('/\b(\d+)\b/', (string)$val, $m)) {
                    return (int)$m[1];
                }
                return null;

            case 'number':
                if (is_string($val)) {
                    $val = str_replace([' ', 'cm', 'CM', 'Cm', 'cm.', 'CM.'], '', $val);
                    $val = str_replace(',', '.', $val);
                }
                if (is_numeric($val)) {
                    return +$val;
                }
                return null;

            case 'string':
            default:
                return (string)$val;
        }
    }

    /**
     * Check if a text indicates a signature, total, or footer block.
     */
    protected function isFooterRow(string $val): bool
    {
        return (bool)preg_match('/^(JUMLAH|TOTAL|MENGETAHUI|KEPALA\s+SEKOLAH|NIP|TANGGAL|CATATAN|KETERANGAN|PETUGAS\s+UKUR)/i', trim($val));
    }

    /**
     * Safely normalize and verify file path.
     */
    protected function resolvePath(string $filePath): string
    {
        if (file_exists($filePath)) {
            return $filePath;
        }

        $candidates = [
            storage_path('app/' . $filePath),
            storage_path('app/private/' . $filePath),
            storage_path('app/excel_imports/' . basename($filePath)),
            base_path($filePath),
            public_path($filePath),
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                return $cand;
            }
        }

        throw new InvalidArgumentException("Berkas Excel tidak ditemukan pada path: {$filePath}");
    }
}

