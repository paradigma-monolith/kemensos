<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'MacBook Pro M3 Max 16"',
                'category' => 'Hardware',
                'price' => 3899.00,
                'stock' => 14,
                'status' => 'active',
                'description' => 'Laptop bertenaga tinggi untuk developer dan creator profesional.',
                'created_by_channel' => 'System',
            ],
            [
                'name' => 'Logitech MX Master 3S',
                'category' => 'Hardware',
                'price' => 99.99,
                'stock' => 45,
                'status' => 'active',
                'description' => 'Wireless performance mouse dengan sensor 8K DPI dan tombol quiet clicks.',
                'created_by_channel' => 'System',
            ],
            [
                'name' => 'Dell UltraSharp 32" 4K Thunderbolt',
                'category' => 'Hardware',
                'price' => 849.50,
                'stock' => 20,
                'status' => 'active',
                'description' => 'Monitor IPS Black beresolusi tinggi dengan color coverage 98% DCI-P3.',
                'created_by_channel' => 'Inertia Bridge',
            ],
            [
                'name' => 'Laravel Forge Professional License',
                'category' => 'Cloud Service',
                'price' => 199.00,
                'stock' => 999,
                'status' => 'active',
                'description' => 'Manajemen server cloud otomatis untuk ekosistem Laravel modern.',
                'created_by_channel' => 'Inertia Bridge',
            ],
            [
                'name' => 'Vite Enterprise Build Optimization Suite',
                'category' => 'Software',
                'price' => 149.00,
                'stock' => 150,
                'status' => 'active',
                'description' => 'Toolset bundling super cepat untuk arsitektur SPA dan SSR.',
                'created_by_channel' => 'SPA API',
            ],
            [
                'name' => 'Keychron Q1 Pro Custom Mechanical Keyboard',
                'category' => 'Hardware',
                'price' => 219.00,
                'stock' => 8,
                'status' => 'active',
                'description' => 'Mechanical keyboard wireless QMK/VIA programmable dengan double gasket mount.',
                'created_by_channel' => 'SPA API',
            ],
        ];

        foreach ($products as $item) {
            Product::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
