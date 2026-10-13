<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Product::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nama Produk',
            'Kategori',
            'Harga (USD)',
            'Stok',
            'Status',
            'Dibuat Melalui',
            'Tanggal Dibuat',
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->category,
            number_format($product->price, 2),
            $product->stock,
            $product->status,
            $product->created_by_channel,
            $product->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
