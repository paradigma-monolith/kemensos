<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    /**
     * Export products to Excel spreadsheet (.xlsx).
     */
    public function exportExcel(): BinaryFileResponse
    {
        return Excel::download(new ProductsExport, 'products-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export products to CSV file (.csv).
     */
    public function exportCsv(): BinaryFileResponse
    {
        return Excel::download(new ProductsExport, 'products-' . date('Y-m-d') . '.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Export products to formatted PDF report (.pdf).
     */
    public function exportPdf(): Response
    {
        $products = Product::all();
        $pdf = Pdf::loadView('exports.products-pdf', [
            'products' => $products,
            'generatedAt' => now(),
        ]);

        return $pdf->download('products-' . date('Y-m-d') . '.pdf');
    }
}
