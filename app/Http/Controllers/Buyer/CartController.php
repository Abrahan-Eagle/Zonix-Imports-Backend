<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador del Carrito de Compras
 * 
 * Gestiona todas las operaciones del carrito para compradores.
 * Usa CartService para la lógica de negocio.
 */
class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Agregar producto al carrito
     * 
     * POST /api/buyer/cart/add
     *
     * @param AddToCartRequest $request
     * @return JsonResponse
     */
    public function add(AddToCartRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado. Complete su perfil primero.'
                ], 404);
            }

            $cartItem = $this->cartService->addItem(
                $profile,
                $request->product_id,
                $request->quantity,
                $request->modality ?? 'retail'
            );

            // Obtener resumen actualizado
            $cartData = $this->cartService->getCart($profile);

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado al carrito exitosamente',
                'data' => [
                    'cart_item' => $cartItem,
                    'cart_total' => $cartData['summary']['total'],
                    'items_count' => $cartData['summary']['items_count']
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error agregando al carrito', [
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'CART_ADD_ERROR'
            ], 400);
        }
    }

    /**
     * Obtener carrito del usuario
     * 
     * GET /api/buyer/cart
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function show(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'items' => [],
                        'summary' => [
                            'items_count' => 0,
                            'subtotal' => 0,
                            'shipping' => 0,
                            'discount' => 0,
                            'total' => 0
                        ]
                    ]
                ]);
            }

            $cartData = $this->cartService->getCart($profile);

            return response()->json([
                'success' => true,
                'message' => 'Carrito obtenido exitosamente',
                'data' => $cartData
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo carrito', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el carrito',
                'code' => 'CART_GET_ERROR'
            ], 500);
        }
    }

    /**
     * Actualizar cantidad de un item
     * 
     * PUT /api/cart/{cartItemId}
     *
     * @param UpdateCartRequest $request
     * @param int $cartItemId
     * @return JsonResponse
     */
    public function update(UpdateCartRequest $request, int $cartItemId): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            $cartItem = $this->cartService->updateQuantity(
                $profile,
                $cartItemId,
                $request->quantity
            );

            // Obtener resumen actualizado
            $cartData = $this->cartService->getCart($profile);

            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada exitosamente',
                'data' => [
                    'cart_item' => $cartItem,
                    'summary' => $cartData['summary']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error actualizando cantidad', [
                'user_id' => $request->user()->id,
                'cart_item_id' => $cartItemId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'CART_UPDATE_ERROR'
            ], 400);
        }
    }

    /**
     * Eliminar item del carrito
     * 
     * DELETE /api/cart/{cartItemId}
     *
     * @param \Illuminate\Http\Request $request
     * @param int $cartItemId
     * @return JsonResponse
     */
    public function destroy(\Illuminate\Http\Request $request, int $cartItemId): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            $this->cartService->removeItem($profile, $cartItemId);

            // Obtener resumen actualizado
            $cartData = $this->cartService->getCart($profile);

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'data' => [
                    'summary' => $cartData['summary']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminando del carrito', [
                'user_id' => $request->user()->id,
                'cart_item_id' => $cartItemId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'CART_REMOVE_ERROR'
            ], 400);
        }
    }

    /**
     * Limpiar todo el carrito
     * 
     * DELETE /api/buyer/cart
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function clear(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            $count = $this->cartService->clearCart($profile);

            return response()->json([
                'success' => true,
                'message' => "Carrito limpiado. {$count} productos eliminados",
                'data' => [
                    'items_deleted' => $count
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error limpiando carrito', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar el carrito',
                'code' => 'CART_CLEAR_ERROR'
            ], 500);
        }
    }

    /**
     * Validar disponibilidad de stock del carrito
     * 
     * GET /api/buyer/cart/validate
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function validateStock(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            $validation = $this->cartService->validateCartStock($profile);

            return response()->json([
                'success' => $validation['valid'],
                'message' => $validation['valid'] 
                    ? 'Carrito válido' 
                    : 'Hay problemas con algunos productos',
                'data' => $validation
            ], $validation['valid'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar el carrito'
            ], 500);
        }
    }
}
