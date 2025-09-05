<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Get payment methods for user
     */
    public function getPaymentMethods()
    {
        try {
            // TODO: Implement actual payment methods from database
            $paymentMethods = [
                [
                    'id' => 1,
                    'type' => 'card',
                    'brand' => 'Visa',
                    'last4' => '1234',
                    'exp_month' => 12,
                    'exp_year' => 2025,
                    'is_default' => true,
                    'cardholder_name' => 'Juan Pérez',
                ],
                [
                    'id' => 2,
                    'type' => 'card',
                    'brand' => 'Mastercard',
                    'last4' => '5678',
                    'exp_month' => 8,
                    'exp_year' => 2026,
                    'is_default' => false,
                    'cardholder_name' => 'Juan Pérez',
                ],
                [
                    'id' => 3,
                    'type' => 'digital_wallet',
                    'brand' => 'PayPal',
                    'email' => 'juan.perez@email.com',
                    'is_default' => false,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $paymentMethods
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment methods: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment methods'
            ], 500);
        }
    }

    /**
     * Add new payment method
     */
    public function addPaymentMethod(Request $request)
    {
        try {
            $user = Auth::user();
            $commerce = $user->profile->commerce ?? null;
            if (!$commerce) {
                return response()->json(['success' => false, 'message' => 'No es comercio'], 403);
            }
            $request->validate([
                'type' => 'required|string',
                'bank_id' => 'nullable|exists:banks,id',
                'brand' => 'nullable|string',
                'account_number' => 'nullable|string',
                'phone' => 'nullable|string',
                'owner_name' => 'nullable|string',
                'owner_id' => 'nullable|string',
                'is_default' => 'boolean',
                'is_active' => 'boolean',
            ]);
            $exists = $commerce->paymentMethods()
                ->where('type', $request->type)
                ->where('bank_id', $request->bank_id)
                ->where('account_number', $request->account_number)
                ->where('phone', $request->phone)
                ->where('owner_name', $request->owner_name)
                ->where('owner_id', $request->owner_id)
                ->exists();
            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Ya existe un método de pago igual registrado.'], 422);
            }
            // Guardar realmente el método de pago
            $paymentMethod = $commerce->paymentMethods()->create([
                'type' => $request->type,
                'bank_id' => $request->bank_id,
                'brand' => $request->brand,
                'account_number' => $request->account_number,
                'phone' => $request->phone,
                'owner_name' => $request->owner_name,
                'owner_id' => $request->owner_id,
                'is_default' => $request->input('is_default', false),
                'is_active' => $request->input('is_active', true),
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Payment method added successfully',
                'data' => $paymentMethod
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error al crear método de pago de comercio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno al crear método de pago'], 500);
        }
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3',
                'payment_method_id' => 'required|integer',
                'order_id' => 'required|integer',
                'description' => 'required|string|max:255'
            ]);

            // TODO: Implement actual payment processing
            $transaction = [
                'id' => rand(10000, 99999),
                'amount' => $request->amount,
                'currency' => $request->currency,
                'status' => 'completed',
                'type' => 'payment',
                'payment_method_id' => $request->payment_method_id,
                'order_id' => $request->order_id,
                'description' => $request->description,
                'transaction_id' => 'txn_' . time() . '_' . rand(1000, 9999),
                'created_at' => now()->toISOString(),
                'processed_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment'
            ], 500);
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(Request $request)
    {
        try {
            $query = $request->all();
            
            // TODO: Implement actual transaction history from database
            $transactions = [
                [
                    'id' => 1,
                    'amount' => 45.85,
                    'currency' => 'PEN',
                    'status' => 'completed',
                    'type' => 'payment',
                    'description' => 'Order #ORD-001',
                    'transaction_id' => 'txn_1234567890',
                    'created_at' => now()->subDays(1)->toISOString(),
                ],
                [
                    'id' => 2,
                    'amount' => 33.88,
                    'currency' => 'PEN',
                    'status' => 'completed',
                    'type' => 'payment',
                    'description' => 'Order #ORD-002',
                    'transaction_id' => 'txn_1234567891',
                    'created_at' => now()->subDays(2)->toISOString(),
                ],
            ];

            // Apply filters
            if (isset($query['status'])) {
                $transactions = array_filter($transactions, function($t) use ($query) {
                    return $t['status'] === $query['status'];
                });
            }

            return response()->json([
                'success' => true,
                'data' => array_values($transactions)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching transaction history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching transaction history'
            ], 500);
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment(Request $request, $transactionId)
    {
        try {
            $request->validate([
                'amount' => 'numeric|min:0.01',
                'reason' => 'string|max:500'
            ]);

            // TODO: Implement actual refund processing
            $refund = [
                'id' => rand(10000, 99999),
                'original_transaction_id' => $transactionId,
                'amount' => $request->input('amount', 45.85),
                'currency' => 'PEN',
                'status' => 'completed',
                'type' => 'refund',
                'created_at' => now()->toISOString(),
                'transaction_id' => 'ref_' . time() . '_' . rand(1000, 9999),
                'description' => 'Refund for transaction #' . $transactionId,
                'reason' => $request->input('reason', 'Customer request')
            ];

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => $refund
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing refund: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing refund'
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            
            // TODO: Implement actual payment statistics from database
            $statistics = [
                'total_revenue' => 45680.50,
                'total_transactions' => 1247,
                'average_transaction_value' => 36.63,
                'successful_payments' => 1189,
                'failed_payments' => 58,
                'refunded_payments' => 23,
                'payment_success_rate' => 95.3,
                'monthly_growth' => 12.5,
                'top_payment_methods' => [
                    ['method' => 'Credit Card', 'usage' => 65, 'revenue' => 29692.33],
                    ['method' => 'Digital Wallet', 'usage' => 25, 'revenue' => 11420.13],
                    ['method' => 'Bank Transfer', 'usage' => 10, 'revenue' => 4568.05],
                ],
                'recent_transactions' => [
                    ['amount' => 45.85, 'status' => 'completed', 'date' => now()->subHours(1)->toISOString()],
                    ['amount' => 33.88, 'status' => 'completed', 'date' => now()->subHours(2)->toISOString()],
                    ['amount' => 28.50, 'status' => 'completed', 'date' => now()->subHours(3)->toISOString()],
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment statistics'
            ], 500);
        }
    }
} 