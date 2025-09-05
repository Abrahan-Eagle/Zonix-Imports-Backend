<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\Profile;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        // Crear 30 notifications de prueba
        Notification::factory(30)->create();
        
        $this->command->info('NotificationSeeder ejecutado exitosamente.');
    }
}
