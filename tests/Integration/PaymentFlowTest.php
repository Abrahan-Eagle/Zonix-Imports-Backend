<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Address;
use App\Models\Product;
use App\Models\Commerce;
use App\Models\City;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function flujo_completo_checkout_y_pago_stripe()
    {
        // 1. Preparar datos
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $profile->id,
            'city_id' => $city->id
        ]);

        $commerce = Commerce::factory()->create([
            'business_name' => 'Test Commerce',
            'payment_methods' => json_encode(['stripe', 'paypal'])
        ]);
        
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 100.00,
            'stock' => 50,
            'available' => true
        ]);

        Sanctum::actingAs($user);

        // 2. Agregar al carrito
        $cart = $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);
        $cart->assertSuccessful();

        // 3. Confirmar checkout
        $checkout = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $address->id,
            'delivery_type' => 'delivery'
        ]);
        $checkout->assertStatus(201);
        
        $orderId = $checkout->json('data.order.id');
        $this->assertNotNull($orderId);

        // 4. Obtener métodos de pago
        $methods = $this->getJson("/api/buyer/payments/methods?order_id=$orderId");
        $methods->assertStatus(200);
        $this->assertCount(2, $methods->json('data.methods')); // stripe, paypal

        // 5. Iniciar pago con Stripe
        $payment = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $orderId,
            'payment_method' => 'stripe'
        ]);
        $payment->assertStatus(201);
        
        $paymentId = $payment->json('data.payment.id');
        $paymentIntentId = $payment->json('data.payment_intent_id');
        $this->assertNotNull($paymentId);
        $this->assertNotNull($paymentIntentId);

        // 6. Verificar estado del pago
        $status = $this->getJson("/api/buyer/payments/$paymentId/status");
        $status->assertStatus(200);
        $this->assertEquals('pending', $status->json('data.payment.status'));

        // 7. Verificar que el carrito está vacío
        $cartCheck = $this->getJson('/api/buyer/cart');
        $this->assertEquals(0, $cartCheck->json('data.summary.items_count'));
    }

    /** @test */
    public function flujo_completo_pago_manual_pago_movil()
    {
        // 1. Preparar datos
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $profile->id,
            'city_id' => $city->id
        ]);

        $commerce = Commerce::factory()->create([
            'payment_methods' => json_encode(['pago_movil', 'zelle'])
        ]);
        
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 50.00,
            'stock' => 50,
            'available' => true
        ]);

        Sanctum::actingAs($user);

        // 2. Agregar al carrito y checkout
        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);

        $checkout = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $address->id,
            'delivery_type' => 'pickup' // Pickup = sin envío
        ]);
        
        $orderId = $checkout->json('data.order.id');

        // 3. Registrar pago manual
        $payment = $this->postJson('/api/buyer/payments/manual', [
            'order_id' => $orderId,
            'payment_method' => 'pago_movil',
            'receipt_url' => 'https://example.com/receipt.jpg',
            'reference' => '123456789',
            'bank' => 'Banco de Venezuela',
            'phone' => '04241234567'
        ]);

        $payment->assertStatus(201);
        $this->assertEquals('pago_movil', $payment->json('data.payment.method'));
        $this->assertEquals('pending', $payment->json('data.payment.status'));
        $this->assertEquals('https://example.com/receipt.jpg', $payment->json('data.payment.receipt_url'));

        // 4. Verificar que el pago está pendiente
        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'method' => 'pago_movil',
            'status' => 'pending',
            'receipt_url' => 'https://example.com/receipt.jpg'
        ]);
    }

    /** @test */
    public function flujo_pago_paypal_completo()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $profile->id,
            'city_id' => $city->id
        ]);

        $commerce = Commerce::factory()->create([
            'payment_methods' => json_encode(['paypal'])
        ]);
        
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 75.00,
            'stock' => 50,
            'available' => true
        ]);

        Sanctum::actingAs($user);

        // Carrito y checkout
        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail'
        ]);

        $checkout = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $address->id,
            'delivery_type' => 'delivery'
        ]);
        
        $orderId = $checkout->json('data.order.id');

        // Iniciar pago con PayPal
        $payment = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $orderId,
            'payment_method' => 'paypal'
        ]);

        $payment->assertStatus(201);
        $this->assertArrayHasKey('approval_url', $payment->json('data'));
        $this->assertArrayHasKey('paypal_order_id', $payment->json('data'));
    }

    /** @test */
    public function flujo_pago_binance_completo()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $profile->id,
            'city_id' => $city->id
        ]);

        $commerce = Commerce::factory()->create([
            'payment_methods' => json_encode(['binance'])
        ]);
        
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 200.00,
            'stock' => 50,
            'available' => true
        ]);

        Sanctum::actingAs($user);

        // Carrito y checkout
        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);

        $checkout = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $address->id,
            'delivery_type' => 'delivery'
        ]);
        
        $orderId = $checkout->json('data.order.id');

        // Iniciar pago con Binance
        $payment = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $orderId,
            'payment_method' => 'binance',
            'crypto_currency' => 'USDT'
        ]);

        $payment->assertStatus(201);
        $this->assertArrayHasKey('checkout_url', $payment->json('data'));
        $this->assertArrayHasKey('binance_order_id', $payment->json('data'));
    }
}

