<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Carrito de Compras
 * 
 * Gestiona todas las operaciones del carrito con persistencia en DB.
 * Incluye validaciones de stock, modalidades y cálculos de totales.
 */
class CartService
{
    /**
     * Agregar producto al carrito
     *
     * @param Profile $profile
     * @param int $productId
     * @param int $quantity
     * @param string $modality
     * @return CartItem
     * @throws \Exception
     */
    public function addItem(Profile $profile, int $productId, int $quantity, string $modality = 'retail')
    {
        // 1. Validar producto existe y está disponible
        $product = Product::with(['commerce', 'category'])->find($productId);
        
        if (!$product) {
            throw new \Exception('Producto no encontrado');
        }
        
        if (!$product->available) {
            throw new \Exception('Producto no disponible');
        }

        // 2. Validar stock según modalidad
        if (in_array($modality, ['retail', 'wholesale'])) {
            if ($product->stock < $quantity) {
                throw new \Exception("Stock insuficiente. Disponible: {$product->stock}");
            }
        }

        // 3. Validar cantidad mínima para mayorista
        if ($modality === 'wholesale') {
            $minQuantity = $product->min_wholesale_quantity ?? 1;
            if ($quantity < $minQuantity) {
                throw new \Exception("Cantidad mínima para mayorista: {$minQuantity} unidades");
            }
        }

        // 4. Validar dropshipping (stock del producto origen)
        if ($modality === 'dropshipping' && $product->origin_product_id) {
            $originProduct = Product::find($product->origin_product_id);
            if ($originProduct && $originProduct->stock < $quantity) {
                throw new \Exception("Stock insuficiente en producto origen. Disponible: {$originProduct->stock}");
            }
        }

        // 5. Calcular precio según modalidad
        $unitPrice = match ($modality) {
            'wholesale' => $product->wholesale_price ?? $product->base_price,
            'referral' => $product->base_price * 0.95, // 5% descuento por referido (ejemplo)
            default => $product->base_price
        };

        $subtotal = $quantity * $unitPrice;

        // 6. Crear o actualizar cart item
        $cartItem = CartItem::updateOrCreate(
            [
                'profile_id' => $profile->id,
                'product_id' => $productId,
                'modality' => $modality
            ],
            [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal
            ]
        );

        Log::info('Producto agregado al carrito', [
            'profile_id' => $profile->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'modality' => $modality
        ]);

        return $cartItem->load(['product.images', 'product.category', 'product.commerce']);
    }

    /**
     * Obtener carrito del usuario con resumen
     *
     * @param Profile $profile
     * @return array
     */
    public function getCart(Profile $profile)
    {
        $items = CartItem::with([
            'product.images',
            'product.category',
            'product.commerce'
        ])
            ->where('profile_id', $profile->id)
            ->get();

        // Calcular resumen
        $summary = $this->calculateSummary($items);

        return [
            'items' => $items,
            'summary' => $summary
        ];
    }

    /**
     * Actualizar cantidad de un item del carrito
     *
     * @param Profile $profile
     * @param int $cartItemId
     * @param int $quantity
     * @return CartItem
     * @throws \Exception
     */
    public function updateQuantity(Profile $profile, int $cartItemId, int $quantity)
    {
        $cartItem = CartItem::where('profile_id', $profile->id)
            ->where('id', $cartItemId)
            ->with('product')
            ->firstOrFail();

        $product = $cartItem->product;

        // Validar stock
        if (in_array($cartItem->modality, ['retail', 'wholesale'])) {
            if ($product->stock < $quantity) {
                throw new \Exception("Stock insuficiente. Disponible: {$product->stock}");
            }
        }

        // Validar cantidad mínima para mayorista
        if ($cartItem->modality === 'wholesale') {
            $minQuantity = $product->min_wholesale_quantity ?? 1;
            if ($quantity < $minQuantity) {
                throw new \Exception("Cantidad mínima para mayorista: {$minQuantity} unidades");
            }
        }

        // Actualizar
        $cartItem->quantity = $quantity;
        $cartItem->subtotal = $quantity * $cartItem->unit_price;
        $cartItem->save();

        Log::info('Cantidad actualizada en carrito', [
            'cart_item_id' => $cartItemId,
            'new_quantity' => $quantity
        ]);

        return $cartItem->fresh(['product.images', 'product.category']);
    }

