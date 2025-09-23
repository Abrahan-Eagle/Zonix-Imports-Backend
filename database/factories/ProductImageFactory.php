<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'url' => $this->faker->imageUrl(800, 600, 'products'),
            'position' => $this->faker->numberBetween(1, 10),
        ];
    }
}
