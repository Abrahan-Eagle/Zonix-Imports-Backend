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

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function flujo_completo_de_checkout_exitoso()
    {
        // 1. Preparar datos
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        
        $city = City::factory()->create();
        $shippingAddress = Address::factory()->create([
            'profile_id' => $profile->id,
            'city_id' => $city->id
        ]);

        $commerce = Commerce::factory()->create(['business_name' => 'Test Commerce']);
        $product1 = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'name' => 'Product 1',
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);
        $product2 = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'name' => 'Product 2',
            'base_price' => 75.00,
            'stock' => 50,
            'available' => true
        ]);

        Sanctum::actingAs($user);

        // 2. Agregar productos al carrito
        $response1 = $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product1->id,
            'quantity' => 2,
            'modality' => 'retail'
        ]);
        $response1->assertSuccessful(); // 200 o 201

        $response2 = $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product2->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);
        $response2->assertSuccessful(); // 200 o 201

        // 3. Ver el carrito
        $response3 = $this->getJson('/api/buyer/cart');
        $response3->assertStatus(200);
        $this->assertEquals(2, $response3->json('data.summary.items_count'));
        $this->assertEquals(175.00, (float)$response3->json('data.summary.subtotal')); // 100 + 75

        // 4. Obtener resumen de checkout
        $response4 = $this->getJson('/api/buyer/checkout/summary');
        $response4->assertStatus(200);
        $this->assertEquals(175.00, $response4->json('data.summary.subtotal'));
        $this->assertEquals(5.00, $response4->json('data.summary.shipping')); // 1 comercio
        $this->assertEquals(2.50, $response4->json('data.summary.discount')); // 50% de shipping (subtotal > 100)
        $this->assertEquals(177.50, $response4->json('data.summary.total')); // 175 + 5 - 2.5

        // 5. Iniciar checkout
        $response5 = $this->postJson('/api/buyer/checkout/initiate', [
            'shipping_address_id' => $shippingAddress->id,
            'delivery_type' => 'delivery'
        ]);
        $response5->assertStatus(200);
        $this->assertEquals('delivery', $response5->json('data.delivery_type'));
        $this->assertEquals($shippingAddress->id, $response5->json('data.shipping_address.id'));

        // 6. Confirmar checkout
        $response6 = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $shippingAddress->id,
            'delivery_type' => 'delivery',
            'notes' => 'Entregar en horario de oficina'
        ]);
        $response6->assertStatus(201);

        $orderId = $response6->json('data.order.id');
        $this->assertNotNull($orderId);
        $this->assertEquals('pending_payment', $response6->json('data.order.status'));
        $this->assertEquals(175.00, $response6->json('data.order.subtotal'));
        $this->assertEquals(5.00, $response6->json('data.order.shipping_total'));
        $this->assertEquals(2.50, $response6->json('data.order.discount_total'));
        $this->assertEquals(177.50, $response6->json('data.order.total'));
        $this->assertCount(2, $response6->json('data.order.items'));

        // 7. Verificar que el carrito está vacío
        $response7 = $this->getJson('/api/buyer/cart');
        $response7->assertStatus(200);
        $this->assertEquals(0, $response7->json('data.summary.items_count'));

        // 8. Verificar que se redujo el stock
        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(98, $product1->stock); // 100 - 2
        $this->assertEquals(49, $product2->stock); // 50 - 1

        // 9. Verificar que la orden existe en BD
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'profile_id' => $profile->id,
            'commerce_id' => $commerce->id,
            'status' => 'pending_payment',
            'subtotal' => 175.00,
            'shipping_total' => 5.00,
            'discount_total' => 2.50,
            'total' => 177.50,
            'shipping_address_id' => $shippingAddress->id,
            'notes' => 'Entregar en horario de oficina'
        ]);

        // 10. Verificar order_items
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 50.00,
            'subtotal' => 100.00
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 75.00,
            'subtotal' => 75.00
        ]);
    }

    /** @test */
    public function flujo_checkout_con_pickup_no_cobra_envio()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $profile->id,
            'city_id' => $city->id
        ]);

        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 150.00,
            'stock' => 100,
            'available' => true
        ]);

        Sanctum::actingAs($user);

        // Agregar al carrito
        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);

        // Checkout con pickup
        $response = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $address->id,
            'delivery_type' => 'pickup' // Pickup
        ]);

        $response->assertStatus(201);
        $this->assertEquals(0, $response->json('data.order.shipping_total')); // Sin envío
        $this->assertEquals(150.00, $response->json('data.order.total')); // Solo subtotal
    }

    /** @test */
    public function flujo_checkout_con_multiples_comercios_calcula_envio_correcto()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $profile->id,
            'city_id' => $city->id
        ]);

        $commerce1 = Commerce::factory()->create(['business_name' => 'Commerce 1']);
        $commerce2 = Commerce::factory()->create(['business_name' => 'Commerce 2']);

        $product1 = Product::factory()->create([
            'commerce_id' => $commerce1->id,
            'base_price' => 60.00,
            'stock' => 100,
            'available' => true
        ]);

        $product2 = Product::factory()->create([
            'commerce_id' => $commerce2->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);

        Sanctum::actingAs($user);

        // Agregar productos de diferentes comercios
        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product1->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);

        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product2->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);

        // Checkout
        $response = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $address->id,
            'delivery_type' => 'delivery'
        ]);

        $response->assertStatus(201);
        
        // Subtotal: 60 + 50 = 110
        // Envío: 2 comercios x $5 = $10
        // Descuento: 50% de $10 = $5 (porque subtotal > 100)
        // Total: 110 + 10 - 5 = 115
        
        $this->assertEquals(110.00, $response->json('data.order.subtotal'));
        $this->assertEquals(10.00, $response->json('data.order.shipping_total'));
        $this->assertEquals(5.00, $response->json('data.order.discount_total'));
        $this->assertEquals(115.00, $response->json('data.order.total'));
    }
}

