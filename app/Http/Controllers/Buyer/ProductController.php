<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar productos desde el lado del comprador.
 *
 * Métodos principales:
 * - show(): Mostrar detalles de un producto específico.
 */
class ProductController extends Controller
{
    /**
     * Servicio de productos.
     * @var ProductService
     */
    protected $productService;

    /**
     * Inyecta el servicio de productos.
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Mostrar detalles de un producto específico.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = $this->productService->getProductById($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Producto encontrado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar productos disponibles para el comprador.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = $this->productService->searchAvailableProducts();
            
            // Aplicar filtros
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
            
            if ($request->has('in_stock') && $request->in_stock) {
                $query->where('stock', '>', 0);
            }
            
            // Aplicar paginación
            $perPage = $request->get('per_page', 20);
            $products = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Productos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos destacados.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $products = $this->productService->getFeaturedProducts($limit);
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Productos destacados obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos destacados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar productos.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            
            $products = $this->productService->searchProducts($query, $page, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Búsqueda completada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos relacionados.
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function related($id, Request $request)
    {
        try {
            $limit = $request->get('limit', 5);
            $products = $this->productService->getRelatedProducts($id, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Productos relacionados obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos relacionados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener marcas populares.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function brands(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $brands = $this->productService->getPopularBrands($limit);
            
            return response()->json([
                'success' => true,
                'data' => $brands,
                'message' => 'Marcas populares obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marcas: ' . $e->getMessage()
            ], 500);
        }
    }
}