    /**
     * Eliminar item del carrito
     *
     * @param Profile $profile
     * @param int $cartItemId
     * @return bool
     * @throws \Exception
     */
    public function removeItem(Profile $profile, int $cartItemId)
    {
        $cartItem = CartItem::where('profile_id', $profile->id)
            ->where('id', $cartItemId)
            ->firstOrFail();

        $deleted = $cartItem->delete();

        Log::info('Producto eliminado del carrito', [
            'cart_item_id' => $cartItemId,
            'product_id' => $cartItem->product_id
        ]);

        return $deleted;
    }

    /**
     * Limpiar todo el carrito del usuario
     *
     * @param Profile $profile
     * @return int Cantidad de items eliminados
     */
    public function clearCart(Profile $profile)
    {
        $count = CartItem::where('profile_id', $profile->id)->delete();

        Log::info('Carrito limpiado', [
            'profile_id' => $profile->id,
            'items_deleted' => $count
        ]);

        return $count;
    }

    /**
     * Calcular resumen del carrito
     *
     * @param \Illuminate\Database\Eloquent\Collection $items
     * @return array
     */
    private function calculateSummary($items)
    {
        $itemsCount = $items->count();
        $subtotal = $items->sum('subtotal');
        $shipping = $this->calculateShipping($items);
        $discount = $this->calculateDiscount($items);
        $total = $subtotal + $shipping - $discount;

        return [
            'items_count' => $itemsCount,
            'subtotal' => round($subtotal, 2),
            'shipping' => round($shipping, 2),
            'discount' => round($discount, 2),
            'total' => round($total, 2)
        ];
    }

    /**
     * Calcular costo de envío
     *
     * @param \Illuminate\Database\Eloquent\Collection $items
     * @return float
     */
    private function calculateShipping($items)
    {
        if ($items->isEmpty()) {
            return 0.00;
        }

        // Agrupar por commerce (tienda)
        $commerces = $items->pluck('product.commerce_id')->unique();
        $commerceCount = $commerces->count();

        // Costo base por tienda
        $baseShipping = 10.00;
        
        // Si es de múltiples tiendas, cobrar por cada una
        $shippingCost = $baseShipping * $commerceCount;

        // Descuento por compra grande
        $subtotal = $items->sum('subtotal');
        if ($subtotal > 100) {
            $shippingCost = $shippingCost * 0.5; // 50% descuento
        }

        return $shippingCost;
    }

    /**
     * Calcular descuentos aplicables
     *
     * @param \Illuminate\Database\Eloquent\Collection $items
     * @return float
     */
    private function calculateDiscount($items)
    {
        $discount = 0.00;

        // Descuento por cantidad (mayorista automático)
        foreach ($items as $item) {
            if ($item->quantity >= 10) {
                $discount += $item->subtotal * 0.05; // 5% descuento
            }
        }

        return $discount;
    }

    /**
     * Validar disponibilidad de stock para todo el carrito
     *
     * @param Profile $profile
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCartStock(Profile $profile)
    {
        $items = CartItem::with('product')->where('profile_id', $profile->id)->get();
        $errors = [];
        $valid = true;

        foreach ($items as $item) {
            $product = $item->product;

            // Validar stock según modalidad
            if (in_array($item->modality, ['retail', 'wholesale'])) {
                if ($product->stock < $item->quantity) {
                    $errors[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'requested' => $item->quantity,
                        'available' => $product->stock,
                        'message' => "Stock insuficiente para {$product->name}. Disponible: {$product->stock}"
                    ];
                    $valid = false;
                }
            }

            // Validar disponibilidad
            if (!$product->available) {
                $errors[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'message' => "{$product->name} ya no está disponible"
                ];
                $valid = false;
            }
        }

        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }

    /**
     * Obtener cantidad total de items en el carrito
     *
     * @param Profile $profile
     * @return int
     */
    public function getItemsCount(Profile $profile)
    {
        return CartItem::where('profile_id', $profile->id)->sum('quantity');
    }

    /**
     * Verificar si el carrito está vacío
     *
     * @param Profile $profile
     * @return bool
     */
    public function isEmpty(Profile $profile)
    {
        return CartItem::where('profile_id', $profile->id)->count() === 0;
    }
}
