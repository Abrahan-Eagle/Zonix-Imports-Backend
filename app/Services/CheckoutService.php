<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Profile;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckoutService
{
    /**
     * Iniciar proceso de checkout
     * 
     * @param Profile $profile
     * @param int $shippingAddressId
     * @param string $deliveryType
     * @param int|null $billingAddressId
     * @return array
     * @throws Exception
     */
    public function initiateCheckout(
        Profile $profile,
        int $shippingAddressId,
        string $deliveryType = 'delivery',
        ?int $billingAddressId = null
    ): array {
        try {
            DB::beginTransaction();

            // 1. Validar carrito
            $cartValidation = $this->validateCart($profile);
            if (!$cartValidation['valid']) {
                throw new Exception($cartValidation['message']);
            }

            $cartItems = $cartValidation['items'];
            $cartSummary = $cartValidation['summary'];

            // 2. Validar dirección de envío
            $shippingAddress = $this->validateAddress($profile, $shippingAddressId);

            // 3. Validar dirección de facturación (si se proporcionó)
            $billingAddress = null;
            if ($billingAddressId) {
                $billingAddress = $this->validateAddress($profile, $billingAddressId);
            }

            // 4. Validar delivery_type
            if (!in_array($deliveryType, ['pickup', 'delivery'])) {
                throw new Exception('Tipo de entrega inválido. Debe ser "pickup" o "delivery".');
            }

            // Si es pickup, el shipping debería ser 0
            $shippingTotal = $deliveryType === 'pickup' ? 0 : $cartSummary['shipping'];

            // 5. Determinar el comercio principal (el del primer item)
            $mainCommerceId = $cartItems->first()->product->commerce_id;

            // 6. Determinar la modalidad principal (del primer item)
            $mainModality = $cartItems->first()->modality;

            // 7. Calcular totales finales (recalcular descuento con shipping correcto)
            $subtotal = $cartSummary['subtotal'];
            $discount = $this->applyDiscounts($subtotal, $shippingTotal);
            $total = $subtotal + $shippingTotal - $discount;

            DB::commit();

            return [
                'valid' => true,
                'cart_items' => $cartItems,
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
                'delivery_type' => $deliveryType,
                'commerce_id' => $mainCommerceId,
                'modality' => $mainModality,
                'summary' => [
                    'subtotal' => round($subtotal, 2),
                    'discount' => round($discount, 2),
                    'shipping' => round($shippingTotal, 2),
                    'total' => round($total, 2),
                    'items_count' => $cartItems->count()
                ]
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en initiate checkout: ' . $e->getMessage(), [
                'profile_id' => $profile->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Confirmar checkout y crear orden
     * 
     * @param Profile $profile
     * @param int $shippingAddressId
     * @param string $deliveryType
     * @param int|null $billingAddressId
     * @param string|null $notes
     * @return Order
     * @throws Exception
     */
    public function confirmCheckout(
        Profile $profile,
        int $shippingAddressId,
        string $deliveryType = 'delivery',
        ?int $billingAddressId = null,
        ?string $notes = null
    ): Order {
        try {
            DB::beginTransaction();

            // 1. Obtener datos del checkout
            $checkoutData = $this->initiateCheckout(
                $profile,
                $shippingAddressId,
                $deliveryType,
                $billingAddressId
            );

            if (!$checkoutData['valid']) {
                throw new Exception('Checkout inválido');
            }

            // 2. Crear la orden
            $order = Order::create([
                'profile_id' => $profile->id,
                'commerce_id' => $checkoutData['commerce_id'],
                'modality' => $checkoutData['modality'],
                'delivery_type' => $deliveryType,
                'status' => 'pending_payment',
                'subtotal' => $checkoutData['summary']['subtotal'],
                'discount_total' => $checkoutData['summary']['discount'],
                'shipping_total' => $checkoutData['summary']['shipping'],
                'total' => $checkoutData['summary']['total'],
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId,
                'notes' => $notes,
                'estimated_delivery' => now()->addDays(5) // 5 días por defecto
            ]);

            // 3. Crear order_items desde cart_items
            foreach ($checkoutData['cart_items'] as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'subtotal' => $cartItem->subtotal
                ]);

                // 4. Reducir stock del producto
                $product = $cartItem->product;
                $product->decrement('stock', $cartItem->quantity);
            }

            // 5. Limpiar el carrito
            CartItem::where('profile_id', $profile->id)->delete();

            DB::commit();

            // 6. Cargar relaciones para el response
            $order->load([
                'orderItems.product',
                'shippingAddress.city.state',
                'billingAddress.city.state',
                'commerce'
            ]);

            Log::info('Orden creada exitosamente', [
                'order_id' => $order->id,
                'profile_id' => $profile->id,
                'total' => $order->total
            ]);

            return $order;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en confirm checkout: ' . $e->getMessage(), [
                'profile_id' => $profile->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validar carrito del usuario
     * 
     * @param Profile $profile
     * @return array
     * @throws Exception
     */
    protected function validateCart(Profile $profile): array
    {
        $cartItems = CartItem::with('product.commerce')
            ->where('profile_id', $profile->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return [
                'valid' => false,
                'message' => 'El carrito está vacío'
            ];
        }

        // Validar stock de cada producto
        foreach ($cartItems as $item) {
            if (!$item->product) {
                return [
                    'valid' => false,
                    'message' => 'Producto no encontrado en el carrito'
                ];
            }

            if (!$item->product->available) {
                return [
                    'valid' => false,
                    'message' => "El producto '{$item->product->name}' no está disponible"
                ];
            }

            if ($item->product->stock < $item->quantity) {
                return [
                    'valid' => false,
                    'message' => "Stock insuficiente para '{$item->product->name}'. Disponible: {$item->product->stock}"
                ];
            }
        }

        // Calcular resumen
        $subtotal = $cartItems->sum('subtotal');
        $shipping = $this->calculateShipping($cartItems);
        $discount = $this->applyDiscounts($subtotal, $shipping);

        return [
            'valid' => true,
            'items' => $cartItems,
            'summary' => [
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'discount' => $discount
            ]
        ];
    }

    /**
     * Validar dirección pertenece al usuario
     * 
     * @param Profile $profile
     * @param int $addressId
     * @return Address
     * @throws Exception
     */
    protected function validateAddress(Profile $profile, int $addressId): Address
    {
        $address = Address::with('city.state')
            ->where('id', $addressId)
            ->where('profile_id', $profile->id)
            ->first();

        if (!$address) {
            throw new Exception('Dirección no encontrada o no pertenece al usuario');
        }

        return $address;
    }

    /**
     * Calcular costo de envío
     * 
     * @param $cartItems
     * @return float
     */
    protected function calculateShipping($cartItems): float
    {
        // $5 por comercio único
        $uniqueCommerces = $cartItems->pluck('product.commerce_id')->unique()->count();
        return $uniqueCommerces * 5.00;
    }

    /**
     * Aplicar descuentos
     * 
     * @param float $subtotal
     * @param float $shipping
     * @return float
     */
    protected function applyDiscounts(float $subtotal, float $shipping): float
    {
        $discount = 0;

        // Si el subtotal es mayor a $100, 50% de descuento en envío
        if ($subtotal > 100) {
            $discount = $shipping * 0.5;
        }

        return $discount;
    }

    /**
     * Obtener resumen del checkout sin confirmar
     * 
     * @param Profile $profile
     * @return array
     */
    public function getCheckoutSummary(Profile $profile): array
    {
        $cartValidation = $this->validateCart($profile);

        if (!$cartValidation['valid']) {
            return [
                'valid' => false,
                'message' => $cartValidation['message'],
                'cart_items' => [],
                'summary' => [
                    'subtotal' => 0,
                    'shipping' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'items_count' => 0
                ]
            ];
        }

        $cartItems = $cartValidation['items'];
        $summary = $cartValidation['summary'];

        return [
            'valid' => true,
            'cart_items' => $cartItems,
            'summary' => [
                'subtotal' => round($summary['subtotal'], 2),
                'shipping' => round($summary['shipping'], 2),
                'discount' => round($summary['discount'], 2),
                'total' => round($summary['subtotal'] + $summary['shipping'] - $summary['discount'], 2),
                'items_count' => $cartItems->count()
            ]
        ];
    }
}

