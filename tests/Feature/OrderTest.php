<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\Address;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $profile;
    protected $commerce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->profile = Profile::factory()->create(['user_id' => $this->user->id]);
        $this->commerce = Commerce::factory()->create();

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function puede_listar_ordenes_del_comprador()
    {
        Order::factory()->count(3)->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id
        ]);

        $response = $this->getJson('/api/buyer/orders');

        $response->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'data' => [
                        'orders' => [
                            '*' => ['id', 'order_number', 'status', 'total', 'commerce', 'created_at']
                        ],
                        'pagination'
                    ]
                ]);

        $this->assertCount(3, $response->json('data.orders'));
    }

    /** @test */
    public function puede_filtrar_ordenes_por_status()
    {
        Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'paid'
        ]);

        Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'pending_payment'
        ]);

        $response = $this->getJson('/api/buyer/orders?status=paid');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.orders'));
    }

    /** @test */
    public function puede_obtener_detalle_de_orden()
    {
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $this->profile->id,
            'city_id' => $city->id
        ]);

        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id,
            'shipping_address_id' => $address->id
        ]);

        $product = Product::factory()->create(['commerce_id' => $this->commerce->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id
        ]);

        $response = $this->getJson("/api/buyer/orders/{$order->id}");

        $response->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'data' => [
                        'order' => [
                            'id', 'order_number', 'status', 'total',
                            'commerce', 'shipping_address', 'items', 'payments'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function falla_al_obtener_orden_de_otro_usuario()
    {
        $otherProfile = Profile::factory()->create();
        $order = Order::factory()->create([
            'profile_id' => $otherProfile->id,
            'commerce_id' => $this->commerce->id
        ]);

        $response = $this->getJson("/api/buyer/orders/{$order->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function puede_obtener_tracking_de_orden()
    {
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $this->profile->id,
            'city_id' => $city->id
        ]);

        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id,
            'shipping_address_id' => $address->id,
            'status' => 'on_way',
            'tracking_number' => 'TRACK123'
        ]);

        $response = $this->getJson("/api/buyer/orders/{$order->id}/tracking");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'order_id' => $order->id,
                        'current_status' => 'on_way',
                        'tracking_number' => 'TRACK123'
                    ]
                ])
                ->assertJsonStructure([
                    'data' => ['order_id', 'current_status', 'tracking_number', 'timeline']
                ]);
    }

    /** @test */
    public function seller_puede_listar_sus_ordenes()
    {
        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $sellerCommerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id]);

        Order::factory()->count(2)->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $sellerCommerce->id
        ]);

        Sanctum::actingAs($sellerUser);

        $response = $this->getJson('/api/seller/orders');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.orders'));
    }

    /** @test */
    public function seller_puede_obtener_detalle_de_orden()
    {
        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $sellerCommerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id]);

        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $this->profile->id,
            'city_id' => $city->id
        ]);

        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $sellerCommerce->id,
            'shipping_address_id' => $address->id
        ]);

        $product = Product::factory()->create(['commerce_id' => $sellerCommerce->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id
        ]);

        Sanctum::actingAs($sellerUser);

        $response = $this->getJson("/api/seller/orders/{$order->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'order' => ['id', 'status', 'buyer', 'items']
                    ]
                ]);
    }

    /** @test */
    public function seller_puede_actualizar_estado_de_orden()
    {
        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $sellerCommerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id]);

        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $sellerCommerce->id,
            'status' => 'paid'
        ]);

        Sanctum::actingAs($sellerUser);

        $response = $this->putJson("/api/seller/orders/{$order->id}/status", [
            'status' => 'preparing',
            'tracking_number' => 'TRACK999'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Estado de orden actualizado'
                ]);

        $order->refresh();
        $this->assertEquals('preparing', $order->status);
        $this->assertEquals('TRACK999', $order->tracking_number);
    }

    /** @test */
    public function falla_al_actualizar_con_transicion_invalida()
    {
        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $sellerCommerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id]);

        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $sellerCommerce->id,
            'status' => 'pending_payment'
        ]);

        Sanctum::actingAs($sellerUser);

        $response = $this->putJson("/api/seller/orders/{$order->id}/status", [
            'status' => 'delivered'
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function seller_no_puede_actualizar_orden_de_otro_comercio()
    {
        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $sellerCommerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id]);

        $otherCommerce = Commerce::factory()->create();
        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $otherCommerce->id
        ]);

        Sanctum::actingAs($sellerUser);

        $response = $this->putJson("/api/seller/orders/{$order->id}/status", [
            'status' => 'preparing'
        ]);

        $response->assertStatus(400);
    }
}

