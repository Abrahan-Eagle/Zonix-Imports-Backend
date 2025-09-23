<?php

namespace Database\Factories;

use App\Models\Phone;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Phone>
 */
class PhoneFactory extends Factory
{
    protected $model = Phone::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'operator_code' => $this->faker->randomElement(['0412', '0414', '0424', '0416', '0426']),
            'country_code' => '+58',
            'number' => $this->faker->numerify('####-##'),
            'is_primary' => $this->faker->boolean(20),
            'is_active' => true,
        ];
    }
}
