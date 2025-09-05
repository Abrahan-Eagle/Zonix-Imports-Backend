<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_product_to_cart()
    {
        $user = User::factory()->create(['role' => 'users']);
        Sanctum::actingAs($user);
        $data = [
            'product_id' => 1,
            'quantity' => 2
        ];
        $response = $this->postJson('/api/buyer/cart/add', $data);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Producto agregado al carrito']);
    }

    public function test_show_cart_contents()
    {
        $user = User::factory()->create(['role' => 'users']);
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/buyer/cart');
        $response->assertStatus(200)
                 ->assertJsonStructure(['cart']);
    }
}
