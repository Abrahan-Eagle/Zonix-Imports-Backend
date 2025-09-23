<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\Order;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test basic application response.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test user profile relationship.
     */
    public function test_user_has_profile_relationship(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertEquals($profile->id, $user->profile->id);
    }

    /**
     * Test profile commerce relationship.
     */
    public function test_profile_has_commerce_relationship(): void
    {
        $profile = Profile::factory()->seller()->create();
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);

        $this->assertInstanceOf(Commerce::class, $profile->commerce);
        $this->assertEquals($commerce->id, $profile->commerce->id);
    }

    /**
     * Test commerce products relationship.
     */
    public function test_commerce_has_products_relationship(): void
    {
        $commerce = Commerce::factory()->create();
        $products = Product::factory()->count(3)->create(['commerce_id' => $commerce->id]);

        $this->assertCount(3, $commerce->products);
        $this->assertTrue($commerce->products->contains($products->first()));
    }

    /**
     * Test order relationships.
     */
    public function test_order_has_relationships(): void
    {
        $profile = Profile::factory()->buyer()->create();
        $commerce = Commerce::factory()->create();
        $order = Order::factory()->create([
            'profile_id' => $profile->id,
            'commerce_id' => $commerce->id,
        ]);

        $this->assertInstanceOf(Profile::class, $order->profile);
        $this->assertInstanceOf(Commerce::class, $order->commerce);
        $this->assertEquals($profile->id, $order->profile->id);
        $this->assertEquals($commerce->id, $order->commerce->id);
    }
}
