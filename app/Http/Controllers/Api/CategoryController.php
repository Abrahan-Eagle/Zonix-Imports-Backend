<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Obtener todas las categorías disponibles
     */
    public function index(): JsonResponse
    {
        try {
            $categories = Category::select('id', 'name', 'description', 'image', 'active')
                ->where('active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Categorías obtenidas exitosamente',
                'data' => $categories
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener categoría específica con sus productos
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = Category::with(['products' => function($query) {
                $query->where('available', true)
                      ->select('id', 'category_id', 'name', 'base_price', 'image', 'modality');
            }])
            ->where('active', true)
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Categoría obtenida exitosamente',
                'data' => $category
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
