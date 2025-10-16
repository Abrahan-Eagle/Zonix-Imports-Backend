<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PaymentService
{
    /**
     * Iniciar proceso de pago
     * 
     * @param Order $order
     * @param string $method (stripe|paypal|binance|pago_movil|zelle)
     * @param array $data
     * @return Payment
     * @throws Exception
     */
    public function initiatePayment(Order $order, string $method, array $data = []): Payment
    {
        try {
            DB::beginTransaction();

            // Validar que la orden esté pendiente de pago
            if (!in_array($order->status, ['pending_payment', 'partially_paid'])) {
                throw new Exception('La orden no está pendiente de pago');
            }

            // Validar método de pago
            $validMethods = ['stripe', 'paypal', 'binance', 'pago_movil', 'zelle'];
            if (!in_array($method, $validMethods)) {
                throw new Exception('Método de pago inválido');
            }

            // Crear registro de pago
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $method,
                'amount' => $order->total,
                'status' => 'pending',
                'currency' => $data['currency'] ?? 'USD',
                'metadata' => json_encode($data)
            ]);

            // Procesar según el método
            switch ($method) {
                case 'stripe':
                    $result = $this->processStripePayment($payment, $data);
                    break;
                case 'paypal':
                    $result = $this->processPayPalPayment($payment, $data);
                    break;
                case 'binance':
                    $result = $this->processBinancePayment($payment, $data);
                    break;
                case 'pago_movil':
                case 'zelle':
                    $result = $this->processManualPayment($payment, $data);
                    break;
                default:
                    throw new Exception('Método no implementado');
            }

            $payment->refresh();
            DB::commit();

            Log::info('Pago iniciado', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'method' => $method,
                'amount' => $payment->amount
            ]);

            return $payment;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al iniciar pago: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'method' => $method,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Procesar pago con Stripe
     * 
     * @param Payment $payment
     * @param array $data
     * @return array
     */
    protected function processStripePayment(Payment $payment, array $data): array
    {
        try {
            // TODO: Integración real con Stripe SDK
            // Por ahora, simulamos la creación de un Payment Intent
            
            $paymentIntentId = 'pi_test_' . Str::random(24);
            $clientSecret = 'pi_test_secret_' . Str::random(24);

            $payment->update([
                'reference' => $paymentIntentId,
                'external_id' => $paymentIntentId,
                'metadata' => json_encode(array_merge(
                    json_decode($payment->metadata, true) ?? [],
                    [
                        'client_secret' => $clientSecret,
                        'payment_intent_id' => $paymentIntentId
                    ]
                ))
            ]);

            Log::info('Stripe Payment Intent creado (simulado)', [
                'payment_id' => $payment->id,
                'intent_id' => $paymentIntentId
            ]);

            return [
                'success' => true,
                'client_secret' => $clientSecret,
                'payment_intent_id' => $paymentIntentId
            ];

        } catch (Exception $e) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Procesar pago con PayPal
     * 
     * @param Payment $payment
     * @param array $data
     * @return array
     */
    protected function processPayPalPayment(Payment $payment, array $data): array
    {
        try {
            // TODO: Integración real con PayPal SDK
            // Por ahora, simulamos la creación de una orden de PayPal
            
            $orderId = 'PAYPAL_' . Str::upper(Str::random(16));
            $approvalUrl = 'https://www.sandbox.paypal.com/checkoutnow?token=' . $orderId;

            $payment->update([
                'reference' => $orderId,
                'external_id' => $orderId,
                'metadata' => json_encode(array_merge(
                    json_decode($payment->metadata, true) ?? [],
                    [
                        'approval_url' => $approvalUrl,
                        'order_id' => $orderId
                    ]
                ))
            ]);

            Log::info('PayPal Order creada (simulado)', [
                'payment_id' => $payment->id,
                'paypal_order_id' => $orderId
            ]);

            return [
                'success' => true,
                'approval_url' => $approvalUrl,
                'order_id' => $orderId
            ];

        } catch (Exception $e) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Procesar pago con Binance
     * 
     * @param Payment $payment
     * @param array $data
     * @return array
     */
    protected function processBinancePayment(Payment $payment, array $data): array
    {
        try {
            // TODO: Integración real con Binance Pay API
            // Por ahora, simulamos la creación de una orden de Binance
            
            $orderId = 'BINANCE_' . Str::upper(Str::random(16));
            $checkoutUrl = 'https://pay.binance.com/checkout/' . $orderId;

            $payment->update([
                'reference' => $orderId,
                'external_id' => $orderId,
                'currency' => 'USD', // Currency en USD, crypto en metadata
                'metadata' => json_encode(array_merge(
                    json_decode($payment->metadata, true) ?? [],
                    [
                        'checkout_url' => $checkoutUrl,
                        'order_id' => $orderId,
                        'crypto_currency' => $data['crypto_currency'] ?? 'USDT'
                    ]
                ))
            ]);

            Log::info('Binance Order creada (simulado)', [
                'payment_id' => $payment->id,
                'binance_order_id' => $orderId
            ]);

            return [
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'order_id' => $orderId
            ];

        } catch (Exception $e) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Procesar pago manual (Pago Móvil, Zelle)
     * 
     * @param Payment $payment
     * @param array $data
     * @return array
     */
    protected function processManualPayment(Payment $payment, array $data): array
    {
        try {
            // Validar que se haya enviado el comprobante
            if (empty($data['receipt_url'])) {
                throw new Exception('El comprobante de pago es obligatorio');
            }

            $reference = $data['reference'] ?? 'REF_' . Str::upper(Str::random(8));

            $payment->update([
                'reference' => $reference,
                'receipt_url' => $data['receipt_url'],
                'status' => 'pending', // Requiere verificación manual
                'metadata' => json_encode(array_merge(
                    json_decode($payment->metadata, true) ?? [],
                    [
                        'bank' => $data['bank'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'account' => $data['account'] ?? null,
                        'reference' => $reference
                    ]
                ))
            ]);

            Log::info('Pago manual registrado', [
                'payment_id' => $payment->id,
                'method' => $payment->method,
                'reference' => $reference
            ]);

            return [
                'success' => true,
                'reference' => $reference,
                'status' => 'pending_verification'
            ];

        } catch (Exception $e) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Completar pago (llamado por webhooks o verificación manual)
     * 
     * @param Payment $payment
     * @param string $externalId
     * @return Payment
     */
    public function completePayment(Payment $payment, ?string $externalId = null): Payment
    {
        try {
            DB::beginTransaction();

            if ($externalId) {
                $payment->external_id = $externalId;
            }

            $payment->status = 'succeeded';
            $payment->processed_at = now();
            $payment->save();

            // Actualizar estado de la orden
            $order = $payment->order;
            $totalPaid = $order->payments()->where('status', 'succeeded')->sum('amount');

            if ($totalPaid >= $order->total) {
                $order->status = 'paid';
            } else {
                $order->status = 'partially_paid';
            }
            $order->save();

            DB::commit();

            Log::info('Pago completado', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'total_paid' => $totalPaid,
                'order_status' => $order->status
            ]);

            return $payment;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al completar pago: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Marcar pago como fallido
     * 
     * @param Payment $payment
     * @param string $reason
     * @return Payment
     */
    public function failPayment(Payment $payment, string $reason): Payment
    {
        $payment->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processed_at' => now()
        ]);

        Log::warning('Pago fallido', [
            'payment_id' => $payment->id,
            'reason' => $reason
        ]);

        return $payment;
    }

    /**
     * Reembolsar pago
     * 
     * @param Payment $payment
     * @param float|null $amount
     * @return Payment
     */
    public function refundPayment(Payment $payment, ?float $amount = null): Payment
    {
        try {
            DB::beginTransaction();

            if ($payment->status !== 'succeeded') {
                throw new Exception('Solo se pueden reembolsar pagos exitosos');
            }

            $refundAmount = $amount ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                throw new Exception('El monto a reembolsar no puede ser mayor al pago');
            }

            // TODO: Implementar reembolso real según el método
            // Por ahora solo actualizamos el estado

            $currentMetadata = $payment->metadata ?? [];

            $payment->update([
                'status' => 'refunded',
                'metadata' => array_merge(
                    $currentMetadata,
                    [
                        'refunded_at' => now()->toIso8601String(),
                        'refund_amount' => $refundAmount
                    ]
                )
            ]);

            // Actualizar estado de la orden si es necesario
            $order = $payment->order;
            $totalPaid = $order->payments()
                ->whereIn('status', ['succeeded', 'partially_paid'])
                ->sum('amount');

            if ($totalPaid < $order->total) {
                $order->status = $totalPaid > 0 ? 'partially_paid' : 'pending_payment';
                $order->save();
            }

            DB::commit();

            Log::info('Pago reembolsado', [
                'payment_id' => $payment->id,
                'refund_amount' => $refundAmount
            ]);

            return $payment;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al reembolsar pago: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener métodos de pago disponibles para una orden
     * 
     * @param Order $order
     * @return array
     */
    public function getAvailablePaymentMethods(Order $order): array
    {
        // Obtener métodos habilitados por el comercio
        $commerce = $order->commerce;
        $commerceMethods = is_array($commerce->payment_methods) 
            ? $commerce->payment_methods 
            : json_decode($commerce->payment_methods ?? '[]', true);

        $allMethods = [
            'stripe' => [
                'id' => 'stripe',
                'name' => 'Tarjeta de Crédito/Débito',
                'type' => 'card',
                'icon' => 'credit_card',
                'enabled' => in_array('stripe', $commerceMethods),
                'currencies' => ['USD', 'EUR']
            ],
            'paypal' => [
                'id' => 'paypal',
                'name' => 'PayPal',
                'type' => 'digital_wallet',
                'icon' => 'paypal',
                'enabled' => in_array('paypal', $commerceMethods),
                'currencies' => ['USD', 'EUR']
            ],
            'binance' => [
                'id' => 'binance',
                'name' => 'Binance Pay',
                'type' => 'crypto',
                'icon' => 'crypto',
                'enabled' => in_array('binance', $commerceMethods),
                'currencies' => ['USDT', 'BTC', 'BNB']
            ],
            'pago_movil' => [
                'id' => 'pago_movil',
                'name' => 'Pago Móvil',
                'type' => 'bank_transfer',
                'icon' => 'mobile_payment',
                'enabled' => in_array('pago_movil', $commerceMethods),
                'currencies' => ['VES'],
                'requires_receipt' => true
            ],
            'zelle' => [
                'id' => 'zelle',
                'name' => 'Zelle',
                'type' => 'bank_transfer',
                'icon' => 'bank',
                'enabled' => in_array('zelle', $commerceMethods),
                'currencies' => ['USD'],
                'requires_receipt' => true
            ]
        ];

        // Filtrar solo los habilitados
        return array_values(array_filter($allMethods, function($method) {
            return $method['enabled'];
        }));
    }
}

