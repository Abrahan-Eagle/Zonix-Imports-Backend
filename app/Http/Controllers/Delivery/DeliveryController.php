<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\DeliveryAgent;
use App\Models\OrderDelivery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    /**
     * Get available orders for delivery
     */
    public function getAvailableOrders()
    {
        try {
            $availableOrders = Order::with(['commerce', 'profile', 'items'])
                ->whereIn('status', ['paid', 'preparing'])
                ->whereDoesntHave('delivery')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $availableOrders
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching available orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching available orders'
            ], 500);
        }
    }

    /**
     * Get orders assigned to delivery agent
     */
    public function getAssignedOrders($deliveryAgentId)
    {
        try {
            $assignedOrders = Order::with(['commerce', 'profile', 'items', 'delivery'])
                ->whereHas('delivery', function ($query) use ($deliveryAgentId) {
                    $query->where('agent_id', $deliveryAgentId);
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignedOrders
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching assigned orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching assigned orders'
            ], 500);
        }
    }

    /**
     * Accept order for delivery
     */
    public function acceptOrder(Request $request, $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            if ($order->status !== 'paid' && $order->status !== 'preparing') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not available for delivery'
                ], 400);
            }

            $deliveryAgent = DeliveryAgent::where('profile_id', Auth::user()->profile->id)->first();
            
            if (!$deliveryAgent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery agent not found'
                ], 404);
            }

            // Verificar que la orden no esté ya asignada
            if ($order->delivery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already assigned'
                ], 400);
            }

            // Create delivery assignment
            OrderDelivery::create([
                'order_id' => $orderId,
                'agent_id' => $deliveryAgent->id,
                'status' => 'assigned',
                'costo_envio' => $order->delivery_fee ?? 0,
                'notas' => $request->input('notes', '')
            ]);

            // Update order status
            $order->update(['status' => 'on_way']);

            return response()->json([
                'message' => 'Orden aceptada',
                'success' => true,
                'data' => $order->load(['commerce', 'profile', 'items', 'delivery'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error accepting order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error accepting order'
            ], 500);
        }
    }

    /**
     * Update delivery location
     */
    public function updateLocation(Request $request)
    {
        try {
            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric'
            ]);

            $deliveryAgent = DeliveryAgent::where('profile_id', Auth::user()->profile->id)->first();
            
            if (!$deliveryAgent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery agent not found'
                ], 404);
            }

            $deliveryAgent->update([
                'current_latitude' => $request->latitude,
                'current_longitude' => $request->longitude,
                'last_location_update' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating location'
            ], 500);
        }
    }

    /**
     * Get delivery statistics
     */
    public function getStatistics($deliveryAgentId)
    {
        try {
            $deliveryAgent = DeliveryAgent::findOrFail($deliveryAgentId);
            
            $totalDeliveries = OrderDelivery::where('agent_id', $deliveryAgentId)->count();
            $completedDeliveries = OrderDelivery::where('agent_id', $deliveryAgentId)
                ->where('status', 'delivered')->count();
            $cancelledDeliveries = OrderDelivery::where('agent_id', $deliveryAgentId)
                ->where('status', 'cancelled')->count();

            $totalEarnings = OrderDelivery::where('agent_id', $deliveryAgentId)
                ->where('status', 'delivered')
                ->sum('costo_envio');

            $statistics = [
                'total_deliveries' => $totalDeliveries,
                'completed_deliveries' => $completedDeliveries,
                'cancelled_deliveries' => $cancelledDeliveries,
                'total_earnings' => $totalEarnings,
                'average_rating' => 4.7, // TODO: Calculate from reviews
                'total_reviews' => 89, // TODO: Count actual reviews
                'on_time_deliveries' => $completedDeliveries, // TODO: Calculate based on estimated time
                'late_deliveries' => 0, // TODO: Calculate based on estimated time
                'average_delivery_time' => 28, // TODO: Calculate actual average
                'total_distance' => 1250.5, // TODO: Calculate from tracking data
                'fuel_efficiency' => 85.2, // TODO: Calculate from vehicle data
                'customer_satisfaction' => 92.5, // TODO: Calculate from ratings
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching delivery statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching delivery statistics'
            ], 500);
        }
    }

    /**
     * Report delivery issue
     */
    public function reportIssue(Request $request, $orderId)
    {
        try {
            $request->validate([
                'issue' => 'required|string|max:255',
                'description' => 'required|string|max:1000'
            ]);

            $order = Order::findOrFail($orderId);
            
            // TODO: Create support ticket or issue record
            Log::info('Delivery issue reported', [
                'order_id' => $orderId,
                'issue' => $request->issue,
                'description' => $request->description,
                'delivery_agent_id' => Auth::user()->profile->deliveryAgent->id ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Issue reported successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error reporting delivery issue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error reporting issue'
            ], 500);
        }
    }

    /**
     * Update order status for delivery
     */
    public function updateOrderStatus($orderId, Request $request)
    {
        try {
            $order = Order::whereHas('delivery', function($query) {
                $query->where('agent_id', Auth::user()->profile->deliveryAgent->id);
            })->findOrFail($orderId);

            $request->validate([
                'status' => 'required|in:on_way,delivered'
            ]);

            $order->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Estado de la orden actualizado'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado de orden de delivery: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar estado de orden'
            ], 500);
        }
    }
} 