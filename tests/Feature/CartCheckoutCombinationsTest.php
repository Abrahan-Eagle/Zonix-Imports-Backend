<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Category;
use App\Models\Product;

class CartCheckoutCombinationsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsBuyer(): void
    {
        $user = User::factory()->create(['email' => 'buyer@test.com']);
        Profile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);
    }

    private function setupSeller(): array
    {
        $seller = User::factory()->create(['email' => 'seller@test.com']);
        $profile = Profile::factory()->create(['user_id' => $seller->id, 'is_verified' => true]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id, 'is_verified' => true, 'open' => true]);
        $category = Category::factory()->create();
        return [$commerce, $category];
    }

    /** @test */
    public function checkout_funciona_para_retail_wholesale_preorder_y_referral()
    {
        [$commerce, $category] = $this->setupSeller();
        $this->actingAsBuyer();

        // Retail
        $retail = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'category_id' => $category->id,
            'modality' => 'retail',
            'base_price' => 10,
            'stock' => 100,
            'available' => true,
        ]);
        $this->postJson('/api/buyer/cart/add', ['product_id' => $retail->id, 'quantity' => 1])->assertStatus(200);
        $this->postJson('/api/checkout', [])->assertStatus(201);

        // Wholesale (cantidad >= mínimo)
        $wholesale = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'category_id' => $category->id,
            'modality' => 'wholesale',
            'base_price' => 8,
            'stock' => 100,
            'min_wholesale_quantity' => 5,
            'wholesale_price' => 7.5,
            'available' => true,
        ]);
        $this->postJson('/api/buyer/cart/add', ['product_id' => $wholesale->id, 'quantity' => 6])->assertStatus(200);
        $this->postJson('/api/checkout', [])->assertStatus(201);

        // Preorder
        $preorder = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'category_id' => $category->id,
            'modality' => 'preorder',
            'base_price' => 12,
            'stock' => 100,
            'preorder_eta' => now()->addWeeks(2)->toDateString(),
            'available' => true,
        ]);
        $this->postJson('/api/buyer/cart/add', ['product_id' => $preorder->id, 'quantity' => 2])->assertStatus(200);
        $this->postJson('/api/checkout', [])->assertStatus(201);

        // Referral (misma lógica de checkout en MVP)
        $referral = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'category_id' => $category->id,
            'modality' => 'referral',
            'base_price' => 9,
            'stock' => 100,
            'available' => true,
        ]);
        $this->postJson('/api/buyer/cart/add', ['product_id' => $referral->id, 'quantity' => 3])->assertStatus(200);
        $this->postJson('/api/checkout', [])->assertStatus(201);
    }
}


