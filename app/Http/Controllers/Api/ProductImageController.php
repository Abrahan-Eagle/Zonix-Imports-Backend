<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    public function store(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        $product = Product::findOrFail($id);

        $nextPosition = (int) ProductImage::where('product_id', $product->id)->max('position') + 1;

        $image = ProductImage::create([
            'product_id' => $product->id,
            'url' => $request->string('url'),
            'position' => $nextPosition,
        ]);

        return response()->json([
            'success' => true,
            'data' => $image,
        ], 201);
    }
}


