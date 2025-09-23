<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Commerce;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commerce>
 */
class CommerceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessImages = [
            'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=500&h=300&fit=crop',
            'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=500&h=300&fit=crop',
            'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=500&h=300&fit=crop',
            'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=500&h=300&fit=crop',
            'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=500&h=300&fit=crop',
        ];

        return [
            'profile_id' => Profile::factory()->seller(),
            'business_name' => $this->faker->company,
            'business_type' => $this->faker->randomElement(['retail', 'wholesale', 'service', 'manufacturing']),
            'image' => $this->faker->randomElement($businessImages),
            'phone' => $this->faker->phoneNumber,
            'rif' => $this->faker->unique()->regexify('V[0-9]{8}'),
            'bank_account' => $this->faker->bankAccountNumber,
            'is_verified' => $this->faker->boolean(70),
            'open' => $this->faker->boolean(80),
            'payment_methods' => $this->faker->randomElements(['stripe', 'paypal', 'binance', 'pago_movil', 'zelle'], $this->faker->numberBetween(1, 3)),
            'schedule' => [
                'monday' => ['open' => '08:00', 'close' => '18:00'],
                'tuesday' => ['open' => '08:00', 'close' => '18:00'],
                'wednesday' => ['open' => '08:00', 'close' => '18:00'],
                'thursday' => ['open' => '08:00', 'close' => '18:00'],
                'friday' => ['open' => '08:00', 'close' => '18:00'],
                'saturday' => ['open' => '09:00', 'close' => '16:00'],
                'sunday' => ['open' => '10:00', 'close' => '15:00'],
            ],
        ];
    }

    /**
     * Indicate that the commerce should be created with a profile.
     */
    public function withProfile()
    {
        return $this->afterCreating(function (Commerce $commerce) {
            $profile = Profile::factory()->create();
            $commerce->update(['profile_id' => $profile->id]);
        });
    }
}
