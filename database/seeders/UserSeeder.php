<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios de prueba específicos
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@zonix.com',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Test Buyer',
                'email' => 'buyer@test.com',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Test Seller',
                'email' => 'seller@test.com',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::factory()->create($userData);
            Profile::factory()->create([
                'user_id' => $user->id,
                'firstName' => explode(' ', $userData['name'])[0],
                'lastName' => explode(' ', $userData['name'])[1] ?? 'User',
                'role' => $userData['email'] === 'admin@zonix.com' ? 'admin' : 
                         ($userData['email'] === 'seller@test.com' ? 'seller' : 'buyer'),
                'is_verified' => true,
            ]);
        }
    }
}
