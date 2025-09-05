<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
public function index()
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            
            // Verificar que el usuario tenga un deliveryAgent
            if (!$profile || !$profile->deliveryAgent) {
                return response()->json(['error' => 'User is not a delivery agent'], 403);
            }
            
            // Obtener órdenes asignadas al delivery agent actual
            $orders = Order::whereHas('orderDelivery', function($query) use ($profile) {
                $query->where('agent_id', $profile->deliveryAgent->id);
            })->orderBy('created_at', 'desc')->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            \Log::error('Error al listar órdenes de delivery: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno al listar órdenes'], 500);
        }
    }

    public function availableOrders()
    {
        try {
            // Obtener órdenes disponibles para asignar
            $orders = Order::whereDoesntHave('orderDelivery')
                ->where('status', 'paid')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            \Log::error('Error al listar órdenes disponibles de delivery: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno al listar órdenes disponibles'], 500);
        }
    }

    public function acceptOrder($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            // Verificar que la orden no esté ya asignada
            if ($order->orderDelivery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already assigned'
                ], 400);
            }

            // Asignar la orden al delivery agent
            $order->orderDelivery()->create([
                'agent_id' => Auth::user()->profile->deliveryAgent->id,
                'status' => 'assigned'
            ]);

            // Actualizar estado de la orden
            $order->update(['status' => 'on_way']);

            return response()->json([
                'message' => 'Orden aceptada',
                'success' => true,
                'data' => $order->load(['orderDelivery'])
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al aceptar orden de delivery: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al aceptar orden'
            ], 500);
        }
    }

    public function updateOrderStatus($orderId, Request $request)
    {
        try {
            $order = Order::whereHas('orderDelivery', function($query) {
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
            \Log::error('Error al actualizar estado de orden de delivery: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar estado de orden'
            ], 500);
        }
    }

    public function updateStatus($id, Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        if (!$profile || !$profile->deliveryAgent) {
            return response()->json(['success' => false, 'message' => 'User is not a delivery agent'], 403);
        }

        $order = Order::whereHas('orderDelivery', function($query) use ($profile) {
            $query->where('agent_id', $profile->deliveryAgent->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:on_way,delivered'
        ]);

        $order->update([
            'status' => $validated['status']
        ]);

        return response()->json(['success' => true, 'message' => 'Estado de la orden actualizado']);
    }

    public function markAsDelivered($id)
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        if (!$profile || !$profile->deliveryAgent) {
            return response()->json(['error' => 'User is not a delivery agent'], 403);
        }

        $order = Order::whereHas('orderDelivery', function($query) use ($profile) {
            $query->where('agent_id', $profile->deliveryAgent->id);
        })->findOrFail($id);

        $order->update([
            'status' => 'delivered'
        ]);

        return response()->json(['message' => 'Pedido entregado con éxito']);
    }

    public function toggleWorkingStatus(Request $request)
        {
            $agent = DeliveryAgent::where('user_id', Auth::id())->firstOrFail();

            $agent->update([
                'trabajando' => !$agent->trabajando
            ]);

            return response()->json([
                'message' => 'Estado actualizado',
                'trabajando' => $agent->trabajando
            ]);
        }
}
