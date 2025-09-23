<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'type' => $this->faker->randomElement(['ci', 'passport', 'rif']),
            'document_number' => $this->faker->numerify('V-#########'),
            'rif_url' => $this->faker->optional()->url(),
            'front_image' => $this->faker->imageUrl(),
            'back_image' => $this->faker->optional()->imageUrl(),
            'issued_at' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+5 years'),
            'approved' => $this->faker->boolean(70),
            'verified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
