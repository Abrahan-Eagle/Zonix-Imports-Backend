<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            $commerce = $profile ? \App\Models\Commerce::where('profile_id', $profile->id)->first() : null;
            if (!$commerce) {
                \Log::error('No se encontró comercio para el usuario', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Comercio no encontrado para el usuario autenticado',
                ], 404);
            }
            $query = Product::where('commerce_id', $commerce->id);

            // Filtros de búsqueda
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filtro por disponibilidad
            if ($request->has('available')) {
                $query->where('available', $request->boolean('available'));
            }

            // Filtro por rango de precio
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->get('min_price'));
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->get('max_price'));
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            if ($request->has('per_page')) {
                $perPage = $request->get('per_page', 15);
                $products = $query->paginate($perPage);
            } else {
                $products = $query->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Productos obtenidos correctamente',
                'data' => $products
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            $commerce = $profile ? \App\Models\Commerce::where('profile_id', $profile->id)->first() : null;
            if (!$commerce) {
                \Log::error('No se encontró comercio para el usuario', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Comercio no encontrado para el usuario autenticado',
                ], 404);
            }
            $validatedData = $request->validated();

            // Agregar commerce_id del usuario autenticado
            $validatedData['commerce_id'] = $commerce->id;

            // Manejar imagen si existe
            if ($request->hasFile('imagen')) {
                $imagePath = $request->file('imagen')->store('productos', 'public');
                $validatedData['imagen'] = $imagePath;
            }

            $product = Product::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Producto creado correctamente',
                'data' => $product
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            $commerce = $profile ? \App\Models\Commerce::where('profile_id', $profile->id)->first() : null;
            if (!$commerce) {
                \Log::error('No se encontró comercio para el usuario', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Comercio no encontrado para el usuario autenticado',
                ], 404);
            }
            $product = Product::where('commerce_id', $commerce->id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Producto obtenido correctamente',
                'data' => $product
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            $commerce = $profile ? \App\Models\Commerce::where('profile_id', $profile->id)->first() : null;
            if (!$commerce) {
                \Log::error('No se encontró comercio para el usuario', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Comercio no encontrado para el usuario autenticado',
                ], 404);
            }
            $product = Product::where('commerce_id', $commerce->id)->findOrFail($id);
            $validatedData = $request->validated();

            // Manejar imagen si existe
            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior
                if ($product->imagen && Storage::disk('public')->exists($product->imagen)) {
                    Storage::disk('public')->delete($product->imagen);
                }

                $imagePath = $request->file('imagen')->store('productos', 'public');
                $validatedData['imagen'] = $imagePath;
            }

            $product->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente',
                'data' => $product
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            $commerce = $profile ? \App\Models\Commerce::where('profile_id', $profile->id)->first() : null;
            if (!$commerce) {
                \Log::error('No se encontró comercio para el usuario', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Comercio no encontrado para el usuario autenticado',
                ], 404);
            }
            $product = Product::where('commerce_id', $commerce->id)->findOrFail($id);

            // Eliminar imagen si existe
            if ($product->imagen && Storage::disk('public')->exists($product->imagen)) {
                Storage::disk('public')->delete($product->imagen);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar disponibilidad del producto
     */
    public function toggleDisponible($id)
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            $commerce = $profile ? \App\Models\Commerce::where('profile_id', $profile->id)->first() : null;
            if (!$commerce) {
                \Log::error('No se encontró comercio para el usuario', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Comercio no encontrado para el usuario autenticado',
                ], 404);
            }
            $product = Product::where('commerce_id', $commerce->id)->findOrFail($id);
            $product->update(['disponible' => !$product->disponible]);

            $message = $product->disponible ? 'Producto marcado como disponible' : 'Producto marcado como no disponible';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $product
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar disponibilidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de productos del comercio
     */
    public function estadisticas()
    {
        try {
            $commerceId = Auth::id();

            $stats = [
                'total_productos' => Product::where('commerce_id', $commerceId)->count(),
                'productos_disponibles' => Product::where('commerce_id', $commerceId)->where('disponible', true)->count(),
                'productos_no_disponibles' => Product::where('commerce_id', $commerceId)->where('disponible', false)->count(),
                'precio_promedio' => Product::where('commerce_id', $commerceId)->avg('precio'),
                'producto_mas_caro' => Product::where('commerce_id', $commerceId)->max('precio'),
                'producto_mas_barato' => Product::where('commerce_id', $commerceId)->min('precio'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => $stats
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
