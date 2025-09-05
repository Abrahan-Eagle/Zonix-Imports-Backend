<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class BroadcastingController extends Controller
{
    /**
     * Autenticar conexiones de broadcasting para canales privados
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'channel_name' => 'required|string',
            'socket_id' => 'required|string',
        ]);

        $channelName = $request->channel_name;
        $socketId = $request->socket_id;
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verificar autorización basada en el tipo de canal
        if (str_starts_with($channelName, 'App.Models.User.')) {
            // Canal de usuario específico
            $userId = (int) str_replace('App.Models.User.', '', $channelName);
            if ((int) $user->id === $userId) {
                return response()->json([
                    'success' => true,
                    'channel' => $channelName,
                    'socket_id' => $socketId
                ]);
            }
        } elseif (str_starts_with($channelName, 'orders.')) {
            // Canal de orden específica
            $orderId = (int) str_replace('orders.', '', $channelName);
            $order = Order::find($orderId);
            
            if ($order && $this->userCanAccessOrder($user, $order)) {
                return response()->json([
                    'success' => true,
                    'channel' => $channelName,
                    'socket_id' => $socketId
                ]);
            }
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * Verificar si el usuario puede acceder a una orden
     */
    private function userCanAccessOrder($user, $order)
    {
        // Usuario puede escuchar si es el comprador, comercio o repartidor
        return $order->profile_id === $user->profile?->id || 
               $order->commerce_id === $user->profile?->commerce?->id ||
               $order->orderDelivery?->agent_id === $user->profile?->deliveryAgent?->id;
    }
} 