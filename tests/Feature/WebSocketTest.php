<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\Profile;
use App\Events\OrderCreated;
use App\Events\PaymentValidated;
use App\Events\OrderStatusChanged;
use App\Events\DeliveryLocationUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;

class WebSocketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $commerce;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario y perfil para las pruebas
        $this->user = User::factory()->create([
            'role' => 'users',
            'google_id' => 'test_google_id_123'
        ]);
        
        // Crear perfil para el usuario
        Profile::factory()->create(['user_id' => $this->user->id]);
        $this->user->refresh();

        $this->commerce = Commerce::factory()->create([
            'business_name' => 'Test Restaurant',
            'address' => 'Test Address',
            'phone' => '1234567890',
            'open' => true,
        ]);

        $this->product = Product::factory()->create([
            'commerce_id' => $this->commerce->id,
            'name' => 'Test Product',
            'description' => 'Test product description',
            'price' => 10.00,
            'available' => true,
        ]);
    }

    /** @test */
    public function it_can_broadcast_order_created_event()
    {
        Event::fake();

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'pending_payment',
            'total' => 25.50,
        ]);

        // Disparar evento
        event(new OrderCreated($order));

        // Verificar que el evento fue disparado
        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    /** @test */
    public function it_can_broadcast_payment_validated_event()
    {
        Event::fake();

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'pending_payment',
        ]);

        // Disparar evento de pago validado
        event(new PaymentValidated($order, true, 'Test Commerce'));

        // Verificar que el evento fue disparado
        Event::assertDispatched(PaymentValidated::class, function ($event) use ($order) {
            return $event->order->id === $order->id && $event->isValidated === true;
        });
    }

    /** @test */
    public function it_can_broadcast_order_status_changed_event()
    {
        Event::fake();

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'pending_payment',
        ]);

        // Disparar evento de cambio de estado
        event(new OrderStatusChanged($order, 'pending_payment', 'paid'));

        // Verificar que el evento fue disparado
        Event::assertDispatched(OrderStatusChanged::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    /** @test */
    public function it_can_broadcast_delivery_location_updated_event()
    {
        Event::fake();

        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'on_way',
        ]);

        // Disparar evento de actualización de ubicación
        event(new DeliveryLocationUpdated(
            $order->id,
            1, // delivery_agent_id
            -12.3456, // latitude
            -78.9012, // longitude
            now()->addMinutes(30) // estimated_arrival
        ));

        // Verificar que el evento fue disparado
        Event::assertDispatched(DeliveryLocationUpdated::class, function ($event) use ($order) {
            return $event->orderId === $order->id && 
                   $event->latitude === -12.3456 && 
                   $event->longitude === -78.9012;
        });
    }

    /** @test */
    public function it_can_access_broadcasting_channels()
    {
        $this->actingAs($this->user);

        // Verificar acceso al canal de usuario
        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'App.Models.User.' . $this->user->id,
            'socket_id' => '123.456'
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_access_order_channels()
    {
        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
        ]);

        $this->actingAs($this->user);

        // Verificar acceso al canal de orden
        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'orders.' . $order->id,
            'socket_id' => '123.456'
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_denies_access_to_unauthorized_channels()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($this->user);

        // Intentar acceder al canal de otro usuario
        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'App.Models.User.' . $otherUser->id,
            'socket_id' => '123.456'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_handle_websocket_connection_with_valid_credentials()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/websocket/connect', [
            'app_id' => 'zonix-eats-app',
            'key' => 'zonix-eats-key',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data']);
    }

    /** @test */
    public function it_rejects_websocket_connection_with_invalid_credentials()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/websocket/connect', [
            'app_id' => 'invalid-app',
            'key' => 'invalid-key',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_subscribe_to_user_channel()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/websocket/subscribe', [
            'channel' => 'App.Models.User.' . $this->user->id,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_subscribe_to_order_channel()
    {
        $order = Order::factory()->create([
            'profile_id' => $this->user->profile->id,
            'commerce_id' => $this->commerce->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson('/api/websocket/subscribe', [
            'channel' => 'orders.' . $order->id,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_unsubscribe_from_channel()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/websocket/unsubscribe', [
            'channel' => 'App.Models.User.' . $this->user->id,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_handle_multiple_concurrent_connections()
    {
        $this->actingAs($this->user);

        // Simular múltiples conexiones
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson('/api/websocket/connect', [
                'app_id' => 'zonix-eats-app',
                'key' => 'zonix-eats-key',
            ]);
        }

        // Verificar que todas las conexiones fueron exitosas
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function it_can_handle_connection_timeout()
    {
        $this->actingAs($this->user);

        // Simular timeout de conexión
        $response = $this->postJson('/api/websocket/connect', [
            'app_id' => 'zonix-eats-app',
            'key' => 'zonix-eats-key',
            'timeout' => 1, // 1 segundo
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_handle_reconnection_after_disconnect()
    {
        $this->actingAs($this->user);

        // Primera conexión
        $response1 = $this->postJson('/api/websocket/connect', [
            'app_id' => 'zonix-eats-app',
            'key' => 'zonix-eats-key',
        ]);

        $response1->assertStatus(200);

        // Desconexión
        $response2 = $this->postJson('/api/websocket/disconnect', []);

        $response2->assertStatus(200);

        // Reconexión
        $response3 = $this->postJson('/api/websocket/connect', [
            'app_id' => 'zonix-eats-app',
            'key' => 'zonix-eats-key',
        ]);

        $response3->assertStatus(200);
    }
} 