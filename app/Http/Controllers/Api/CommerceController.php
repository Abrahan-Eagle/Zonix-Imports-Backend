<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommerceController extends Controller
{
    /**
     * Listar todas las tiendas (público)
     * GET /api/commerces
     */
    public function index(Request $request)
    {
        $query = Commerce::with(['profile.user', 'products'])
            ->where('is_verified', true); // Solo tiendas verificadas

        // Filtro: Solo tiendas abiertas
        if ($request->has('open') && $request->open == 'true') {
            $query->where('open', true);
        }

        // Filtro: Por tipo de negocio
        if ($request->has('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        // Búsqueda por nombre
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('business_name', 'LIKE', "%{$search}%");
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'business_name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 20);
        $commerces = $query->paginate($perPage);

        // Agregar conteo de productos activos
        $commerces->getCollection()->transform(function ($commerce) {
            $commerce->products_count = $commerce->products()->where('available', true)->count();
            return $commerce;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tiendas obtenidas exitosamente',
            'data' => $commerces->items(),
            'meta' => [
                'current_page' => $commerces->currentPage(),
                'per_page' => $commerces->perPage(),
                'total' => $commerces->total(),
                'last_page' => $commerces->lastPage(),
            ]
        ]);
    }

    /**
     * Ver detalles de una tienda (público)
     * GET /api/commerces/{id}
     */
    public function show($id)
    {
        $commerce = Commerce::with([
            'profile.user',
            'profile.addresses' => function($query) {
                $query->where('is_default', true);
            }
        ])
        ->where('is_verified', true)
        ->find($id);

        if (!$commerce) {
            return response()->json([
                'success' => false,
                'message' => 'Tienda no encontrada o no verificada'
            ], 404);
        }

        // Estadísticas de la tienda
        $commerce->statistics = [
            'total_products' => $commerce->products()->count(),
            'active_products' => $commerce->products()->where('available', true)->count(),
            'total_orders' => $commerce->orders()->count(),
            'completed_orders' => $commerce->orders()->where('status', 'delivered')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Detalles de tienda obtenidos exitosamente',
            'data' => $commerce
        ]);
    }

    /**
     * Obtener productos de una tienda específica (público)
     * GET /api/commerces/{id}/products
     */
    public function products(Request $request, $id)
    {
        $commerce = Commerce::where('is_verified', true)->find($id);

        if (!$commerce) {
            return response()->json([
                'success' => false,
                'message' => 'Tienda no encontrada'
            ], 404);
        }

        $query = $commerce->products()->with(['category', 'images'])
            ->where('available', true);

        // Filtros
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('modality')) {
            $query->where('modality', $request->modality);
        }

        if ($request->has('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // Búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Productos de la tienda obtenidos exitosamente',
            'data' => $products->items(),
            'commerce' => [
                'id' => $commerce->id,
                'business_name' => $commerce->business_name,
                'image' => $commerce->image,
            ],
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    /**
     * Obtener tienda del usuario autenticado (vendedor)
     * GET /api/my-commerce
     */
    public function myCommerce(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil no encontrado'
            ], 404);
        }

        $commerce = $profile->commerce;

        if (!$commerce) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda registrada'
            ], 404);
        }

        // Estadísticas
        $commerce->statistics = [
            'total_products' => $commerce->products()->count(),
            'active_products' => $commerce->products()->where('available', true)->count(),
            'total_orders' => $commerce->orders()->count(),
            'pending_orders' => $commerce->orders()->where('status', 'pending')->count(),
            'total_sales' => $commerce->orders()->where('status', 'delivered')->sum('total'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Tu tienda obtenida exitosamente',
            'data' => $commerce
        ]);
    }

    /**
     * Actualizar estado de la tienda (vendedor)
     * PUT /api/my-commerce/toggle
     */
    public function toggleStatus(Request $request)
    {
        $user = $request->user();
        $commerce = $user->profile->commerce;

        if (!$commerce) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una tienda registrada'
            ], 404);
        }

        $commerce->open = !$commerce->open;
        $commerce->save();

        return response()->json([
            'success' => true,
            'message' => $commerce->open ? 'Tienda abierta' : 'Tienda cerrada',
            'data' => $commerce
        ]);
    }

    /**
     * Obtener tipos de negocio disponibles
     * GET /api/business-types
     */
    public function businessTypes()
    {
        $types = [
            ['value' => 'retail', 'label' => 'Tienda al Detal'],
            ['value' => 'wholesale', 'label' => 'Mayorista'],
            ['value' => 'both', 'label' => 'Detal y Mayor'],
            ['value' => 'dropshipping', 'label' => 'Dropshipping'],
            ['value' => 'preorder', 'label' => 'Pre-órdenes'],
        ];

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }
}

