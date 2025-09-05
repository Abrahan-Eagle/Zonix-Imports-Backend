<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\DeliveryAgent;
use App\Models\Order;
use App\Models\OrderDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_can_list_accept_and_update_order_status()
    {
        $user = User::factory()->create(['role' => 'delivery']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $delivery = DeliveryAgent::factory()->create(['profile_id' => $profile->id]);
        // Eliminar todas las órdenes antes de crear la de prueba
        \App\Models\Order::query()->delete();
        $order = Order::factory()->create(['status' => 'paid']);
        $this->actingAs($user, 'sanctum');

        // Listar órdenes asignadas (debe estar vacío)
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json());

        // Listar órdenes disponibles
        $response = $this->getJson('/api/delivery/available-orders');
        $response->assertStatus(200);
        fwrite(STDERR, print_r($response->json(), true));
        $this->assertCount(1, $response->json('data'));

        // Aceptar orden
        $response = $this->postJson('/api/delivery/orders/' . $order->id . '/accept');
        $response->assertStatus(200)->assertJson(['message' => 'Orden aceptada']);

        // Listar órdenes asignadas (debe tener 1)
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());

        // Actualizar estado de la orden
        $response = $this->patchJson('/api/delivery/orders/' . $order->id . '/status', [
            'status' => 'on_way'
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $order->refresh();
        $this->assertEquals('on_way', $order->status);
    }
} 