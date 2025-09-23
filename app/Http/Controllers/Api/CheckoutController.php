<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;

class CheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user?->profile;
        if (!$profile) {
            return response()->json(['message' => 'Perfil no encontrado'], 422);
        }

        $cartItems = CartItem::with(['product'])
            ->where('profile_id', $profile->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Carrito vacío'], 422);
        }

        // Asumimos un solo comercio en el carrito para MVP
        $commerceId = $cartItems->first()->product->commerce_id;

        return DB::transaction(function () use ($cartItems, $profile, $commerceId) {
            $subtotal = $cartItems->sum('subtotal');
            $shipping = 0;
            $discount = 0;
            $total = $subtotal + $shipping - $discount;

            $order = Order::create([
                'profile_id' => $profile->id,
                'commerce_id' => $commerceId,
                'modality' => 'retail',
                'delivery_type' => 'pickup',
                'status' => 'pending_payment',
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => $shipping,
                'total' => $total,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            // Limpiar carrito del perfil
            CartItem::where('profile_id', $profile->id)->delete();

            return response()->json([
                'success' => true,
                'data' => $order->load('orderItems')
            ], 201);
        });
    }
}


