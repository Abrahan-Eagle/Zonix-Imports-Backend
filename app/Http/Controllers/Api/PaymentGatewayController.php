<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\Payment;

class PaymentGatewayController extends Controller
{
    public function apiPayment(Request $request, string $provider): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $order = Order::findOrFail($data['order_id']);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => $provider,
            'amount' => $data['amount'],
            'status' => 'pending',
            'currency' => $data['currency'] ?? 'USD',
            'reference' => null,
        ]);

        return response()->json(['success' => true, 'payment' => $payment], 201);
    }

    public function comprobante(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'method' => ['required', 'in:pago_movil,zelle'],
            'amount' => ['required', 'numeric', 'min:0'],
            'receipt_url' => ['required', 'string', 'max:2048'],
        ]);

        $payment = Payment::create([
            'order_id' => $data['order_id'],
            'method' => $data['method'],
            'amount' => $data['amount'],
            'status' => 'pending',
            'receipt_url' => $data['receipt_url'],
            'currency' => 'USD',
        ]);

        return response()->json(['success' => true, 'payment' => $payment], 201);
    }
}


