<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiatePaymentRequest;
use App\Http\Requests\ManualPaymentRequest;
use App\Services\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Obtener métodos de pago disponibles para una orden
     * 
     * GET /api/buyer/payments/methods?order_id=X
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function methods(Request $request)
    {
        try {
            $orderId = $request->query('order_id');

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere order_id'
                ], 400);
            }

            $order = Order::with('commerce')->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            // Verificar que la orden pertenece al usuario
            if ($order->profile_id !== $request->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta orden'
                ], 403);
            }

            $methods = $this->paymentService->getAvailablePaymentMethods($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'methods' => $methods,
                    'order' => [
                        'id' => $order->id,
                        'total' => (float) $order->total,
                        'currency' => 'USD'
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al obtener métodos de pago: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos de pago',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Iniciar proceso de pago
     * 
     * POST /api/buyer/payments/initiate
     * Body: { order_id, payment_method, currency?, ... }
     * 
     * @param InitiatePaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(InitiatePaymentRequest $request)
    {
        try {

            $order = Order::with('commerce')->find($request->order_id);

            // Verificar que la orden pertenece al usuario
            if ($order->profile_id !== $request->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para pagar esta orden'
                ], 403);
            }

            // Iniciar pago
            $payment = $this->paymentService->initiatePayment(
                $order,
                $request->payment_method,
                $request->except(['order_id', 'payment_method'])
            );

            // Preparar respuesta según el método
            $responseData = [
                'payment' => [
                    'id' => $payment->id,
                    'method' => $payment->method,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'reference' => $payment->reference
                ]
            ];

            // Agregar datos específicos según el método
            $metadata = json_decode($payment->metadata, true) ?? [];
            
            if ($payment->method === 'stripe' && isset($metadata['client_secret'])) {
                $responseData['client_secret'] = $metadata['client_secret'];
                $responseData['payment_intent_id'] = $metadata['payment_intent_id'];
            } elseif ($payment->method === 'paypal' && isset($metadata['approval_url'])) {
                $responseData['approval_url'] = $metadata['approval_url'];
                $responseData['paypal_order_id'] = $metadata['order_id'];
            } elseif ($payment->method === 'binance' && isset($metadata['checkout_url'])) {
                $responseData['checkout_url'] = $metadata['checkout_url'];
                $responseData['binance_order_id'] = $metadata['order_id'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago iniciado exitosamente',
                'data' => $responseData
            ], 201);

        } catch (Exception $e) {
            Log::error('Error al iniciar pago: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null
            ], 400);
        }
    }

    /**
     * Registrar pago manual (Pago Móvil, Zelle)
     * 
     * POST /api/buyer/payments/manual
     * Body: { order_id, payment_method, receipt_url, reference?, bank?, phone?, account? }
     * 
     * @param ManualPaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function manual(ManualPaymentRequest $request)
    {
        try {

            $order = Order::with('commerce')->find($request->order_id);

            // Verificar que la orden pertenece al usuario
            if ($order->profile_id !== $request->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para pagar esta orden'
                ], 403);
            }

            // Iniciar pago manual
            $payment = $this->paymentService->initiatePayment(
                $order,
                $request->payment_method,
                $request->only(['receipt_url', 'reference', 'bank', 'phone', 'account'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Pago manual registrado. En espera de verificación.',
                'data' => [
                    'payment' => [
                        'id' => $payment->id,
                        'method' => $payment->method,
                        'amount' => (float) $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'reference' => $payment->reference,
                        'receipt_url' => $payment->receipt_url
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error('Error al registrar pago manual: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null
            ], 400);
        }
    }

    /**
     * Verificar estado de un pago
     * 
     * GET /api/buyer/payments/{id}/status
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request, int $id)
    {
        try {
            $payment = Payment::with('order')->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            // Verificar que el pago pertenece al usuario
            if ($payment->order->profile_id !== $request->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver este pago'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment' => [
                        'id' => $payment->id,
                        'method' => $payment->method,
                        'amount' => (float) $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'reference' => $payment->reference,
                        'external_id' => $payment->external_id,
                        'processed_at' => $payment->processed_at?->toIso8601String(),
                        'failure_reason' => $payment->failure_reason
                    ],
                    'order' => [
                        'id' => $payment->order->id,
                        'status' => $payment->order->status,
                        'total' => (float) $payment->order->total
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al verificar estado de pago: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar estado de pago',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}

