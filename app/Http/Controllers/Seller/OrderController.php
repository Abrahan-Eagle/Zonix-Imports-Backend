<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Listar órdenes del vendedor
     * 
     * GET /api/seller/orders?status=X&delivery_type=Y&page=1
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $profile = $request->user()->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            $filters = [
                'status' => $request->query('status'),
                'delivery_type' => $request->query('delivery_type'),
                'per_page' => $request->query('per_page', 15)
            ];

            $orders = $this->orderService->getSellerOrders($profile, $filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                            'status' => $order->status,
                            'modality' => $order->modality,
                            'delivery_type' => $order->delivery_type,
                            'total' => (float) $order->total,
                            'buyer' => [
                                'id' => $order->profile->id,
                                'name' => $order->profile->firstName . ' ' . $order->profile->lastName,
                                'email' => $order->profile->user->email ?? null
                            ],
                            'items_count' => $order->orderItems->count(),
                            'created_at' => $order->created_at->format('Y-m-d H:i:s')
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al listar órdenes (seller): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener detalle de una orden (seller)
     * 
     * GET /api/seller/orders/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id)
    {
        try {
            $profile = $request->user()->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            $order = $this->orderService->getSellerOrderDetail($id, $profile);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                        'status' => $order->status,
                        'modality' => $order->modality,
                        'delivery_type' => $order->delivery_type,
                        'subtotal' => (float) $order->subtotal,
                        'discount_total' => (float) $order->discount_total,
                        'shipping_total' => (float) $order->shipping_total,
                        'total' => (float) $order->total,
                        'tracking_number' => $order->tracking_number,
                        'estimated_delivery' => $order->estimated_delivery?->format('Y-m-d'),
                        'notes' => $order->notes,
                        'buyer' => [
                            'id' => $order->profile->id,
                            'name' => $order->profile->firstName . ' ' . $order->profile->lastName,
                            'email' => $order->profile->user->email ?? null,
                            'phone' => $order->profile->phone
                        ],
                        'shipping_address' => [
                            'street' => $order->shippingAddress->street,
                            'house_number' => $order->shippingAddress->house_number,
                            'reference' => $order->shippingAddress->reference,
                            'city' => [
                                'name' => $order->shippingAddress->city->name,
                                'state' => [
                                    'name' => $order->shippingAddress->city->state->name
                                ]
                            ]
                        ],
                        'items' => $order->orderItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product' => [
                                    'id' => $item->product->id,
                                    'name' => $item->product->name,
                                    'image' => $item->product->image,
                                    'sku' => $item->product->sku
                                ],
                                'quantity' => $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'subtotal' => (float) $item->subtotal
                            ];
                        }),
                        'payments' => $order->payments->map(function ($payment) {
                            return [
                                'id' => $payment->id,
                                'method' => $payment->method,
                                'amount' => (float) $payment->amount,
                                'status' => $payment->status,
                                'reference' => $payment->reference,
                                'receipt_url' => $payment->receipt_url,
                                'processed_at' => $payment->processed_at?->format('Y-m-d H:i:s')
                            ];
                        }),
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $order->updated_at->format('Y-m-d H:i:s')
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al obtener detalle de orden (seller): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null
            ], $e->getMessage() === 'Orden no encontrada' ? 404 : 403);
        }
    }

    /**
     * Actualizar estado de la orden (seller)
     * 
     * PUT /api/seller/orders/{id}/status
     * Body: { status, tracking_number? }
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, int $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:paid,preparing,on_way,delivered,cancelled',
                'tracking_number' => 'nullable|string|max:100'
            ]);

            $profile = $request->user()->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            $order = $this->orderService->updateOrderStatus(
                $id,
                $profile,
                $request->status,
                $request->tracking_number
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado de orden actualizado',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                        'status' => $order->status,
                        'tracking_number' => $order->tracking_number,
                        'updated_at' => $order->updated_at->format('Y-m-d H:i:s')
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al actualizar estado de orden: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null
            ], 400);
        }
    }
}

