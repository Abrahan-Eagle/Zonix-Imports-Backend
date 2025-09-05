<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Order;
use App\Models\User;
use App\Models\Commerce;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal para usuario específico
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal para órdenes específicas
Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);
    if (!$order) return false;
    
    // Usuario puede escuchar si es el comprador (profile_id), comercio o repartidor
    return $order->profile_id === $user->profile?->id || 
           $order->commerce_id === $user->profile?->commerce?->id ||
           $order->orderDelivery?->agent_id === $user->profile?->deliveryAgent?->id;
});

// Canal para comercio específico
Broadcast::channel('commerce.{commerceId}', function ($user, $commerceId) {
    return $user->role === 'commerce' && $user->profile?->commerce?->id === (int) $commerceId;
});

// Canal para repartidor específico
Broadcast::channel('delivery.{deliveryAgentId}', function ($user, $deliveryAgentId) {
    return $user->role === 'delivery' && $user->profile?->deliveryAgent?->id === (int) $deliveryAgentId;
});

// Canal para usuario específico (alias)
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal público para órdenes (solo lectura)
Broadcast::channel('orders', function ($user) {
    return $user->role === 'commerce' || $user->role === 'delivery' || $user->role === 'users';
});

// Canal de presencia para chat de órdenes
Broadcast::channel('presence-chat.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);
    if (!$order) return false;
    
    // Verificar que el usuario tiene acceso a esta orden
    $hasAccess = $order->profile_id === $user->profile?->id || 
                 $order->commerce_id === $user->profile?->commerce?->id ||
                 $order->orderDelivery?->agent_id === $user->profile?->deliveryAgent?->id;
    
    if ($hasAccess) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ];
    }
    
    return false;
});
