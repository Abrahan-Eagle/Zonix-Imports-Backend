<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 5, 100);
        
        return [
            'profile_id' => Profile::factory()->buyer(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'modality' => $this->faker->randomElement(['retail', 'wholesale', 'preorder', 'referral', 'dropshipping']),
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
        ];
    }
}
