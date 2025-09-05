<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Obtener métodos de pago disponibles
     */
    public function getPaymentMethods(): JsonResponse
    {
        $paymentMethods = [
            [
                'id' => 'credit_card',
                'name' => 'Tarjeta de Crédito/Débito',
                'icon' => 'credit_card',
                'description' => 'Visa, MasterCard, American Express',
                'enabled' => true,
                'supported_cards' => ['visa', 'mastercard', 'amex', 'discover']
            ],
            [
                'id' => 'cash',
                'name' => 'Efectivo',
                'icon' => 'money',
                'description' => 'Pago al momento de la entrega',
                'enabled' => true,
                'supported_cards' => []
            ],
            [
                'id' => 'mobile_payment',
                'name' => 'Pago Móvil',
                'icon' => 'smartphone',
                'description' => 'Pago a través de banca móvil',
                'enabled' => true,
                'supported_banks' => ['banesco', 'banco_de_venezuela', 'bbva', 'provincial', 'mercantil']
            ],
            [
                'id' => 'paypal',
                'name' => 'PayPal',
                'icon' => 'paypal',
                'description' => 'Pago seguro con PayPal',
                'enabled' => true,
                'supported_cards' => []
            ],
            [
                'id' => 'stripe',
                'name' => 'Stripe',
                'icon' => 'stripe',
                'description' => 'Pago con tarjeta vía Stripe',
                'enabled' => true,
                'supported_cards' => ['visa', 'mastercard', 'amex']
            ],
            [
                'id' => 'mercadopago',
                'name' => 'MercadoPago',
                'icon' => 'mercadopago',
                'description' => 'Pago con MercadoPago',
                'enabled' => true,
                'supported_cards' => ['visa', 'mastercard', 'amex']
            ],
            [
                'id' => 'digital_wallet',
                'name' => 'Billetera Digital',
                'icon' => 'account_balance_wallet',
                'description' => 'Apple Pay, Google Pay, Samsung Pay',
                'enabled' => true,
                'supported_cards' => []
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $paymentMethods
        ]);
    }

    /**
     * Procesar pago con tarjeta
     */
    public function processCardPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'card_number' => 'required|string|min:13|max:19',
            'card_holder' => 'required|string|max:100',
            'expiry_month' => 'required|integer|between:1,12',
            'expiry_year' => 'required|integer|min:' . date('Y'),
            'cvv' => 'required|string|min:3|max:4',
            'amount' => 'required|numeric|min:0.01',
            'payment_gateway' => 'sometimes|string|in:stripe,mercadopago,paypal'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de tarjeta inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);
            $paymentGateway = $request->get('payment_gateway', 'stripe');
            
            // Procesar pago según la pasarela seleccionada
            $paymentResult = $this->processPaymentWithGateway($request->all(), $paymentGateway);
            
            if ($paymentResult['success']) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'credit_card',
                    'payment_gateway' => $paymentGateway,
                    'payment_proof' => $paymentResult['transaction_id'],
                    'paid_at' => now(),
                    'card_last_four' => substr($request->card_number, -4)
                ]);

                // Generar factura electrónica
                $invoice = $this->generateElectronicInvoice($order);

                return response()->json([
                    'success' => true,
                    'message' => 'Pago procesado exitosamente',
                    'data' => [
                        'transaction_id' => $paymentResult['transaction_id'],
                        'amount' => $request->amount,
                        'order_id' => $order->id,
                        'invoice_url' => $invoice['url'] ?? null,
                        'invoice_number' => $invoice['number'] ?? null
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar el pago: ' . $paymentResult['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing card payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Procesar pago con PayPal
     */
    public function processPayPalPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'paypal_order_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de PayPal inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);
            
            // Simular captura de pago de PayPal
            $paymentResult = $this->capturePayPalPayment($request->paypal_order_id);
            
            if ($paymentResult['success']) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'paypal',
                    'payment_gateway' => 'paypal',
                    'payment_proof' => $paymentResult['transaction_id'],
                    'paid_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pago con PayPal procesado exitosamente',
                    'data' => [
                        'transaction_id' => $paymentResult['transaction_id'],
                        'amount' => $request->amount,
                        'order_id' => $order->id
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar pago con PayPal: ' . $paymentResult['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing PayPal payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Procesar pago con MercadoPago
     */
    public function processMercadoPagoPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'preference_id' => 'required|string',
            'payment_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de MercadoPago inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);
            
            // Simular verificación de pago de MercadoPago
            $paymentResult = $this->verifyMercadoPagoPayment($request->payment_id);
            
            if ($paymentResult['success']) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'mercadopago',
                    'payment_gateway' => 'mercadopago',
                    'payment_proof' => $paymentResult['transaction_id'],
                    'paid_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pago con MercadoPago procesado exitosamente',
                    'data' => [
                        'transaction_id' => $paymentResult['transaction_id'],
                        'amount' => $request->amount,
                        'order_id' => $order->id
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar pago con MercadoPago: ' . $paymentResult['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing MercadoPago payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Confirmar pago en efectivo
     */
    public function confirmCashPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);
            
            $order->update([
                'payment_status' => 'pending_cash',
                'payment_method' => 'cash',
                'total_amount' => $request->amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago en efectivo confirmado',
                'data' => [
                    'order_id' => $order->id,
                    'amount' => $request->amount,
                    'payment_status' => 'pending_cash'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error confirming cash payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Procesar pago móvil
     */
    public function processMobilePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'bank' => 'required|string|in:banesco,banco_de_venezuela,bbva,provincial,mercantil',
            'phone_number' => 'required|string|min:10|max:15',
            'reference_number' => 'required|string|min:6|max:20',
            'amount' => 'required|numeric|min:0.01',
            'cedula' => 'required|string|min:7|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de pago móvil inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);
            
            // Simular verificación de pago móvil
            $paymentResult = $this->verifyMobilePayment($request->all());
            
            if ($paymentResult['success']) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'mobile_payment',
                    'payment_gateway' => $request->bank,
                    'payment_proof' => $paymentResult['transaction_id'],
                    'paid_at' => now(),
                    'mobile_payment_data' => json_encode([
                        'bank' => $request->bank,
                        'phone_number' => $request->phone_number,
                        'reference_number' => $request->reference_number,
                        'cedula' => $request->cedula
                    ])
                ]);

                // Generar factura electrónica
                $invoice = $this->generateElectronicInvoice($order);

                return response()->json([
                    'success' => true,
                    'message' => 'Pago móvil procesado exitosamente',
                    'data' => [
                        'transaction_id' => $paymentResult['transaction_id'],
                        'amount' => $request->amount,
                        'order_id' => $order->id,
                        'bank' => $request->bank,
                        'reference_number' => $request->reference_number,
                        'invoice_url' => $invoice['url'] ?? null,
                        'invoice_number' => $invoice['number'] ?? null
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar pago móvil: ' . $paymentResult['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing mobile payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Solicitar reembolso
     */
    public function requestRefund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|string|max:500',
            'amount' => 'sometimes|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);
            
            // Verificar que el pedido esté pagado
            if ($order->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido no está pagado'
                ], 400);
            }

            // Verificar que no haya pasado mucho tiempo
            $hoursSincePayment = now()->diffInHours($order->paid_at);
            if ($hoursSincePayment > 24) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden solicitar reembolsos dentro de las 24 horas posteriores al pago'
                ], 400);
            }

            // Procesar reembolso automático
            $refundResult = $this->processAutomaticRefund($order, $request->reason, $request->amount);
            
            if ($refundResult['success']) {
                $order->update([
                    'payment_status' => 'refunded',
                    'refund_reason' => $request->reason,
                    'refunded_at' => now(),
                    'refund_amount' => $refundResult['amount']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reembolso procesado exitosamente',
                    'data' => [
                        'refund_id' => $refundResult['refund_id'],
                        'amount' => $refundResult['amount'],
                        'estimated_processing_time' => '3-5 días hábiles'
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar reembolso: ' . $refundResult['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing refund: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener comprobante de pago
     */
    public function getPaymentReceipt($orderId): JsonResponse
    {
        try {
            $order = Order::with(['profile', 'commerce', 'items.product'])
                ->findOrFail($orderId);

            $receipt = [
                'receipt_number' => 'RCP-' . str_pad($order->id, 8, '0', STR_PAD_LEFT),
                'order_id' => $order->id,
                'date' => $order->created_at->format('d/m/Y H:i'),
                'customer' => [
                    'name' => $order->profile->full_name ?? 'Cliente',
                    'email' => $order->profile->email ?? 'N/A',
                    'phone' => $order->profile->phone ?? 'N/A'
                ],
                'restaurant' => [
                    'name' => $order->commerce->name ?? 'Restaurante',
                    'address' => $order->commerce->address ?? 'N/A'
                ],
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_name' => $item->product->name ?? 'Producto',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total_price
                    ];
                }),
                'subtotal' => $order->subtotal,
                'tax' => $order->tax_amount ?? 0,
                'delivery_fee' => $order->delivery_fee ?? 0,
                'total' => $order->total_amount,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'transaction_id' => $order->payment_proof,
                'card_last_four' => $order->card_last_four ?? null
            ];

            return response()->json([
                'success' => true,
                'data' => $receipt
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating receipt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el comprobante'
            ], 500);
        }
    }

    /**
     * Obtener historial de pagos
     */
    public function getPaymentHistory(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $payments = Order::where('buyer_id', $user->id)
                ->whereNotNull('paid_at')
                ->with(['commerce'])
                ->orderBy('paid_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'order_id' => $order->id,
                        'amount' => $order->total_amount,
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'transaction_id' => $order->payment_proof,
                        'paid_at' => $order->paid_at->format('d/m/Y H:i'),
                        'restaurant' => $order->commerce->name ?? 'N/A',
                        'can_refund' => $this->canRequestRefund($order)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting payment history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de pagos'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de pagos
     */
    public function getPaymentStatistics(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $stats = [
                'total_payments' => Order::where('buyer_id', $user->id)
                    ->where('payment_status', 'paid')
                    ->count(),
                'total_spent' => Order::where('buyer_id', $user->id)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount'),
                'payment_methods_used' => Order::where('buyer_id', $user->id)
                    ->where('payment_status', 'paid')
                    ->select('payment_method', DB::raw('count(*) as count'))
                    ->groupBy('payment_method')
                    ->get(),
                'monthly_spending' => Order::where('buyer_id', $user->id)
                    ->where('payment_status', 'paid')
                    ->whereMonth('paid_at', now()->month)
                    ->sum('total_amount')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting payment statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de pagos'
            ], 500);
        }
    }

    /**
     * Procesar pago con pasarela específica
     */
    private function processPaymentWithGateway(array $cardData, string $gateway): array
    {
        switch ($gateway) {
            case 'stripe':
                return $this->processStripePayment($cardData);
            case 'mercadopago':
                return $this->processMercadoPagoCardPayment($cardData);
            case 'paypal':
                return $this->processPayPalCardPayment($cardData);
            default:
                return $this->simulateCardPayment($cardData);
        }
    }

    /**
     * Procesar pago con Stripe
     */
    private function processStripePayment(array $cardData): array
    {
        // Simulación de integración con Stripe
        $cardNumber = $cardData['card_number'];
        $lastDigit = substr($cardNumber, -1);
        
        if ($lastDigit === '0') {
            return [
                'success' => false,
                'message' => 'Tarjeta rechazada por Stripe',
                'transaction_id' => null
            ];
        }

        $transactionId = 'STRIPE-' . time() . '-' . rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => 'Pago procesado exitosamente con Stripe',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Procesar pago con MercadoPago
     */
    private function processMercadoPagoCardPayment(array $cardData): array
    {
        // Simulación de integración con MercadoPago
        $transactionId = 'MP-' . time() . '-' . rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => 'Pago procesado exitosamente con MercadoPago',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Procesar pago con PayPal
     */
    private function processPayPalCardPayment(array $cardData): array
    {
        // Simulación de integración con PayPal
        $transactionId = 'PP-' . time() . '-' . rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => 'Pago procesado exitosamente con PayPal',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Capturar pago de PayPal
     */
    private function capturePayPalPayment(string $paypalOrderId): array
    {
        // Simulación de captura de PayPal
        $transactionId = 'PP-CAPTURE-' . time() . '-' . rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => 'Pago capturado exitosamente',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Verificar pago de MercadoPago
     */
    private function verifyMercadoPagoPayment(string $paymentId): array
    {
        // Simulación de verificación de MercadoPago
        $transactionId = 'MP-VERIFY-' . time() . '-' . rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => 'Pago verificado exitosamente',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Verificar pago móvil
     */
    private function verifyMobilePayment(array $paymentData): array
    {
        // Simulación de verificación de pago móvil
        $referenceNumber = $paymentData['reference_number'];
        $lastDigit = substr($referenceNumber, -1);
        
        // Simular rechazo de referencias que terminan en 0
        if ($lastDigit === '0') {
            return [
                'success' => false,
                'message' => 'Referencia de pago móvil no encontrada',
                'transaction_id' => null
            ];
        }

        // Simular referencias que terminan en 5 como sin fondos
        if ($lastDigit === '5') {
            return [
                'success' => false,
                'message' => 'Fondos insuficientes en la cuenta',
                'transaction_id' => null
            ];
        }

        // Simular pago exitoso
        $transactionId = 'MOBILE-' . time() . '-' . rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => 'Pago móvil verificado exitosamente',
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Procesar reembolso automático
     */
    private function processAutomaticRefund(Order $order, string $reason, ?float $amount): array
    {
        $refundAmount = $amount ?? $order->total_amount;
        $refundId = 'REFUND-' . time() . '-' . rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => 'Reembolso procesado automáticamente',
            'refund_id' => $refundId,
            'amount' => $refundAmount
        ];
    }

    /**
     * Generar factura electrónica
     */
    private function generateElectronicInvoice(Order $order): array
    {
        $invoiceNumber = 'INV-' . str_pad($order->id, 8, '0', STR_PAD_LEFT);
        $invoiceUrl = 'https://api.zonix.com/invoices/' . $invoiceNumber;
        
        return [
            'number' => $invoiceNumber,
            'url' => $invoiceUrl
        ];
    }

    /**
     * Verificar si se puede solicitar reembolso
     */
    private function canRequestRefund(Order $order): bool
    {
        if ($order->payment_status !== 'paid') {
            return false;
        }

        $hoursSincePayment = now()->diffInHours($order->paid_at);
        return $hoursSincePayment <= 24;
    }

    /**
     * Simular procesamiento de pago con tarjeta
     */
    private function simulateCardPayment(array $cardData): array
    {
        // Simular validación de tarjeta
        $cardNumber = $cardData['card_number'];
        $lastDigit = substr($cardNumber, -1);
        
        // Simular rechazo de tarjetas que terminan en 0
        if ($lastDigit === '0') {
            return [
                'success' => false,
                'message' => 'Tarjeta rechazada por el banco',
                'transaction_id' => null
            ];
        }

        // Simular tarjetas que terminan en 5 como sin fondos
        if ($lastDigit === '5') {
            return [
                'success' => false,
                'message' => 'Fondos insuficientes',
                'transaction_id' => null
            ];
        }

        // Simular pago exitoso
        $transactionId = 'TXN-' . time() . '-' . rand(1000, 9999);
        
        return [
            'success' => true,
            'message' => 'Pago procesado exitosamente',
            'transaction_id' => $transactionId
        ];
    }
} 