<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Commerce;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Búsqueda avanzada de restaurantes
     */
    public function searchRestaurants(Request $request): JsonResponse
    {
        try {
            $query = Commerce::with(['products', 'reviews'])
                ->where('is_active', true)
                ->where('is_open', true);

            // Búsqueda por nombre
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('products', function ($productQuery) use ($searchTerm) {
                          $productQuery->where('name', 'LIKE', "%{$searchTerm}%")
                                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }

            // Filtro por categoría
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Filtro por precio mínimo
            if ($request->filled('min_price')) {
                $query->whereHas('products', function ($productQuery) use ($request) {
                    $productQuery->where('price', '>=', $request->min_price);
                });
            }

            // Filtro por precio máximo
            if ($request->filled('max_price')) {
                $query->whereHas('products', function ($productQuery) use ($request) {
                    $productQuery->where('price', '<=', $request->max_price);
                });
            }

            // Filtro por calificación mínima
            if ($request->filled('min_rating')) {
                $query->where('average_rating', '>=', $request->min_rating);
            }

            // Filtro por distancia (si se proporcionan coordenadas)
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $lat = $request->latitude;
                $lng = $request->longitude;
                $maxDistance = $request->max_distance ?? 10; // km por defecto

                $query->selectRaw("
                    *,
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                    sin(radians(latitude)))) AS distance
                ", [$lat, $lng, $lat])
                ->having('distance', '<=', $maxDistance)
                ->orderBy('distance', 'asc');
            }

            // Ordenamiento
            $sortBy = $request->sort_by ?? 'name';
            $sortOrder = $request->sort_order ?? 'asc';

            switch ($sortBy) {
                case 'rating':
                    $query->orderBy('average_rating', $sortOrder);
                    break;
                case 'distance':
                    if ($request->filled('latitude') && $request->filled('longitude')) {
                        // Ya ordenado por distancia arriba
                    } else {
                        $query->orderBy('name', 'asc');
                    }
                    break;
                case 'delivery_time':
                    $query->orderBy('estimated_delivery_time', $sortOrder);
                    break;
                case 'price':
                    $query->orderBy(DB::raw('(SELECT MIN(price) FROM products WHERE commerce_id = commerces.id)'), $sortOrder);
                    break;
                default:
                    $query->orderBy('name', 'asc');
            }

            // Paginación
            $perPage = $request->per_page ?? 20;
            $restaurants = $query->paginate($perPage);

            $restaurantsData = $restaurants->map(function ($restaurant) use ($request) {
                $data = [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'description' => $restaurant->description,
                    'category' => $restaurant->category,
                    'address' => $restaurant->address,
                    'phone' => $restaurant->phone,
                    'logo_url' => $restaurant->logo_url,
                    'cover_url' => $restaurant->cover_url,
                    'average_rating' => $restaurant->average_rating ?? 0,
                    'total_reviews' => $restaurant->total_reviews ?? 0,
                    'estimated_delivery_time' => $restaurant->estimated_delivery_time ?? 30,
                    'delivery_fee' => $restaurant->delivery_fee ?? 0,
                    'minimum_order' => $restaurant->minimum_order ?? 0,
                    'is_open' => $restaurant->is_open,
                    'is_favorite' => $this->isFavorite($restaurant->id),
                    'total_products' => $restaurant->products->count()
                ];

                // Agregar distancia si se calculó
                if (isset($restaurant->distance)) {
                    $data['distance'] = round($restaurant->distance, 2);
                }

                // Agregar productos destacados
                $data['featured_products'] = $restaurant->products
                    ->take(3)
                    ->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->price,
                            'image_url' => $product->image_url
                        ];
                    });

                return $data;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'restaurants' => $restaurantsData,
                    'pagination' => [
                        'current_page' => $restaurants->currentPage(),
                        'last_page' => $restaurants->lastPage(),
                        'per_page' => $restaurants->perPage(),
                        'total' => $restaurants->total()
                    ],
                    'filters_applied' => [
                        'search' => $request->search,
                        'category' => $request->category,
                        'min_price' => $request->min_price,
                        'max_price' => $request->max_price,
                        'min_rating' => $request->min_rating,
                        'max_distance' => $request->max_distance,
                        'sort_by' => $request->sort_by,
                        'sort_order' => $request->sort_order
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching restaurants: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar restaurantes'
            ], 500);
        }
    }

    /**
     * Búsqueda de productos
     */
    public function searchProducts(Request $request): JsonResponse
    {
        try {
            $query = Product::with(['commerce'])
                ->where('is_active', true);

            // Búsqueda por nombre o descripción
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Filtro por restaurante
            if ($request->filled('commerce_id')) {
                $query->where('commerce_id', $request->commerce_id);
            }

            // Filtro por categoría
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Filtro por precio
            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filtro por disponibilidad
            if ($request->filled('available')) {
                $query->where('is_available', $request->available);
            }

            // Ordenamiento
            $sortBy = $request->sort_by ?? 'name';
            $sortOrder = $request->sort_order ?? 'asc';

            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->per_page ?? 20;
            $products = $query->paginate($perPage);

            $productsData = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'category' => $product->category,
                    'image_url' => $product->image_url,
                    'is_available' => $product->is_available,
                    'is_popular' => $product->is_popular,
                    'commerce' => [
                        'id' => $product->commerce->id,
                        'name' => $product->commerce->name,
                        'logo_url' => $product->commerce->logo_url
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $productsData,
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar productos'
            ], 500);
        }
    }

    /**
     * Obtener categorías disponibles
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = \App\Models\Category::select('id', 'name', 'description')->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías'
            ], 500);
        }
    }

    /**
     * Obtener sugerencias de búsqueda
     */
    public function getSearchSuggestions(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->search ?? '';
            
            if (strlen($searchTerm) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // Sugerencias de restaurantes
            $restaurantSuggestions = Commerce::where('is_active', true)
                ->where('name', 'LIKE', "%{$searchTerm}%")
                ->limit(5)
                ->pluck('name');

            // Sugerencias de productos
            $productSuggestions = Product::where('is_active', true)
                ->where('name', 'LIKE', "%{$searchTerm}%")
                ->limit(5)
                ->pluck('name');

            $suggestions = $restaurantSuggestions->merge($productSuggestions)
                ->unique()
                ->values()
                ->take(10);

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting search suggestions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sugerencias'
            ], 500);
        }
    }

    /**
     * Verificar si un restaurante está en favoritos
     */
    private function isFavorite($commerceId): bool
    {
        $profile = auth()->user()->profile;
        return $profile->favorites()
            ->where('commerce_id', $commerceId)
            ->exists();
    }
} 