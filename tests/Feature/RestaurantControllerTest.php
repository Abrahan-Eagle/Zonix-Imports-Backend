<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Commerce;
use App\Models\Product;

class RestaurantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function actingAsBuyer()
    {
        $user = \App\Models\User::factory()->create(['role' => 'users']);
        \Laravel\Sanctum\Sanctum::actingAs($user);
        return $user;
    }

    public function test_can_list_restaurants()
    {
        $this->actingAsBuyer();
        \App\Models\Commerce::factory()->withProfile()->count(3)->create();
        $response = $this->getJson('/api/buyer/restaurants');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'profile_id', 'business_name','image', 'address', 'phone', 'open', 'schedule']
                 ]);
    }

    public function test_can_show_restaurant_details()
    {
        $this->actingAsBuyer();
        $commerce = \App\Models\Commerce::factory()->withProfile()->create();
        $response = $this->getJson("/api/buyer/restaurants/{$commerce->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $commerce->id]);
    }

    public function test_show_restaurant_not_found()
    {
        $this->actingAsBuyer();
        $response = $this->getJson('/api/buyer/restaurants/999');
        $response->assertStatus(404);
    }
}
