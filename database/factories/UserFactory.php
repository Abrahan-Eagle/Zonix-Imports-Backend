<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'google_id' => $this->faker->boolean(70) ? Str::uuid() : null,
            'given_name' => $this->faker->firstName(),
            'family_name' => $this->faker->lastName(),
            'profile_pic' => $this->faker->imageUrl(),
            'AccessToken' => $this->faker->boolean(50) ? Str::random(40) : null,
            'role' => $this->faker->randomElement(['admin', 'users', 'commerce', 'delivery_company', 'delivery_agent', 'delivery']),
            'completed_onboarding' => $this->faker->boolean(80),
            'remember_token' => Str::random(10),
        ];
    }


    /**
     * Indicate that the model's email address should be unverified.
     */
    // public function configure()
    // {

    // }


     // Estados para diferentes roles
    public function admin(): Factory
    {
        return $this->state([
            'role' => 'admin',
        ]);
    }

    public function buyer()
    {
        return $this->state([
            'role' => 'users',
        ]);
    }

    public function commerce(): Factory
    {
        return $this->state([
            'role' => 'commerce',
        ]);
    }

    public function deliveryCompany(): Factory
    {
        return $this->state([
            'role' => 'delivery_company',
        ]);
    }

    public function deliveryAgent(): Factory
    {
        return $this->state([
            'role' => 'delivery',
        ]);
    }

    public function delivery(): Factory
    {
        return $this->state([
            'role' => 'delivery',
        ]);
    }
}

