<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Models\Payment;
use App\Models\ProcessedWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class WebhookController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Webhook de Stripe
     * 
     * POST /api/webhooks/stripe
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stripe(Request $request)
    {
        try {
            $payload = $request->getContent();
            $signature = $request->header('Stripe-Signature');

            // TODO: Verificar firma de Stripe en producción
            // $event = \Stripe\Webhook::constructEvent($payload, $signature, env('STRIPE_WEBHOOK_SECRET'));
            
            $event = json_decode($payload, true);
            $eventId = $event['id'] ?? uniqid('stripe_');
            $eventType = $event['type'] ?? null;

            Log::info('Webhook Stripe recibido', [
                'event_id' => $eventId,
                'type' => $eventType
            ]);

            // Verificar idempotencia
            if ($this->isEventProcessed('stripe', $eventId)) {
                Log::info('Evento Stripe ya procesado', ['event_id' => $eventId]);
                return response()->json(['received' => true]);
            }

            // Procesar según el tipo de evento
            switch ($eventType) {
                case 'payment_intent.succeeded':
                    $this->handleStripePaymentSuccess($event);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handleStripePaymentFailed($event);
                    break;
                    
                case 'charge.refunded':
                    $this->handleStripeRefund($event);
                    break;
                    
                default:
                    Log::info('Evento Stripe no manejado', ['type' => $eventType]);
            }

            // Marcar evento como procesado
            $this->markEventProcessed('stripe', $eventId);

            return response()->json(['received' => true]);

        } catch (Exception $e) {
            Log::error('Error procesando webhook Stripe: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Webhook de PayPal
     * 
     * POST /api/webhooks/paypal
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paypal(Request $request)
    {
        try {
            $payload = $request->all();
            $eventId = $payload['id'] ?? uniqid('paypal_');
            $eventType = $payload['event_type'] ?? null;

            Log::info('Webhook PayPal recibido', [
                'event_id' => $eventId,
                'type' => $eventType
            ]);

            // TODO: Verificar firma de PayPal en producción
            
            // Verificar idempotencia
            if ($this->isEventProcessed('paypal', $eventId)) {
                Log::info('Evento PayPal ya procesado', ['event_id' => $eventId]);
                return response()->json(['received' => true]);
            }

            // Procesar según el tipo de evento
            switch ($eventType) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    $this->handlePayPalPaymentSuccess($payload);
                    break;
                    
                case 'PAYMENT.CAPTURE.DENIED':
                case 'PAYMENT.CAPTURE.FAILED':
                    $this->handlePayPalPaymentFailed($payload);
                    break;
                    
                case 'PAYMENT.CAPTURE.REFUNDED':
                    $this->handlePayPalRefund($payload);
                    break;
                    
                default:
                    Log::info('Evento PayPal no manejado', ['type' => $eventType]);
            }

            // Marcar evento como procesado
            $this->markEventProcessed('paypal', $eventId);

            return response()->json(['received' => true]);

        } catch (Exception $e) {
            Log::error('Error procesando webhook PayPal: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Webhook de Binance
     * 
     * POST /api/webhooks/binance
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function binance(Request $request)
    {
        try {
            $payload = $request->all();
            $eventId = $payload['bizId'] ?? uniqid('binance_');
            $status = $payload['bizStatus'] ?? null;

            Log::info('Webhook Binance recibido', [
                'event_id' => $eventId,
                'status' => $status
            ]);

            // TODO: Verificar firma de Binance en producción
            
            // Verificar idempotencia
            if ($this->isEventProcessed('binance', $eventId)) {
                Log::info('Evento Binance ya procesado', ['event_id' => $eventId]);
                return response()->json(['returnCode' => 'SUCCESS']);
            }

            // Procesar según el estado
            switch ($status) {
                case 'PAY_SUCCESS':
                    $this->handleBinancePaymentSuccess($payload);
                    break;
                    
                case 'PAY_CLOSED':
                case 'PAY_FAIL':
                    $this->handleBinancePaymentFailed($payload);
                    break;
                    
                case 'REFUND':
                    $this->handleBinanceRefund($payload);
                    break;
                    
                default:
                    Log::info('Evento Binance no manejado', ['status' => $status]);
            }

            // Marcar evento como procesado
            $this->markEventProcessed('binance', $eventId);

            return response()->json(['returnCode' => 'SUCCESS']);

        } catch (Exception $e) {
            Log::error('Error procesando webhook Binance: ' . $e->getMessage());
            return response()->json(['returnCode' => 'FAIL', 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handlers para Stripe
     */
    protected function handleStripePaymentSuccess($event)
    {
        $paymentIntentId = $event['data']['object']['id'] ?? null;
        
        if (!$paymentIntentId) {
            throw new Exception('Payment Intent ID no encontrado');
        }

        $payment = Payment::where('reference', $paymentIntentId)
            ->orWhere('external_id', $paymentIntentId)
            ->first();

        if ($payment) {
            $this->paymentService->completePayment($payment, $paymentIntentId);
            Log::info('Pago Stripe completado', ['payment_id' => $payment->id]);
        } else {
            Log::warning('Pago Stripe no encontrado', ['intent_id' => $paymentIntentId]);
        }
    }

    protected function handleStripePaymentFailed($event)
    {
        $paymentIntentId = $event['data']['object']['id'] ?? null;
        $failureMessage = $event['data']['object']['last_payment_error']['message'] ?? 'Error desconocido';

        $payment = Payment::where('reference', $paymentIntentId)
            ->orWhere('external_id', $paymentIntentId)
            ->first();

        if ($payment) {
            $this->paymentService->failPayment($payment, $failureMessage);
            Log::info('Pago Stripe fallido', ['payment_id' => $payment->id, 'reason' => $failureMessage]);
        }
    }

    protected function handleStripeRefund($event)
    {
        $chargeId = $event['data']['object']['charge'] ?? null;
        $amount = ($event['data']['object']['amount'] ?? 0) / 100; // Stripe usa centavos

        // Buscar pago por charge_id en metadata
        $payment = Payment::where('method', 'stripe')
            ->where('status', 'succeeded')
            ->whereRaw("JSON_EXTRACT(metadata, '$.charge_id') = ?", [$chargeId])
            ->first();

        if ($payment) {
            $this->paymentService->refundPayment($payment, $amount);
            Log::info('Reembolso Stripe procesado', ['payment_id' => $payment->id, 'amount' => $amount]);
        }
    }

    /**
     * Handlers para PayPal
     */
    protected function handlePayPalPaymentSuccess($payload)
    {
        $captureId = $payload['resource']['id'] ?? null;
        
        $payment = Payment::where('reference', 'LIKE', '%PAYPAL%')
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $this->paymentService->completePayment($payment, $captureId);
            Log::info('Pago PayPal completado', ['payment_id' => $payment->id]);
        }
    }

    protected function handlePayPalPaymentFailed($payload)
    {
        $reason = $payload['resource']['status_details']['reason'] ?? 'Error desconocido';
        
        $payment = Payment::where('reference', 'LIKE', '%PAYPAL%')
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $this->paymentService->failPayment($payment, $reason);
        }
    }

    protected function handlePayPalRefund($payload)
    {
        $amount = (float) ($payload['resource']['amount']['value'] ?? 0);
        
        $payment = Payment::where('method', 'paypal')
            ->where('status', 'succeeded')
            ->first();

        if ($payment) {
            $this->paymentService->refundPayment($payment, $amount);
        }
    }

    /**
     * Handlers para Binance
     */
    protected function handleBinancePaymentSuccess($payload)
    {
        $orderId = $payload['prepayId'] ?? null;
        
        $payment = Payment::where('reference', 'LIKE', '%BINANCE%')
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $this->paymentService->completePayment($payment, $orderId);
            Log::info('Pago Binance completado', ['payment_id' => $payment->id]);
        }
    }

    protected function handleBinancePaymentFailed($payload)
    {
        $reason = $payload['failReason'] ?? 'Error desconocido';
        
        $payment = Payment::where('reference', 'LIKE', '%BINANCE%')
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $this->paymentService->failPayment($payment, $reason);
        }
    }

    protected function handleBinanceRefund($payload)
    {
        $amount = (float) ($payload['refundAmount'] ?? 0);
        
        $payment = Payment::where('method', 'binance')
            ->where('status', 'succeeded')
            ->first();

        if ($payment) {
            $this->paymentService->refundPayment($payment, $amount);
        }
    }

    /**
     * Verificar si un evento ya fue procesado (idempotencia)
     */
    protected function isEventProcessed(string $provider, string $eventId): bool
    {
        return ProcessedWebhookEvent::where('provider', $provider)
            ->where('event_id', $eventId)
            ->exists();
    }

    /**
     * Marcar evento como procesado
     */
    protected function markEventProcessed(string $provider, string $eventId): void
    {
        ProcessedWebhookEvent::create([
            'provider' => $provider,
            'event_id' => $eventId
        ]);
    }
}

