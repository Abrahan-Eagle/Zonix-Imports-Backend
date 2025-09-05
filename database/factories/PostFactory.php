<?php

namespace Database\Factories;

use App\Models\Commerce;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commerce_id' => Commerce::factory(),
            'tipo' => $this->faker->randomElement(['promo', 'news', 'product']),
            'media_url' => $this->faker->imageUrl(),
            'description' => $this->faker->sentence,
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 1, 100),
        ];
    }
}
