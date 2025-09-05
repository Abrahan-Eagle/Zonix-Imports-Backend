<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Profile;
use App\Models\Order;
use App\Models\Commerce;
use App\Models\DeliveryAgent;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // Crear 20 reviews de prueba
        Review::factory(20)->create();
        
        $this->command->info('ReviewSeeder ejecutado exitosamente.');
    }
}
