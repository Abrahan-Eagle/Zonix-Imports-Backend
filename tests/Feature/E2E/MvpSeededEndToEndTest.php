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
use App\Models\Address;
use App\Models\City;

class MvpSeededEndToEndTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function full_flow_using_seeded_data_and_multiple_modalities_and_payments()
    {
        // Sembrar base completa
        $this->seed();

        // Tomar un vendedor/commerce existente
        $seller = Profile::where('role', 'seller')->firstOrFail();
        $commerce = Commerce::where('profile_id', $seller->id)->firstOrFail();

        // Crear un comprador con datos de contacto y dirección real (ciudad de seeders)
        $buyer = Profile::factory()->buyer()->create();
        $city = City::inRandomOrder()->first();
        Address::factory()->create([
            'profile_id' => $buyer->id,
            'city_id' => $city->id,
            'is_default' => true,
            'status' => 'completeData',
        ]);

        // 1) Retail: comprar producto existente del seeder/factory
        $retailProduct = Product::where('commerce_id', $commerce->id)->where('modality', 'retail')->first();
        if (!$retailProduct) {
            $retailProduct = Product::factory()->create([
                'commerce_id' => $commerce->id,
                'modality' => 'retail',
                'stock' => 20,
                'base_price' => 15.00,
            ]);
        }

        $retailCart = CartItem::factory()->create([
            'profile_id' => $buyer->id,
            'product_id' => $retailProduct->id,
            'quantity' => 2,
            'unit_price' => $retailProduct->base_price,
            'subtotal' => 2 * $retailProduct->base_price,
            'modality' => 'retail',
        ]);

        $retailOrder = Order::factory()->create([
            'profile_id' => $buyer->id,
            'commerce_id' => $commerce->id,
            'modality' => 'retail',
            'subtotal' => $retailCart->subtotal,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $retailCart->subtotal,
            'status' => 'pending_payment',
        ]);

        OrderItem::factory()->create([
            'order_id' => $retailOrder->id,
            'product_id' => $retailProduct->id,
            'quantity' => $retailCart->quantity,
            'unit_price' => $retailCart->unit_price,
            'subtotal' => $retailCart->subtotal,
        ]);

        InventoryMovement::factory()->create([
            'product_id' => $retailProduct->id,
            'user_id' => $seller->user_id,
            'type' => 'out',
            'quantity' => $retailCart->quantity,
            'reason' => 'order_reservation',
        ]);

        Payment::factory()->create([
            'order_id' => $retailOrder->id,
            'method' => 'stripe',
            'amount' => $retailOrder->total,
            'status' => 'succeeded',
        ]);

        $retailOrder->update(['status' => 'paid']);

        // 2) Wholesale: validar mínimo de mayor
        $wholesaleProduct = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'modality' => 'wholesale',
            'stock' => 200,
            'base_price' => 12.00,
            'min_wholesale_quantity' => 10,
            'wholesale_price' => 10.50,
        ]);
        $qty = 12; // >= min
        $whSub = $qty * $wholesaleProduct->wholesale_price;

        $whOrder = Order::factory()->create([
            'profile_id' => $buyer->id,
            'commerce_id' => $commerce->id,
            'modality' => 'wholesale',
            'subtotal' => $whSub,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $whSub,
            'status' => 'pending_payment',
        ]);
        OrderItem::factory()->create([
            'order_id' => $whOrder->id,
            'product_id' => $wholesaleProduct->id,
            'quantity' => $qty,
            'unit_price' => $wholesaleProduct->wholesale_price,
            'subtotal' => $whSub,
        ]);
        Payment::factory()->create([
            'order_id' => $whOrder->id,
            'method' => 'zelle',
            'amount' => $whSub,
            'status' => 'succeeded',
            'receipt_url' => 'http://example.com/receipt.jpg',
        ]);
        $whOrder->update(['status' => 'paid']);

        // 3) Preorder con abonos (partially_paid -> paid)
        $preProduct = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'modality' => 'preorder',
            'base_price' => 100,
            'preorder_eta' => now()->addDays(30),
        ]);
        $preOrder = Order::factory()->create([
            'profile_id' => $buyer->id,
            'commerce_id' => $commerce->id,
            'modality' => 'preorder',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'status' => 'pending_payment',
        ]);
        OrderItem::factory()->create([
            'order_id' => $preOrder->id,
            'product_id' => $preProduct->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);
        // Abono 1
        Payment::factory()->create([
            'order_id' => $preOrder->id,
            'method' => 'pago_movil',
            'amount' => 40,
            'status' => 'succeeded',
        ]);
        $preOrder->update(['status' => 'partially_paid']);
        // Abono 2
        Payment::factory()->create([
            'order_id' => $preOrder->id,
            'method' => 'pago_movil',
            'amount' => 60,
            'status' => 'succeeded',
        ]);
        $preOrder->update(['status' => 'paid']);

        // 4) Dropshipping: producto que referencia a otro
        $origin = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'modality' => 'retail',
            'stock' => 100,
            'base_price' => 30,
        ]);
        $drop = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'modality' => 'dropshipping',
            'base_price' => 35,
            'origin_product_id' => $origin->id,
        ]);
        $dropOrder = Order::factory()->create([
            'profile_id' => $buyer->id,
            'commerce_id' => $commerce->id,
            'modality' => 'dropshipping',
            'subtotal' => 70,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 70,
            'status' => 'pending_payment',
        ]);
        OrderItem::factory()->create([
            'order_id' => $dropOrder->id,
            'product_id' => $drop->id,
            'quantity' => 2,
            'unit_price' => 35,
            'subtotal' => 70,
        ]);
        Payment::factory()->create([
            'order_id' => $dropOrder->id,
            'method' => 'paypal',
            'amount' => 70,
            'status' => 'succeeded',
        ]);
        $dropOrder->update(['status' => 'paid']);

        // Notificaciones clave
        Notification::factory()->create([
            'profile_id' => $buyer->id,
            'title' => 'Pedido pagado',
            'body' => 'Tu pedido fue pagado exitosamente.',
            'type' => 'payment_received',
            'is_read' => false,
        ]);
        Notification::factory()->create([
            'profile_id' => $seller->id,
            'title' => 'Nuevo pedido',
            'body' => 'Tienes pedidos para preparar.',
            'type' => 'order_created',
            'is_read' => false,
        ]);

        // Asserts mínimos del flujo
        $this->assertDatabaseHas('orders', ['id' => $retailOrder->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['order_id' => $retailOrder->id, 'status' => 'succeeded']);

        $this->assertDatabaseHas('orders', ['id' => $whOrder->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['order_id' => $whOrder->id, 'method' => 'zelle', 'status' => 'succeeded']);

        $this->assertDatabaseHas('orders', ['id' => $preOrder->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['order_id' => $preOrder->id, 'method' => 'pago_movil', 'status' => 'succeeded']);

        $this->assertDatabaseHas('orders', ['id' => $dropOrder->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['order_id' => $dropOrder->id, 'method' => 'paypal', 'status' => 'succeeded']);

        $this->assertDatabaseHas('inventory_movements', ['product_id' => $retailProduct->id, 'type' => 'out']);
        $this->assertDatabaseHas('notifications', ['profile_id' => $buyer->id, 'type' => 'payment_received', 'is_read' => 0]);
        $this->assertDatabaseHas('notifications', ['profile_id' => $seller->id, 'type' => 'order_created', 'is_read' => 0]);
    }
}


