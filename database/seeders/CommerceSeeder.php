<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Commerce;
use App\Models\Profile;

class CommerceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea 3 comercios para los primeros 3 perfiles
        $profiles = \App\Models\Profile::take(3)->get();
        foreach ($profiles as $profile) {
            $profile->user->update(['role' => 'commerce']);
            Commerce::factory()->create(['profile_id' => $profile->id]);
        }
        // No poblar campos de métodos de pago eliminados
    }
}
