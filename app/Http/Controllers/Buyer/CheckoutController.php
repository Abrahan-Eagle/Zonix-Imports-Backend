<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateCheckoutRequest;
use App\Http\Requests\ConfirmCheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckoutController extends Controller
{
    protected $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    /**
     * Obtener resumen del checkout (sin confirmar)
     * 
     * GET /api/buyer/checkout/summary
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(Request $request)
    {
        try {
            $profile = $request->user()->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil de usuario no encontrado'
                ], 404);
            }

            $summary = $this->checkoutService->getCheckoutSummary($profile);

            if (!$summary['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $summary['message'],
                    'data' => [
                        'summary' => $summary['summary']
                    ]
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Resumen de checkout obtenido',
                'data' => [
                    'cart_items' => $summary['cart_items']->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'image' => $item->product->image,
                                'commerce' => [
                                    'id' => $item->product->commerce->id,
                                    'name' => $item->product->commerce->name
                                ]
                            ],
                            'quantity' => $item->quantity,
                            'modality' => $item->modality,
                            'unit_price' => (float) $item->unit_price,
                            'subtotal' => (float) $item->subtotal
                        ];
                    }),
                    'summary' => $summary['summary']
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al obtener resumen de checkout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de checkout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar proceso de checkout (validar datos)
     * 
     * POST /api/buyer/checkout/initiate
     * Body: { shipping_address_id, delivery_type, billing_address_id? }
     * 
     * @param InitiateCheckoutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(InitiateCheckoutRequest $request)
    {
        try {
            $profile = $request->user()->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil de usuario no encontrado'
                ], 404);
            }

            $checkoutData = $this->checkoutService->initiateCheckout(
                $profile,
                $request->shipping_address_id,
                $request->delivery_type,
                $request->billing_address_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Checkout iniciado exitosamente',
                'data' => [
                    'shipping_address' => [
                        'id' => $checkoutData['shipping_address']->id,
                        'street' => $checkoutData['shipping_address']->street,
                        'house_number' => $checkoutData['shipping_address']->house_number,
                        'address_line_2' => $checkoutData['shipping_address']->address_line_2,
                        'reference' => $checkoutData['shipping_address']->reference,
                        'city' => [
                            'id' => $checkoutData['shipping_address']->city->id,
                            'name' => $checkoutData['shipping_address']->city->name,
                            'state' => [
                                'id' => $checkoutData['shipping_address']->city->state->id,
                                'name' => $checkoutData['shipping_address']->city->state->name
                            ]
                        ]
                    ],
                    'billing_address' => $checkoutData['billing_address'] ? [
                        'id' => $checkoutData['billing_address']->id,
                        'street' => $checkoutData['billing_address']->street,
                        'house_number' => $checkoutData['billing_address']->house_number,
                        'city' => [
                            'id' => $checkoutData['billing_address']->city->id,
                            'name' => $checkoutData['billing_address']->city->name
                        ]
                    ] : null,
                    'delivery_type' => $checkoutData['delivery_type'],
                    'cart_items' => $checkoutData['cart_items']->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'image' => $item->product->image
                            ],
                            'quantity' => $item->quantity,
                            'modality' => $item->modality,
                            'unit_price' => (float) $item->unit_price,
                            'subtotal' => (float) $item->subtotal
                        ];
                    }),
                    'summary' => $checkoutData['summary']
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al iniciar checkout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : 'Error al iniciar checkout'
            ], 400);
        }
    }

    /**
     * Confirmar checkout y crear orden
     * 
     * POST /api/buyer/checkout/confirm
     * Body: { shipping_address_id, delivery_type, billing_address_id?, notes? }
     * 
     * @param ConfirmCheckoutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(ConfirmCheckoutRequest $request)
    {
        try {
            $profile = $request->user()->profile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil de usuario no encontrado'
                ], 404);
            }

            $order = $this->checkoutService->confirmCheckout(
                $profile,
                $request->shipping_address_id,
                $request->delivery_type,
                $request->billing_address_id,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Orden creada exitosamente',
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
                        'estimated_delivery' => $order->estimated_delivery?->format('Y-m-d'),
                        'shipping_address' => [
                            'id' => $order->shippingAddress->id,
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
                        'commerce' => [
                            'id' => $order->commerce->id,
                            'name' => $order->commerce->name
                        ],
                        'items' => $order->orderItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product' => [
                                    'id' => $item->product->id,
                                    'name' => $item->product->name,
                                    'image' => $item->product->image
                                ],
                                'quantity' => $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'subtotal' => (float) $item->subtotal
                            ];
                        }),
                        'created_at' => $order->created_at->format('Y-m-d H:i:s')
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error('Error al confirmar checkout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : 'Error al confirmar checkout'
            ], 400);
        }
    }
}

