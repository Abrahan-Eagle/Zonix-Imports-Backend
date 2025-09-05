<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Commerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommerceOrderController extends Controller
{
    /**
     * Mostrar todas las órdenes del comercio
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        if (!$profile || !$profile->commerce) {
            return response()->json(['error' => 'User is not associated with a commerce'], 403);
        }

        $commerce = $profile->commerce;
        
        // Verificar si se solicita un commerce_id específico
        if ($request->has('commerce_id') && $request->commerce_id != $commerce->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $orders = Order::where('commerce_id', $commerce->id)
            ->with(['profile.user', 'orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Mostrar una orden específica
     */
    public function show(Order $order)
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        if (!$profile || !$profile->commerce) {
            return response()->json(['error' => 'User is not associated with a commerce'], 403);
        }

        // Verificar que la orden pertenece al comercio del usuario
        if ($order->commerce_id !== $profile->commerce->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($order->load(['profile.user', 'orderItems.product', 'orderDelivery']));
    }

    /**
     * Actualizar el estado de una orden
     */
    public function updateStatus(Request $request, Order $order)
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        if (!$profile || !$profile->commerce) {
            return response()->json(['error' => 'User is not associated with a commerce'], 403);
        }

        // Verificar que la orden pertenece al comercio del usuario
        if ($order->commerce_id !== $profile->commerce->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,picked_up,delivered,cancelled'
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->fresh()
        ]);
    }
} 