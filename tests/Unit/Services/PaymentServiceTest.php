<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();

        // Crear orden de prueba
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $commerce = Commerce::factory()->create([
            'payment_methods' => json_encode(['stripe', 'paypal', 'binance', 'pago_movil', 'zelle'])
        ]);
        
        $this->order = Order::factory()->create([
            'profile_id' => $profile->id,
            'commerce_id' => $commerce->id,
            'total' => 100.00,
            'status' => 'pending_payment'
        ]);
    }

    /** @test */
    public function puede_iniciar_pago_con_stripe()
    {
        $payment = $this->paymentService->initiatePayment($this->order, 'stripe');

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($this->order->id, $payment->order_id);
        $this->assertEquals('stripe', $payment->method);
        $this->assertEquals(100.00, $payment->amount);
        $this->assertEquals('pending', $payment->status);
        $this->assertNotNull($payment->reference);
    }

    /** @test */
    public function puede_iniciar_pago_con_paypal()
    {
        $payment = $this->paymentService->initiatePayment($this->order, 'paypal');

        $this->assertEquals('paypal', $payment->method);
        $this->assertStringContainsString('PAYPAL_', $payment->reference);
    }

    /** @test */
    public function puede_iniciar_pago_con_binance()
    {
        $payment = $this->paymentService->initiatePayment($this->order, 'binance', [
            'crypto_currency' => 'USDT'
        ]);

        $this->assertEquals('binance', $payment->method);
        $this->assertEquals('USD', $payment->currency); // Currency base en USD
        $this->assertStringContainsString('BINANCE_', $payment->reference);
        
        // Verificar crypto_currency en metadata
        $metadata = is_array($payment->metadata) ? $payment->metadata : json_decode($payment->metadata, true);
        $this->assertEquals('USDT', $metadata['crypto_currency']);
    }

    /** @test */
    public function puede_iniciar_pago_manual_pago_movil()
    {
        $payment = $this->paymentService->initiatePayment($this->order, 'pago_movil', [
            'receipt_url' => 'https://example.com/receipt.jpg',
            'reference' => '123456',
            'bank' => 'Banco Test',
            'phone' => '04241234567'
        ]);

        $this->assertEquals('pago_movil', $payment->method);
        $this->assertEquals('https://example.com/receipt.jpg', $payment->receipt_url);
        $this->assertEquals('pending', $payment->status);
    }

    /** @test */
    public function puede_iniciar_pago_manual_zelle()
    {
        $payment = $this->paymentService->initiatePayment($this->order, 'zelle', [
            'receipt_url' => 'https://example.com/zelle.jpg',
            'reference' => 'ZELLE123'
        ]);

        $this->assertEquals('zelle', $payment->method);
        $this->assertEquals('pending', $payment->status);
    }

    /** @test */
    public function lanza_excepcion_si_orden_no_esta_pendiente()
    {
        $this->order->update(['status' => 'paid']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La orden no está pendiente de pago');

        $this->paymentService->initiatePayment($this->order, 'stripe');
    }

    /** @test */
    public function lanza_excepcion_si_metodo_invalido()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Método de pago inválido');

        $this->paymentService->initiatePayment($this->order, 'invalid_method');
    }

    /** @test */
    public function lanza_excepcion_si_pago_manual_sin_comprobante()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('El comprobante de pago es obligatorio');

        $this->paymentService->initiatePayment($this->order, 'pago_movil', []);
    }

    /** @test */
    public function puede_completar_pago()
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending',
            'amount' => 100.00
        ]);

        $completed = $this->paymentService->completePayment($payment, 'EXTERNAL_123');

        $this->assertEquals('succeeded', $completed->status);
        $this->assertEquals('EXTERNAL_123', $completed->external_id);
        $this->assertNotNull($completed->processed_at);

        // Verificar que la orden se actualizó
        $this->order->refresh();
        $this->assertEquals('paid', $this->order->status);
    }

    /** @test */
    public function puede_marcar_pago_como_fallido()
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending'
        ]);

        $failed = $this->paymentService->failPayment($payment, 'Insufficient funds');

        $this->assertEquals('failed', $failed->status);
        $this->assertEquals('Insufficient funds', $failed->failure_reason);
        $this->assertNotNull($failed->processed_at);
    }

    /** @test */
    public function puede_reembolsar_pago()
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'succeeded',
            'amount' => 100.00
        ]);

        $refunded = $this->paymentService->refundPayment($payment);

        $this->assertEquals('refunded', $refunded->status);
        
        $metadata = $refunded->metadata; // Ya es un array gracias al cast
        $this->assertArrayHasKey('refund_amount', $metadata);
        $this->assertEquals(100.00, $metadata['refund_amount']);
    }

    /** @test */
    public function lanza_excepcion_al_reembolsar_pago_no_exitoso()
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Solo se pueden reembolsar pagos exitosos');

        $this->paymentService->refundPayment($payment);
    }

    /** @test */
    public function puede_obtener_metodos_disponibles()
    {
        $methods = $this->paymentService->getAvailablePaymentMethods($this->order);

        $this->assertIsArray($methods);
        $this->assertCount(5, $methods); // stripe, paypal, binance, pago_movil, zelle

        // Verificar estructura de cada método
        foreach ($methods as $method) {
            $this->assertArrayHasKey('id', $method);
            $this->assertArrayHasKey('name', $method);
            $this->assertArrayHasKey('type', $method);
            $this->assertArrayHasKey('enabled', $method);
            $this->assertTrue($method['enabled']);
        }
    }

    /** @test */
    public function solo_muestra_metodos_habilitados_por_comercio()
    {
        // Actualizar comercio para tener solo stripe
        $this->order->commerce->update([
            'payment_methods' => json_encode(['stripe'])
        ]);

        $methods = $this->paymentService->getAvailablePaymentMethods($this->order);

        $this->assertCount(1, $methods);
        $this->assertEquals('stripe', $methods[0]['id']);
    }

    /** @test */
    public function actualiza_orden_a_partially_paid_si_pago_parcial()
    {
        $this->order->update(['total' => 200.00]);

        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending',
            'amount' => 100.00
        ]);

        $this->paymentService->completePayment($payment);

        $this->order->refresh();
        $this->assertEquals('partially_paid', $this->order->status);
    }
}

