<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Commerce;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $profile;
    protected $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->profile = Profile::factory()->create([
            'user_id' => $this->user->id
        ]);

        $city = City::factory()->create();
        $this->address = Address::factory()->create([
            'profile_id' => $this->profile->id,
            'city_id' => $city->id
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function puede_obtener_resumen_de_checkout_vacio()
    {
        $response = $this->getJson('/api/buyer/checkout/summary');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'El carrito está vacío'
                ]);
    }

    /** @test */
    public function puede_obtener_resumen_de_checkout_con_items()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true,
            'name' => 'Test Product'
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 100.00
        ]);

        $response = $this->getJson('/api/buyer/checkout/summary');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Resumen de checkout obtenido'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'cart_items' => [
                            '*' => ['id', 'product', 'quantity', 'modality', 'unit_price', 'subtotal']
                        ],
                        'summary' => ['subtotal', 'shipping', 'discount', 'total', 'items_count']
                    ]
                ]);

        $this->assertEquals(100.00, $response->json('data.summary.subtotal'));
        $this->assertEquals(1, $response->json('data.summary.items_count'));
    }

    /** @test */
    public function puede_iniciar_checkout_con_datos_validos()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 100.00
        ]);

        $response = $this->postJson('/api/buyer/checkout/initiate', [
            'shipping_address_id' => $this->address->id,
            'delivery_type' => 'delivery'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Checkout iniciado exitosamente'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'shipping_address',
                        'billing_address',
                        'delivery_type',
                        'cart_items',
                        'summary'
                    ]
                ]);
    }

    /** @test */
    public function falla_al_iniciar_checkout_sin_shipping_address()
    {
        $response = $this->postJson('/api/buyer/checkout/initiate', [
            'delivery_type' => 'delivery'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['shipping_address_id']);
    }

    /** @test */
    public function falla_al_iniciar_checkout_con_delivery_type_invalido()
    {
        $response = $this->postJson('/api/buyer/checkout/initiate', [
            'shipping_address_id' => $this->address->id,
            'delivery_type' => 'invalid'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['delivery_type']);
    }

    /** @test */
    public function puede_confirmar_checkout_y_crear_orden()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 100.00
        ]);

        $response = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $this->address->id,
            'delivery_type' => 'delivery',
            'notes' => 'Test order notes'
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Orden creada exitosamente'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'order' => [
                            'id',
                            'order_number',
                            'status',
                            'modality',
                            'delivery_type',
                            'subtotal',
                            'shipping_total',
                            'total',
                            'shipping_address',
                            'commerce',
                            'items'
                        ]
                    ]
                ]);

        // Verificar que la orden se creó
        $this->assertDatabaseHas('orders', [
            'profile_id' => $this->profile->id,
            'status' => 'pending_payment',
            'notes' => 'Test order notes'
        ]);

        // Verificar que el carrito se vació
        $this->assertDatabaseCount('cart_items', 0);

        // Verificar que se redujo el stock
        $product->refresh();
        $this->assertEquals(98, $product->stock);
    }

    /** @test */
    public function falla_al_confirmar_checkout_con_carrito_vacio()
    {
        $response = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $this->address->id,
            'delivery_type' => 'delivery'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'El carrito está vacío'
                ]);
    }

    /** @test */
    public function falla_al_confirmar_checkout_con_producto_sin_stock()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'stock' => 1, // Stock limitado
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 5, // Más del stock disponible
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 250.00
        ]);

        $response = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $this->address->id,
            'delivery_type' => 'delivery'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);

        $this->assertStringContainsString('Stock insuficiente', $response->json('message'));
    }

    /** @test */
    public function falla_al_confirmar_checkout_con_direccion_de_otro_usuario()
    {
        $otherUser = User::factory()->create();
        $otherProfile = Profile::factory()->create(['user_id' => $otherUser->id]);
        $otherCity = City::factory()->create();
        $otherAddress = Address::factory()->create([
            'profile_id' => $otherProfile->id,
            'city_id' => $otherCity->id
        ]);

        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 50.00
        ]);

        $response = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $otherAddress->id,
            'delivery_type' => 'delivery'
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('no pertenece al usuario', $response->json('message'));
    }

    /** @test */
    public function puede_usar_direccion_de_facturacion_diferente()
    {
        $city = City::factory()->create();
        $billingAddress = Address::factory()->create([
            'profile_id' => $this->profile->id,
            'city_id' => $city->id
        ]);

        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 50.00
        ]);

        $response = $this->postJson('/api/buyer/checkout/confirm', [
            'shipping_address_id' => $this->address->id,
            'delivery_type' => 'delivery',
            'billing_address_id' => $billingAddress->id
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'shipping_address_id' => $this->address->id,
            'billing_address_id' => $billingAddress->id
        ]);
    }

}

