<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    /**
     * Display a listing of products as JSON.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $category = $request->query('category');

        $query = Product::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category && $category !== 'All') {
            $query->where('category', $category);
        }

        $products = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'channel' => 'REST API (Traditional SPA)',
            'timestamp' => now()->toISOString(),
            'total' => $products->count(),
            'data' => $products,
        ]);
    }

    /**
     * Store a newly created product via API.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['created_by_channel'] = 'SPA API';
        $validated['status'] = 'active';

        $product = Product::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dibuat melalui Traditional REST API!',
            'channel' => 'REST API (Traditional SPA)',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'channel' => 'REST API (Traditional SPA)',
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product via API.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:100',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|string',
            'description' => 'nullable|string',
        ]);

        $product->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diperbarui via API!',
            'data' => $product,
        ]);
    }

    /**
     * Remove the specified product via API.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dihapus via Traditional REST API!',
            'id' => $product->id,
        ]);
    }
}
