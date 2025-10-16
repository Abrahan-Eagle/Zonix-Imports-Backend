<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Address;
use App\Models\Product;
use App\Models\Commerce;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function flujo_completo_desde_checkout_hasta_entrega()
    {
        // 1. Preparar datos
        $buyerUser = User::factory()->create();
        $buyerProfile = Profile::factory()->create(['user_id' => $buyerUser->id]);

        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $commerce = Commerce::factory()->create([
            'profile_id' => $sellerProfile->id,
            'payment_methods' => json_encode(['stripe'])
        ]);

        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $buyerProfile->id,
            'city_id' => $city->id
        ]);

        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 100.00,
            'stock' => 50,
            'available' => true
        ]);

        // 2. Comprador: Agregar al carrito y checkout
        Sanctum::actingAs($buyerUser);

        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail'
        ])->assertSuccessful();

        $checkout = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $address->id,
            'delivery_type' => 'delivery'
        ])->assertStatus(201);

        $orderId = $checkout->json('data.order.id');

        // 3. Comprador: Ver sus órdenes
        $orders = $this->getJson('/api/buyer/orders');
        $orders->assertStatus(200);
        $this->assertCount(1, $orders->json('data.orders'));

        // 4. Comprador: Ver detalle de la orden
        $detail = $this->getJson("/api/buyer/orders/$orderId");
        $detail->assertStatus(200);
        $this->assertEquals('pending_payment', $detail->json('data.order.status'));

        // 5. Comprador: Iniciar pago
        $payment = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $orderId,
            'payment_method' => 'stripe'
        ])->assertStatus(201);

        $paymentId = $payment->json('data.payment.id');

        // 6. Simular pago exitoso (actualizar directamente)
        \App\Models\Payment::find($paymentId)->update(['status' => 'succeeded']);
        \App\Models\Order::find($orderId)->update(['status' => 'paid']);

        // 7. Vendedor: Ver sus órdenes
        Sanctum::actingAs($sellerUser);

        $sellerOrders = $this->getJson('/api/seller/orders');
        $sellerOrders->assertStatus(200);
        $this->assertCount(1, $sellerOrders->json('data.orders'));

        // 8. Vendedor: Actualizar a "preparing"
        $this->putJson("/api/seller/orders/$orderId/status", [
            'status' => 'preparing',
            'tracking_number' => 'TRACK001'
        ])->assertStatus(200);

        // 9. Vendedor: Actualizar a "on_way"
        $this->putJson("/api/seller/orders/$orderId/status", [
            'status' => 'on_way'
        ])->assertStatus(200);

        // 10. Vendedor: Actualizar a "delivered"
        $this->putJson("/api/seller/orders/$orderId/status", [
            'status' => 'delivered'
        ])->assertStatus(200);

        // 11. Comprador: Ver tracking
        Sanctum::actingAs($buyerUser);

        $tracking = $this->getJson("/api/buyer/orders/$orderId/tracking");
        $tracking->assertStatus(200);
        $this->assertEquals('delivered', $tracking->json('data.current_status'));
        $this->assertEquals('TRACK001', $tracking->json('data.tracking_number'));
    }
}

