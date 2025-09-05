<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Notification;
use App\Models\Profile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['order_status', 'promotion', 'delivery_update', 'payment_confirmation', 'system'];
        $type = $this->faker->randomElement($types);
        
        $titles = [
            'order_status' => [
                'Tu pedido está siendo preparado',
                'Tu pedido está en camino',
                'Tu pedido ha sido entregado',
                'Tu pedido ha sido cancelado'
            ],
            'promotion' => [
                '¡Oferta especial! 20% de descuento',
                'Nuevo restaurante disponible',
                'Cupón de descuento disponible',
                'Happy Hour activo'
            ],
            'delivery_update' => [
                'Tu repartidor está en camino',
                'Actualización de entrega',
                'Tu pedido llegará pronto',
                'Repartidor asignado'
            ],
            'payment_confirmation' => [
                'Pago confirmado',
                'Recibo disponible',
                'Factura generada',
                'Pago procesado exitosamente'
            ],
            'system' => [
                'Mantenimiento programado',
                'Nueva funcionalidad disponible',
                'Actualización de la aplicación',
                'Información importante'
            ]
        ];
        
        $bodies = [
            'order_status' => [
                'Tu pedido #{{order_id}} está siendo preparado por el restaurante. Te notificaremos cuando esté listo para entrega.',
                '¡Tu pedido #{{order_id}} está en camino! El repartidor llegará en aproximadamente {{time}} minutos.',
                'Tu pedido #{{order_id}} ha sido entregado exitosamente. ¡Disfruta tu comida!',
                'Tu pedido #{{order_id}} ha sido cancelado. El reembolso será procesado en 3-5 días hábiles.'
            ],
            'promotion' => [
                '¡Aprovecha esta oferta especial! Usa el código {{code}} para obtener 20% de descuento en tu próxima orden.',
                'Un nuevo restaurante se ha unido a Zonix Eats. ¡Explora su menú ahora!',
                'Tienes un cupón de descuento disponible. Revisa tu perfil para más detalles.',
                'Happy Hour activo en varios restaurantes. ¡Aprovecha los precios especiales!'
            ],
            'delivery_update' => [
                'Tu repartidor {{driver_name}} está en camino hacia el restaurante para recoger tu pedido.',
                'Tu pedido ha sido recogido y está siendo entregado. Tiempo estimado: {{time}} minutos.',
                'Tu pedido llegará en aproximadamente {{time}} minutos. ¡Prepárate!',
                'Se ha asignado un repartidor a tu pedido. Recibirás actualizaciones en tiempo real.'
            ],
            'payment_confirmation' => [
                'Tu pago de ${{amount}} ha sido confirmado. Gracias por tu compra.',
                'Tu recibo está disponible en tu perfil. Descárgalo cuando quieras.',
                'Se ha generado la factura para tu pedido #{{order_id}}. Está disponible en tu perfil.',
                'Tu pago ha sido procesado exitosamente. Tu pedido está siendo preparado.'
            ],
            'system' => [
                'El sistema estará en mantenimiento el {{date}} de {{time}} a {{time}}. Disculpa las molestias.',
                'Nueva funcionalidad disponible: ahora puedes programar entregas con anticipación.',
                'Se ha lanzado una nueva versión de la aplicación con mejoras de rendimiento.',
                'Información importante sobre cambios en nuestros términos de servicio.'
            ]
        ];
        
        $title = $this->faker->randomElement($titles[$type]);
        $body = $this->faker->randomElement($bodies[$type]);
        
        // Reemplazar placeholders con datos reales
        $body = str_replace('{{order_id}}', $this->faker->numberBetween(1000, 9999), $body);
        $body = str_replace('{{time}}', $this->faker->numberBetween(10, 45), $body);
        $body = str_replace('{{code}}', strtoupper($this->faker->bothify('??##')), $body);
        $body = str_replace('{{amount}}', $this->faker->randomFloat(2, 10, 100), $body);
        $body = str_replace('{{driver_name}}', $this->faker->name(), $body);
        $body = str_replace('{{date}}', $this->faker->date('d/m/Y'), $body);
        
        return [
            'profile_id' => Profile::factory(),
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'read_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'data' => [
                'order_id' => $type === 'order_status' || $type === 'payment_confirmation' ? $this->faker->numberBetween(1000, 9999) : null,
                'restaurant_id' => $this->faker->optional()->numberBetween(1, 10),
                'delivery_time' => $this->faker->optional()->numberBetween(10, 45),
                'discount_code' => $type === 'promotion' ? strtoupper($this->faker->bothify('??##')) : null,
                'amount' => $type === 'payment_confirmation' ? $this->faker->randomFloat(2, 10, 100) : null,
            ],
        ];
    }
    
    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
    
    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }
    
    /**
     * Create an order status notification.
     */
    public function orderStatus(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'order_status',
            'title' => $this->faker->randomElement([
                'Tu pedido está siendo preparado',
                'Tu pedido está en camino',
                'Tu pedido ha sido entregado',
                'Tu pedido ha sido cancelado'
            ]),
        ]);
    }
    
    /**
     * Create a promotion notification.
     */
    public function promotion(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'promotion',
            'title' => $this->faker->randomElement([
                '¡Oferta especial! 20% de descuento',
                'Nuevo restaurante disponible',
                'Cupón de descuento disponible',
                'Happy Hour activo'
            ]),
        ]);
    }
}
