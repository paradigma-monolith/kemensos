<?php

namespace App\Http\Controllers;

use App\Services\GuruTendikComparisonService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuruTendikComparisonController extends Controller
{
    public function __construct(
        protected GuruTendikComparisonService $comparisonService
    ) {}

    /**
     * Display the Comparison Dashboard between Shoe Recap (Guru & Tendik) and Master Guru & Tendik.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'match_status' => $request->query('match_status', ''),
            'sekolah' => $request->query('sekolah', ''),
            'status_sekolah' => $request->query('status_sekolah', ''),
            'jenis_kelamin' => $request->query('jenis_kelamin', ''),
            'kategori' => $request->query('kategori', ''),
            'status_kepegawaian' => $request->query('status_kepegawaian', ''),
            'search' => $request->query('search', ''),
            'tab' => $request->query('tab', 'records'),
        ];

        $comparison = $this->comparisonService->compare($filters);

        // Paginate filtered individual rows (50 per page) to keep Inertia payload fast
        $perPage = 50;
        $currentPage = max(1, (int) $request->query('page', 1));
        $filteredRows = $comparison['filtered_rows'];
        $totalRows = count($filteredRows);
        $offset = ($currentPage - 1) * $perPage;
        $pagedItems = array_slice($filteredRows, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $pagedItems,
            $totalRows,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('GuruTendik/Comparison', [
            'summary' => $comparison['summary'],
            'schoolComparisons' => $comparison['school_comparisons'],
            'records' => $paginator,
            'filterOptions' => $comparison['filter_options'],
            'filters' => $filters,
        ]);
    }

    /**
     * Export the comparison report to an Excel (.xlsx) file.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $filters = [
            'match_status' => $request->query('match_status', ''),
            'sekolah' => $request->query('sekolah', ''),
            'status_sekolah' => $request->query('status_sekolah', ''),
            'jenis_kelamin' => $request->query('jenis_kelamin', ''),
            'kategori' => $request->query('kategori', ''),
            'status_kepegawaian' => $request->query('status_kepegawaian', ''),
            'search' => $request->query('search', ''),
        ];

        $spreadsheet = $this->comparisonService->generateComparisonExcel($filters);
        $fileName = 'PERBANDINGAN_SEPATU_VS_GURU_TENDIK_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

