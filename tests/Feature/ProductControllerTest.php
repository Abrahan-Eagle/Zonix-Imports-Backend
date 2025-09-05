<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function actingAsBuyer()
    {
        $user = \App\Models\User::factory()->create(['role' => 'users']);
        \Laravel\Sanctum\Sanctum::actingAs($user);
        return $user;
    }

    public function test_can_show_product_details()
    {
        $this->actingAsBuyer();
        $product = \App\Models\Product::factory()->withCommerce()->create();
        $response = $this->getJson("/api/buyer/products/{$product->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $product->id]);
    }

    public function test_show_product_not_found()
    {
        $this->actingAsBuyer();
        $response = $this->getJson('/api/buyer/products/999');
        $response->assertStatus(404);
    }
}
