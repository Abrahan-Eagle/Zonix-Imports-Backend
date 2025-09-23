<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Profile;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'city_id' => City::factory(),
            'street' => $this->faker->streetName(),
            'house_number' => $this->faker->buildingNumber(),
            'address_line_2' => $this->faker->optional()->secondaryAddress(),
            'reference' => $this->faker->optional()->sentence(),
            'status' => 'completeData',
            'is_default' => $this->faker->boolean(20),
        ];
    }
}
