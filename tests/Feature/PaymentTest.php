<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Commerce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $profile;
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->profile = Profile::factory()->create(['user_id' => $this->user->id]);
        
        $commerce = Commerce::factory()->create([
            'payment_methods' => json_encode(['stripe', 'paypal', 'binance', 'pago_movil', 'zelle'])
        ]);
        
        $this->order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $commerce->id,
            'total' => 100.00,
            'status' => 'pending_payment'
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function puede_obtener_metodos_de_pago_disponibles()
    {
        $response = $this->getJson("/api/buyer/payments/methods?order_id={$this->order->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'data' => [
                        'methods' => [
                            '*' => ['id', 'name', 'type', 'icon', 'enabled', 'currencies']
                        ],
                        'order' => ['id', 'total', 'currency']
                    ]
                ]);

        $this->assertCount(5, $response->json('data.methods'));
    }

    /** @test */
    public function falla_al_obtener_metodos_sin_order_id()
    {
        $response = $this->getJson('/api/buyer/payments/methods');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Se requiere order_id'
                ]);
    }

    /** @test */
    public function falla_al_obtener_metodos_de_orden_inexistente()
    {
        $response = $this->getJson('/api/buyer/payments/methods?order_id=999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ]);
    }

    /** @test */
    public function puede_iniciar_pago_con_stripe()
    {
        $response = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $this->order->id,
            'payment_method' => 'stripe'
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Pago iniciado exitosamente'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'payment' => ['id', 'method', 'amount', 'status'],
                        'client_secret',
                        'payment_intent_id'
                    ]
                ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'method' => 'stripe',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function puede_iniciar_pago_con_paypal()
    {
        $response = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $this->order->id,
            'payment_method' => 'paypal'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'payment',
                        'approval_url',
                        'paypal_order_id'
                    ]
                ]);
    }

    /** @test */
    public function puede_iniciar_pago_con_binance()
    {
        $response = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $this->order->id,
            'payment_method' => 'binance',
            'crypto_currency' => 'USDT'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'payment',
                        'checkout_url',
                        'binance_order_id'
                    ]
                ]);
    }

    /** @test */
    public function falla_al_iniciar_pago_sin_order_id()
    {
        $response = $this->postJson('/api/buyer/payments/initiate', [
            'payment_method' => 'stripe'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['order_id']);
    }

    /** @test */
    public function falla_al_iniciar_pago_con_metodo_invalido()
    {
        $response = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $this->order->id,
            'payment_method' => 'invalid'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function falla_al_iniciar_pago_de_orden_ya_pagada()
    {
        $this->order->update(['status' => 'paid']);

        $response = $this->postJson('/api/buyer/payments/initiate', [
            'order_id' => $this->order->id,
            'payment_method' => 'stripe'
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function puede_registrar_pago_manual_pago_movil()
    {
        $response = $this->postJson('/api/buyer/payments/manual', [
            'order_id' => $this->order->id,
            'payment_method' => 'pago_movil',
            'receipt_url' => 'https://example.com/receipt.jpg',
            'reference' => '123456',
            'bank' => 'Banco Test',
            'phone' => '04241234567'
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Pago manual registrado. En espera de verificación.'
                ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'method' => 'pago_movil',
            'receipt_url' => 'https://example.com/receipt.jpg',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function puede_registrar_pago_manual_zelle()
    {
        $response = $this->postJson('/api/buyer/payments/manual', [
            'order_id' => $this->order->id,
            'payment_method' => 'zelle',
            'receipt_url' => 'https://example.com/zelle.jpg',
            'reference' => 'ZELLE123'
        ]);

        $response->assertStatus(201);
        $this->assertEquals('zelle', $response->json('data.payment.method'));
    }

    /** @test */
    public function falla_al_registrar_pago_manual_sin_comprobante()
    {
        $response = $this->postJson('/api/buyer/payments/manual', [
            'order_id' => $this->order->id,
            'payment_method' => 'pago_movil'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['receipt_url']);
    }

    /** @test */
    public function falla_al_registrar_pago_manual_con_metodo_invalido()
    {
        $response = $this->postJson('/api/buyer/payments/manual', [
            'order_id' => $this->order->id,
            'payment_method' => 'stripe', // No es manual
            'receipt_url' => 'https://example.com/receipt.jpg'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function puede_verificar_estado_de_pago()
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'succeeded'
        ]);

        $response = $this->getJson("/api/buyer/payments/{$payment->id}/status");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'data' => [
                        'payment' => ['id', 'method', 'amount', 'status'],
                        'order' => ['id', 'status', 'total']
                    ]
                ]);
    }

    /** @test */
    public function falla_al_verificar_pago_inexistente()
    {
        $response = $this->getJson('/api/buyer/payments/999/status');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ]);
    }

    /** @test */
    public function falla_al_acceder_pago_de_otro_usuario()
    {
        $otherUser = User::factory()->create();
        $otherProfile = Profile::factory()->create(['user_id' => $otherUser->id]);
        $otherCommerce = Commerce::factory()->create();
        $otherOrder = Order::factory()->create([
            'profile_id' => $otherProfile->id,
            'commerce_id' => $otherCommerce->id,
            'total' => 50.00
        ]);

        $payment = Payment::factory()->create(['order_id' => $otherOrder->id]);

        $response = $this->getJson("/api/buyer/payments/{$payment->id}/status");

        $response->assertStatus(403);
    }
}

