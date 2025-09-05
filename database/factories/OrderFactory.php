<?php

namespace Database\Factories;

use App\Models\Commerce;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'commerce_id' => Commerce::factory(),
            'delivery_type' => $this->faker->randomElement(['pickup', 'delivery']),
            'status' => $this->faker->randomElement(['pending_payment', 'paid', 'preparing', 'on_way', 'delivered', 'cancelled']),
            'total' => $this->faker->randomFloat(2, 10, 100),
            'receipt_url' => $this->faker->optional()->url(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
