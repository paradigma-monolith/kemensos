<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\UniformExcelImportService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UniformController extends Controller
{
    public function __construct(
        protected UniformExcelImportService $uniformService
    ) {}

    /**
     * Display the uniform recapitulation dashboard and import view.
     */
    public function index(Request $request): Response
    {
        $schema = UniformExcelImportService::SCHEMA;
        $initialData = [];
        foreach ($schema as $key => $conf) {
            $initialData[$key] = [
                'structure' => $conf['structure'],
                'data' => [],
            ];
        }

        $sessionRecap = session('uniform_recap');

        // Master PIC list combining default constant and any existing database values
        $existingPics = School::whereNotNull('pic_nama')
            ->where('pic_nama', '!=', '')
            ->distinct()
            ->pluck('pic_nama')
            ->map(fn($p) => School::normalizePicName($p))
            ->filter()
            ->toArray();
        $allPics = array_values(array_unique(array_merge(School::DEFAULT_PICS, $existingPics)));
        sort($allPics);

        return Inertia::render('Uniforms/Index', [
            'initialRecap' => $sessionRecap ?? [
                'school_name' => null,
                'file_name' => null,
                'pic_nama' => null,
                'header_rows' => [1, 2],
                'data_start_row' => 3,
                'total_rows' => 0,
                'sheet_counts' => array_fill_keys(array_keys($schema), 0),
                'recapitulation' => $initialData,
            ],
            'schema' => $schema,
            'picList' => $allPics,
        ]);
    }

    /**
     * Parse uploaded Excel file and return recapitulation JSON structure or redirect in Inertia.
     */
    public function parse(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|max:51200',
            'excel_file' => 'nullable|file|max:51200',
            'file_path' => 'nullable|string',
            'pic_nama' => 'nullable|string|max:100',
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
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Berkas Excel wajib diunggah (field: file atau excel_file).',
                ], 422);
            }
            return back()->withErrors(['file' => 'Berkas Excel wajib diunggah.']);
        }

        $selectedPic = School::normalizePicName($request->input('pic_nama'));

        try {
            $resultWithMeta = $this->uniformService->parseWithMetadata($tempPath, $originalName, $selectedPic);

            if ($request->wantsJson() || $request->is('api/*')) {
                if ($request->boolean('metadata')) {
                    return response()->json($resultWithMeta);
                }
                return response()->json($resultWithMeta['recapitulation']);
            }

            $picInfo = $resultWithMeta['pic_nama'] ? " (PIC: {$resultWithMeta['pic_nama']})" : "";

            return redirect()->route('uniforms.index')
                ->with('uniform_recap', $resultWithMeta)
                ->with('success', "Data ukuran seragam untuk {$resultWithMeta['school_name']}{$picInfo} berhasil diimpor ({$resultWithMeta['total_rows']} baris data teridentifikasi).");
        } catch (Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal memproses data ukuran seragam: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['file' => 'Gagal memproses data ukuran seragam: ' . $e->getMessage()]);
        }
    }

    /**
     * Import recapitulation data and store or return parsed result.
     */
    public function import(Request $request)
    {
        return $this->parse($request);
    }
}
