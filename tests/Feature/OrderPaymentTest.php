<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class OrderPaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $commerce;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario con perfil
        $this->user = User::factory()->create(['role' => 'users']);
        $this->profile = Profile::factory()->create(['user_id' => $this->user->id]);
        
        // Crear comercio con perfil
        $commerceUser = User::factory()->create(['role' => 'commerce']);
        $commerceProfile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $this->commerce = Commerce::factory()->create(['profile_id' => $commerceProfile->id]);

        // Crear productos para el comercio
        $this->products = Product::factory()->count(3)->create([
            'commerce_id' => $this->commerce->id,
        ]);
    }

    /** @test */
    public function user_can_create_order()
    {
        Sanctum::actingAs($this->user);

        $orderData = [
            'commerce_id' => $this->commerce->id,
            'delivery_type' => 'pickup',
            'total' => 20.00,
            'products' => [
                [
                    'id' => $this->products[0]->id,
                    'quantity' => 2
                ]
            ],
            'notes' => 'Test order'
        ];

        $response = $this->postJson('/api/buyer/orders', $orderData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'profile_id',
                         'commerce_id',
                         'status',
                         'total',
                         'delivery_type',
                         'notes',
                         'created_at',
                         'products'
                     ]
                 ]);

        $this->assertDatabaseHas('orders', [
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'pending_payment',
            'total' => 20.00,
        ]);
    }

    /** @test */
    public function user_cannot_create_order_with_invalid_commerce()
    {
        Sanctum::actingAs($this->user);

        $orderData = [
            'commerce_id' => 99999, // Commerce inexistente
            'products' => [
                [
                    'id' => $this->products[0]->id,
                    'quantity' => 2
                ]
            ],
            'delivery_type' => 'pickup',
            'notes' => 'Test order'
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['commerce_id']);
    }

    /** @test */
    public function user_cannot_create_order_with_invalid_product()
    {
        Sanctum::actingAs($this->user);

        $orderData = [
            'commerce_id' => $this->commerce->id,
            'products' => [
                [
                    'id' => 99999, // Producto inexistente
                    'quantity' => 2
                ]
            ],
            'delivery_type' => 'pickup',
            'total' => 20.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['products.0.id']);
    }

    /** @test */
    public function user_can_upload_payment_proof()
    {
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'pending_payment',
        ]);

        $file = UploadedFile::fake()->image('payment_proof.jpg');

        $response = $this->postJson("/api/buyer/orders/{$order->id}/payment-proof", [
            'payment_proof' => $file,
            'payment_method' => 'pago_movil',
            'reference_number' => 'REF123456',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_proof' => 'payment_proofs/' . $file->hashName(),
            'payment_method' => 'pago_movil',
            'reference_number' => 'REF123456',
        ]);
    }

    /** @test */
    public function user_cannot_upload_payment_proof_for_completed_order()
    {
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'delivered',
        ]);

        $file = UploadedFile::fake()->image('payment_proof.jpg');

        $response = $this->postJson("/api/buyer/orders/{$order->id}/payment-proof", [
            'payment_proof' => $file,
            'payment_method' => 'pago_movil',
            'reference_number' => 'REF123456',
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'No se puede subir comprobante para una orden completada']);
    }

    /** @test */
    public function commerce_can_validate_payment()
    {
        $commerceUser = User::factory()->create(['role' => 'commerce']);
        $commerceProfile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $commerceProfile->id]);
        Sanctum::actingAs($commerceUser);

        $order = Order::factory()->create([
            'commerce_id' => $commerce->id,
            'status' => 'pending_payment',
            'payment_proof' => 'payment_proofs/test.jpg',
        ]);

        $validationData = [
            'is_valid' => true,
            'notes' => 'Payment validated successfully'
        ];

        $response = $this->postJson("/api/commerce/orders/{$order->id}/validate-payment", $validationData);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_validated_at' => now(),
        ]);
    }

    /** @test */
    public function commerce_can_reject_payment()
    {
        $commerceUser = User::factory()->create(['role' => 'commerce']);
        $commerceProfile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $commerceProfile->id]);
        Sanctum::actingAs($commerceUser);

        $order = Order::factory()->create([
            'commerce_id' => $commerce->id,
            'status' => 'pending_payment',
            'payment_proof' => 'payment_proofs/test.jpg',
        ]);

        $validationData = [
            'is_valid' => false,
            'rejection_reason' => 'Comprobante ilegible',
        ];

        $response = $this->postJson("/api/commerce/orders/{$order->id}/validate-payment", $validationData);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Comprobante ilegible',
        ]);
    }

    /** @test */
    public function commerce_can_update_order_status()
    {
        $commerceUser = User::factory()->create(['role' => 'commerce']);
        $commerceProfile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $commerceProfile->id]);
        Sanctum::actingAs($commerceUser);

        $order = Order::factory()->create([
            'commerce_id' => $commerce->id,
            'status' => 'paid',
        ]);

        $statusData = [
            'status' => 'preparing',
        ];

        $response = $this->putJson("/api/commerce/orders/{$order->id}/status", $statusData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'preparing',
        ]);
    }

    /** @test */
    public function user_can_view_their_orders()
    {
        Sanctum::actingAs($this->user);

        Order::factory()->count(3)->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
        ]);

        $response = $this->getJson('/api/buyer/orders');

        $response->assertStatus(200);

        $this->assertIsArray($response->json());
    }

    /** @test */
    public function user_can_view_specific_order()
    {
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
        ]);

        $response = $this->getJson("/api/buyer/orders/{$order->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function user_cannot_view_other_user_order()
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherProfile = Profile::factory()->create(['user_id' => $otherUser->id]);
        $order = Order::factory()->create([
            'profile_id' => $otherProfile->id,
            'commerce_id' => $this->commerce->id,
        ]);

        $response = $this->getJson("/api/buyer/orders/{$order->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function commerce_can_view_their_orders()
    {
        $commerceUser = User::factory()->create(['role' => 'commerce']);
        $commerceProfile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $commerceProfile->id]);
        Sanctum::actingAs($commerceUser);

        Order::factory()->count(3)->create([
            'commerce_id' => $commerce->id,
        ]);

        $response = $this->getJson('/api/commerce/orders');

        $response->assertStatus(200);

        $this->assertIsArray($response->json());
    }

    /** @test */
    public function order_calculates_total_correctly()
    {
        Sanctum::actingAs($this->user);

        $orderData = [
            'commerce_id' => $this->commerce->id,
            'delivery_type' => 'pickup',
            'total' => 20.00,
            'products' => [
                [
                    'id' => $this->products[0]->id,
                    'quantity' => 2
                ]
            ],
            'notes' => 'Test order'
        ];

        $response = $this->postJson('/api/buyer/orders', $orderData);

        $response->assertStatus(201);

        $orderId = $response->json('data.id');
        
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $this->products[0]->id,
            'quantity' => 2,
            'unit_price' => $this->products[0]->price
        ]);
    }

    /** @test */
    public function order_with_delivery_includes_delivery_fee()
    {
        Sanctum::actingAs($this->user);

        $orderData = [
            'commerce_id' => $this->commerce->id,
            'delivery_type' => 'delivery',
            'total' => 25.00,
            'products' => [
                [
                    'id' => $this->products[0]->id,
                    'quantity' => 1
                ]
            ],
            'delivery_address' => 'Test Delivery Address',
            'notes' => 'Test order'
        ];

        $response = $this->postJson('/api/buyer/orders', $orderData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'delivery_type' => 'delivery',
            'delivery_address' => 'Test Delivery Address',
        ]);
    }

    /** @test */
    public function order_can_be_cancelled_by_user()
    {
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'pending_payment',
        ]);

        $response = $this->postJson("/api/buyer/orders/{$order->id}/cancel", [
            'reason' => 'Changed my mind',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Changed my mind',
        ]);
    }

    /** @test */
    public function order_cannot_be_cancelled_when_in_preparation()
    {
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'preparing',
        ]);

        $response = $this->postJson("/api/buyer/orders/{$order->id}/cancel", [
            'reason' => 'Changed my mind',
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'No se puede cancelar una orden en preparación']);
    }
} 