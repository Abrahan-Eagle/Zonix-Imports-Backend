<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralFactory extends Factory
{
    public function definition(): array
    {
        $percentage = $this->faker->randomFloat(2, 1, 20);
        $commissionEarned = $this->faker->randomFloat(2, 0, 100);
        
        return [
            'product_id' => Product::factory(),
            'referrer_profile_id' => Profile::factory()->seller(),
            'percentage' => $percentage,
            'commission_earned' => $commissionEarned,
            'link' => 'ref_' . $this->faker->unique()->regexify('[A-Z0-9]{16}'),
            'active' => $this->faker->boolean(80),
            'expires_at' => $this->faker->optional(0.6)->dateTimeBetween('+1 month', '+1 year'),
        ];
    }
}
