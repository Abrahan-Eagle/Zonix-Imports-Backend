<?php

namespace Database\Factories;

use App\Models\Commerce;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productImages = [
            'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=500&h=300&fit=crop',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&h=300&fit=crop',
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&h=300&fit=crop',
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&h=300&fit=crop',
            'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=500&h=300&fit=crop',
        ];

        $modality = $this->faker->randomElement(['retail', 'wholesale', 'preorder', 'referral', 'dropshipping']);
        $basePrice = $this->faker->randomFloat(2, 10, 500);
        
        return [
            'commerce_id' => Commerce::factory(),
            'category_id' => null, // Se puede asignar después si es necesario
            'name' => $this->faker->words(3, true),
            'sku' => $this->faker->optional(0.7)->regexify('[A-Z0-9]{8}'),
            'description' => $this->faker->paragraph(),
            'modality' => $modality,
            'base_price' => $basePrice,
            'stock' => $this->faker->numberBetween(0, 1000),
            'min_wholesale_quantity' => $modality === 'wholesale' ? $this->faker->numberBetween(5, 50) : null,
            'wholesale_price' => $modality === 'wholesale' ? $basePrice * 0.8 : null,
            'preorder_eta' => $modality === 'preorder' ? $this->faker->dateTimeBetween('+1 week', '+2 months') : null,
            'origin_product_id' => null, // Se puede asignar después si es necesario
            'image' => $this->faker->randomElement($productImages),
            'video_url' => $this->faker->optional(0.2)->url(),
            'weight' => $this->faker->optional(0.6)->randomFloat(2, 0.1, 50),
            'dimensions' => $this->faker->optional(0.4)->randomElements([
                'length' => $this->faker->randomFloat(2, 1, 100),
                'width' => $this->faker->randomFloat(2, 1, 100),
                'height' => $this->faker->randomFloat(2, 1, 100),
            ], 3),
            'available' => $this->faker->boolean(85),
        ];
    }

    /**
     * Indicate that the product should be created with a commerce.
     */
    public function withCommerce()
    {
        return $this->afterCreating(function (Product $product) {
            $commerce = Commerce::factory()->create();
            $product->update(['commerce_id' => $commerce->id]);
        });
    }
}
