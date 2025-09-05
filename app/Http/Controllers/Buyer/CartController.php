<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar el carrito de compras del comprador.
 *
 * Métodos principales:
 * - add(): Agregar un producto al carrito.
 * - show(): Mostrar el contenido del carrito.
 */
class CartController extends Controller
{
    /**
     * Servicio de carrito.
     * @var CartService
     */
    protected $cartService;

    /**
     * Inyecta el servicio de carrito.
     * @param CartService $cartService
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Agregar un producto al carrito.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);
        $cart = $this->cartService->addToCart($validated);
        return response()->json(['message' => 'Producto agregado al carrito', 'cart' => $cart]);
    }

    /**
     * Mostrar el contenido del carrito.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        $cart = $this->cartService->getCart();
        return response()->json(['cart' => $cart]);
    }

    /**
     * Actualizar cantidad de un producto en el carrito.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $cart = $this->cartService->updateQuantity($validated['product_id'], $validated['quantity']);
        return response()->json(['message' => 'Cantidad actualizada', 'cart' => $cart]);
    }

    /**
     * Remover un producto del carrito.
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove($productId)
    {
        $cart = $this->cartService->removeFromCart($productId);
        return response()->json(['message' => 'Producto removido del carrito', 'cart' => $cart]);
    }

    /**
     * Agregar notas al carrito.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNotes(Request $request)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);
        
        $cart = $this->cartService->addNotes($validated['notes']);
        return response()->json(['message' => 'Notas agregadas', 'cart' => $cart]);
    }
}
