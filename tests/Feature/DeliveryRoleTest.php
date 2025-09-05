<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Profile;
use App\Models\DeliveryAgent;
use App\Models\OrderDelivery;
use Laravel\Sanctum\Sanctum;

class DeliveryRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_can_list_assigned_orders()
    {
        $delivery = User::factory()->deliveryAgent()->create();
        $deliveryProfile = Profile::factory()->create(['user_id' => $delivery->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $deliveryProfile->id]);
        Sanctum::actingAs($delivery);
        
        // Crear órdenes asignadas
        $user = User::factory()->create(['role' => 'users']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $orders = Order::factory()->count(2)->create([
            'status' => 'on_way',
            'profile_id' => $profile->id
        ]);
        
        // Asignar órdenes al repartidor
        foreach ($orders as $order) {
            OrderDelivery::factory()->create([
                'order_id' => $order->id,
                'agent_id' => $deliveryAgent->id,
                'status' => 'assigned'
            ]);
        }
        
        // Listar órdenes asignadas
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(200);
    }

    public function test_delivery_can_mark_order_as_delivered()
    {
        $delivery = User::factory()->deliveryAgent()->create();
        $deliveryProfile = Profile::factory()->create(['user_id' => $delivery->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $deliveryProfile->id]);
        
        Sanctum::actingAs($delivery);
        
        $user = User::factory()->create(['role' => 'users']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'status' => 'on_way',
            'profile_id' => $profile->id
        ]);
        
        // Crear la relación de entrega
        OrderDelivery::factory()->create([
            'order_id' => $order->id,
            'agent_id' => $deliveryAgent->id,
            'status' => 'assigned'
        ]);
        
        // Marcar orden como entregada
        $response = $this->patchJson("/api/delivery/orders/{$order->id}/status", ['status' => 'delivered']);
        $response->assertStatus(200);
    }
}
