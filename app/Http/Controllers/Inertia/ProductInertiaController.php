<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductInertiaController extends Controller
{
    /**
     * Display a listing of products via Inertia.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $products = Product::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
            })
            ->latest()
            ->get();

        return Inertia::render('Inertia/Products', [
            'products' => $products,
            'filters' => [
                'search' => $search,
            ],
            'meta' => [
                'channel' => 'Inertia.js Bridge',
                'explanation' => 'Data ini di-render langsung oleh Controller Laravel ke Vue Component tanpa route API publik terpisah.',
            ],
        ]);
    }

    /**
     * Store a newly created product via Inertia.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['created_by_channel'] = 'Inertia Bridge';
        $validated['status'] = 'active';

        Product::create($validated);

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan melalui Inertia.js Bridge!');
    }

    /**
     * Remove the specified product via Inertia.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus melalui Inertia.js Bridge!');
    }
}
