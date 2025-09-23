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
        $profiles = Profile::all();
        
        if ($profiles->isEmpty()) {
            $this->command->warn('No hay perfiles disponibles. Creando notificaciones de prueba...');
            Notification::factory(20)->create();
            return;
        }

        $notificationTypes = [
            'order_created' => 'Nueva orden creada',
            'order_confirmed' => 'Orden confirmada',
            'order_shipped' => 'Orden enviada',
            'order_delivered' => 'Orden entregada',
            'payment_received' => 'Pago recibido',
            'payment_failed' => 'Pago fallido',
            'product_approved' => 'Producto aprobado',
            'product_rejected' => 'Producto rechazado',
            'referral_earned' => 'Comisión ganada por referido',
            'inventory_low' => 'Stock bajo en producto',
            'account_verified' => 'Cuenta verificada',
            'welcome' => 'Bienvenido a Zonix Imports',
        ];

        $priorities = ['low', 'medium', 'high'];

        foreach ($profiles as $profile) {
            $numNotifications = fake()->numberBetween(2, 8);
            
            for ($i = 0; $i < $numNotifications; $i++) {
                $type = fake()->randomElement(array_keys($notificationTypes));
                $priority = fake()->randomElement($priorities);
                
                Notification::create([
                    'profile_id' => $profile->id,
                    'title' => $notificationTypes[$type],
                    'body' => $this->generateNotificationBody($type),
                    'type' => $type,
                    'is_read' => fake()->boolean(60),
                    'priority' => $priority,
                    'data' => $this->generateNotificationData($type),
                ]);
            }
        }
        
        $this->command->info('NotificationSeeder ejecutado exitosamente.');
    }

    private function generateNotificationBody($type): string
    {
        $bodies = [
            'order_created' => 'Se ha creado una nueva orden #' . fake()->numberBetween(1000, 9999) . ' por un total de $' . fake()->numberBetween(10, 500) . '.',
            'order_confirmed' => 'Tu orden #' . fake()->numberBetween(1000, 9999) . ' ha sido confirmada y está siendo procesada.',
            'order_shipped' => 'Tu orden #' . fake()->numberBetween(1000, 9999) . ' ha sido enviada. Número de seguimiento: ' . fake()->regexify('[A-Z0-9]{12}'),
            'order_delivered' => 'Tu orden #' . fake()->numberBetween(1000, 9999) . ' ha sido entregada exitosamente.',
            'payment_received' => 'Hemos recibido tu pago de $' . fake()->numberBetween(10, 500) . ' por la orden #' . fake()->numberBetween(1000, 9999) . '.',
            'payment_failed' => 'Tu pago por $' . fake()->numberBetween(10, 500) . ' no pudo ser procesado. Por favor, intenta nuevamente.',
            'product_approved' => 'Tu producto "' . fake()->words(3, true) . '" ha sido aprobado y está disponible para la venta.',
            'product_rejected' => 'Tu producto "' . fake()->words(3, true) . '" fue rechazado. Revisa los comentarios del administrador.',
            'referral_earned' => 'Has ganado $' . fake()->numberBetween(1, 50) . ' en comisiones por referidos.',
            'inventory_low' => 'El stock del producto "' . fake()->words(3, true) . '" está bajo. Considera reponer inventario.',
            'account_verified' => 'Tu cuenta ha sido verificada exitosamente. Ahora puedes acceder a todas las funciones.',
            'welcome' => '¡Bienvenido a Zonix Imports! Explora nuestros productos y comienza a comprar o vender.',
        ];

        return $bodies[$type] ?? 'Notificación del sistema.';
    }

    private function generateNotificationData($type): array
    {
        $data = ['type' => $type];
        
        switch ($type) {
            case 'order_created':
            case 'order_confirmed':
            case 'order_shipped':
            case 'order_delivered':
                $data['order_id'] = fake()->numberBetween(1, 100);
                break;
            case 'payment_received':
            case 'payment_failed':
                $data['payment_id'] = fake()->numberBetween(1, 100);
                $data['amount'] = fake()->numberBetween(10, 500);
                break;
            case 'product_approved':
            case 'product_rejected':
            case 'inventory_low':
                $data['product_id'] = fake()->numberBetween(1, 100);
                break;
            case 'referral_earned':
                $data['referral_id'] = fake()->numberBetween(1, 50);
                $data['commission'] = fake()->numberBetween(1, 50);
                break;
        }
        
        return $data;
    }
}
