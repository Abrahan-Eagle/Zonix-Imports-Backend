<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryMovementFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['in', 'out', 'adjustment']);
        $quantity = $this->faker->numberBetween(1, 100);
        
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'type' => $type,
            'quantity' => $type === 'out' ? -$quantity : $quantity,
            'reason' => $this->faker->randomElement([
                'purchase', 'sale', 'return', 'adjustment', 'transfer', 'damage', 'expired'
            ]),
            'reference' => $this->faker->optional(0.7)->regexify('[A-Z0-9]{8}'),
        ];
    }
}
