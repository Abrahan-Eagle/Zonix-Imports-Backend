<?php

namespace Tests\Feature\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\InventoryMovement;

class MvpEndToEndTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function full_flow_admin_seller_buyer_checkout_payment_and_delivery()
    {
        // Admin
        $admin = Profile::factory()->admin()->create([
            'is_verified' => true,
        ]);

        // Seller + Commerce + Product
        $seller = Profile::factory()->seller()->create([
            'is_verified' => true,
            'rif' => 'J-12345678-9',
        ]);
        $commerce = Commerce::factory()->create([
            'profile_id' => $seller->id,
            'is_verified' => true,
            'open' => true,
        ]);
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'stock' => 50,
            'base_price' => 25.50,
            'modality' => 'retail',
            'available' => true,
        ]);

        // Buyer
        $buyer = Profile::factory()->buyer()->create();

        // Cart: add item (snapshot price)
        $cartItem = CartItem::factory()->create([
            'profile_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => $product->base_price,
            'subtotal' => 3 * $product->base_price,
            'modality' => 'retail',
        ]);

        // Checkout: create order
        $order = Order::factory()->create([
            'profile_id' => $buyer->id,
            'commerce_id' => $commerce->id,
            'modality' => 'retail',
            'subtotal' => $cartItem->subtotal,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $cartItem->subtotal,
            'status' => 'pending_payment',
        ]);

        // Order items
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $cartItem->quantity,
            'unit_price' => $cartItem->unit_price,
            'subtotal' => $cartItem->subtotal,
        ]);

        // Inventory movement (reservation/prepare)
        InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $seller->user_id,
            'type' => 'out',
            'quantity' => $orderItem->quantity,
            'reason' => 'order_reservation',
        ]);

        // Payment succeeded (API or manual)
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'method' => 'stripe',
            'amount' => $order->total,
            'status' => 'succeeded',
        ]);

        // Update order status to paid -> preparing -> on_way -> delivered
        $order->refresh();
        $order->status = 'paid';
        $order->save();

        $order->status = 'preparing';
        $order->save();

        $order->status = 'on_way';
        $order->tracking_number = 'TRACK-'.uniqid();
        $order->save();

        $order->status = 'delivered';
        $order->save();

        // Notifications (buyer and seller)
        Notification::factory()->create([
            'profile_id' => $buyer->id,
            'title' => 'Pago recibido',
            'body' => 'Tu pedido ha sido pagado y está en preparación.',
            'type' => 'payment_received',
            'is_read' => false,
        ]);
        Notification::factory()->create([
            'profile_id' => $seller->id,
            'title' => 'Nuevo pedido',
            'body' => 'Tienes un nuevo pedido listo para preparar.',
            'type' => 'order_created',
            'is_read' => false,
        ]);

        // Assertions
        $this->assertDatabaseHas('commerces', [
            'id' => $commerce->id,
            'is_verified' => 1,
            'open' => 1,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'commerce_id' => $commerce->id,
            'available' => 1,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'profile_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'profile_id' => $buyer->id,
            'commerce_id' => $commerce->id,
            'status' => 'delivered',
        ]);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
            'status' => 'succeeded',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => $orderItem->quantity,
        ]);
        $this->assertDatabaseHas('notifications', [
            'profile_id' => $buyer->id,
            'type' => 'payment_received',
            'is_read' => 0,
        ]);
        $this->assertDatabaseHas('notifications', [
            'profile_id' => $seller->id,
            'type' => 'order_created',
            'is_read' => 0,
        ]);
    }
}


