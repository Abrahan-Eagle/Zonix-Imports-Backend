<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Commerce;
use Laravel\Sanctum\Sanctum;

class EcommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_complete_purchase_flow()
    {
        // Crear usuario buyer y autenticar
        $user = User::factory()->create(['role' => 'users']);
        // Asociar perfil al usuario buyer
        $profile = $user->profile()->create([
            'firstName' => 'Comprador',
            'lastName' => 'Test',
            'address' => 'Calle Falsa 123',
            'status' => 'completeData',
        ]);
        Sanctum::actingAs($user);

        // Crear restaurante y producto
        $commerce = Commerce::factory()->withProfile()->create(['open' => true]);
        $product = Product::factory()->withCommerce()->create(['commerce_id' => $commerce->id, 'available' => true]);

        // Listar restaurantes
        $this->getJson('/api/buyer/restaurants')->assertStatus(200);

        // Ver detalles de restaurante
        $this->getJson("/api/buyer/restaurants/{$commerce->id}")->assertStatus(200);

        // Ver detalles de producto
        $this->getJson("/api/buyer/products/{$product->id}")->assertStatus(200);

        // Agregar producto al carrito
        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2
        ])->assertStatus(200);

        // Ver carrito
        $this->getJson('/api/buyer/cart')->assertStatus(200);

        // Crear orden
        $orderData = [
            'products' => [
                ['id' => $product->id, 'quantity' => 2]
            ],
            'commerce_id' => $commerce->id,
            'delivery_type' => 'delivery',
            'total' => $product->price * 2,
            'address' => 'Calle Falsa 123'
        ];
        $this->postJson('/api/buyer/orders', $orderData)
            ->assertStatus(201)
            ->assertJsonFragment(['message' => 'Orden creada exitosamente']);

        // Listar órdenes
        $this->getJson('/api/buyer/orders')->assertStatus(200);
    }
}
