<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_commerce_can_list_show_and_update_order_status()
    {
        $user = User::factory()->create(['role' => 'commerce']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        $order = Order::factory()->create(['commerce_id' => $commerce->id]);
        $this->actingAs($user, 'sanctum');

        // Listar órdenes
        $response = $this->getJson('/api/commerce/orders');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());

        // Mostrar orden
        $response = $this->getJson('/api/commerce/orders/' . $order->id);
        $response->assertStatus(200);
        $this->assertEquals($order->id, $response->json('id'));

        // Actualizar estado de la orden
        $response = $this->putJson('/api/commerce/orders/' . $order->id . '/status', [
            'status' => 'preparing'
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $order->refresh();
        $this->assertEquals('preparing', $order->status);
    }
} 