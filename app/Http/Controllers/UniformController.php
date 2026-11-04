<?php

namespace App\Http\Controllers;

use App\Services\UniformExcelImportService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UniformController extends Controller
{
    public function __construct(
        protected UniformExcelImportService $uniformService
    ) {}

    /**
     * Parse uploaded Excel file and return recapitulation JSON structure.
     *
     * Supports:
     * - `file` or `excel_file` (uploaded file)
     * - `file_path` (path in storage)
     * - `metadata` (boolean query parameter to include school_name, total_rows, etc.)
     */
    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'nullable|file|mimes:xlsx,xls,csv|max:51200',
            'excel_file' => 'nullable|file|mimes:xlsx,xls,csv|max:51200',
            'file_path' => 'nullable|string',
            'metadata' => 'nullable|boolean',
        ]);

        $uploadedFile = $request->file('file') ?? $request->file('excel_file');
        $originalName = null;
        $tempPath = null;

        if ($uploadedFile) {
            $originalName = $uploadedFile->getClientOriginalName();
            $tempPath = $uploadedFile->getRealPath();
        } elseif ($request->filled('file_path')) {
            $tempPath = $request->input('file_path');
            $originalName = basename($tempPath);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Berkas Excel wajib diunggah (field: file atau excel_file).',
            ], 422);
        }

        try {
            if ($request->boolean('metadata')) {
                $result = $this->uniformService->parseWithMetadata($tempPath, $originalName);
            } else {
                $result = $this->uniformService->parse($tempPath, $originalName);
            }

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses data ukuran seragam: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import recapitulation data and store in storage.
     */
    public function import(Request $request): JsonResponse
    {
        return $this->parse($request);
    }
}

