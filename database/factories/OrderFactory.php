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
        $subtotal = $this->faker->randomFloat(2, 20, 500);
        $discountTotal = $this->faker->randomFloat(2, 0, $subtotal * 0.2);
        $shippingTotal = $this->faker->randomFloat(2, 0, 50);
        $total = $subtotal - $discountTotal + $shippingTotal;
        
        return [
            'profile_id' => Profile::factory()->buyer(),
            'commerce_id' => Commerce::factory(),
            'modality' => $this->faker->randomElement(['retail', 'wholesale', 'preorder', 'referral', 'dropshipping']),
            'delivery_type' => $this->faker->randomElement(['pickup', 'delivery']),
            'status' => $this->faker->randomElement([
                'pending_payment', 'partially_paid', 'paid', 'preparing', 
                'on_way', 'delivered', 'cancelled'
            ]),
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'shipping_total' => $shippingTotal,
            'total' => $total,
            'shipping_address_id' => null, // Se puede asignar después si es necesario
            'billing_address_id' => null, // Se puede asignar después si es necesario
            'tracking_number' => $this->faker->optional(0.4)->regexify('[A-Z0-9]{12}'),
            'estimated_delivery' => $this->faker->optional(0.7)->dateTimeBetween('+1 day', '+1 week'),
            'receipt_url' => $this->faker->optional(0.3)->url(),
            'notes' => $this->faker->optional(0.4)->sentence(),
        ];
    }
}
