<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Commerce;
use App\Models\Product;
use Laravel\Sanctum\Sanctum;

class CommerceProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function actingAsCommerce()
    {
        $user = User::factory()->commerce()->create();
        $profile = \App\Models\Profile::factory()->create(['user_id' => $user->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        $profile->refresh();
        \Laravel\Sanctum\Sanctum::actingAs($user);
        return [$user, $commerce];
    }

    public function test_commerce_can_list_own_products()
    {
        [$user, $commerce] = $this->actingAsCommerce();
        $products = Product::factory()->count(3)->create(['commerce_id' => $commerce->id]);
        $response = $this->getJson('/api/commerce/products');
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $products[0]->id]);
    }

    public function test_commerce_can_create_product()
    {
        [$user, $commerce] = $this->actingAsCommerce();
        $data = [
            'name' => 'Pizza Test',
            'description' => 'Pizza grande',
            'price' => 10.5,
            'available' => true
        ];
        $response = $this->postJson('/api/commerce/products', $data);
        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Pizza Test']);
    }

    public function test_commerce_can_view_own_product()
    {
        [$user, $commerce] = $this->actingAsCommerce();
        $product = Product::factory()->create(['commerce_id' => $commerce->id]);
        $response = $this->getJson("/api/commerce/products/{$product->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $product->id]);
    }
}
