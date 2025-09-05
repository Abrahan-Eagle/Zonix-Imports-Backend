<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Commerce;
use App\Models\Order;
use Laravel\Sanctum\Sanctum;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_commerces_and_orders()
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        // Crear comercios y órdenes
        $commerces = Commerce::factory()->count(2)->create();
        $orders = Order::factory()->count(2)->create();

        // Listar comercios (simulación de endpoint admin)
        $response = $this->getJson('/api/admin/commerces');
        $response->assertStatus(200);

        // Listar órdenes (simulación de endpoint admin)
        $response = $this->getJson('/api/admin/orders');
        $response->assertStatus(200);
    }

    public function test_admin_can_update_order_status()
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $order = Order::factory()->create(['status' => 'pending_payment']);

        // Cambiar estado de la orden (simulación de endpoint admin)
        $response = $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'preparing']);
        $response->assertStatus(200);
    }
}
