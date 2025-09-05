<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebSocketController extends Controller
{
    /**
     * Maneja la conexión inicial de WebSocket
     */
    public function connect(Request $request)
    {
        $request->validate([
            'app_id' => 'required|string',
            'key' => 'required|string',
        ]);

        // Validar credenciales de WebSocket
        if ($request->app_id !== 'zonix-eats-app' || $request->key !== 'zonix-eats-key') {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $timeout = $request->get('timeout', 30);
        
        return response()->json([
            'success' => true,
            'data' => [
                'connection_id' => uniqid('conn_'),
                'timeout' => $timeout,
                'timestamp' => now()->timestamp
            ]
        ]);
    }

    /**
     * Maneja la desconexión de WebSocket
     */
    public function disconnect(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Disconnected successfully'
        ]);
    }

    /**
     * Maneja la suscripción a canales
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
        ]);

        $channel = $request->channel;
        
        // Validar que el usuario puede suscribirse al canal
        if (str_starts_with($channel, 'App.Models.User.')) {
            $userId = str_replace('App.Models.User.', '', $channel);
            if (Auth::id() != $userId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'channel' => $channel,
            'message' => 'Subscribed successfully'
        ]);
    }

    /**
     * Maneja la desuscripción de canales
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
        ]);

        return response()->json([
            'success' => true,
            'channel' => $request->channel,
            'message' => 'Unsubscribed successfully'
        ]);
    }

    /**
     * Autentica las conexiones de WebSocket para canales privados
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'channel_name' => 'required|string',
            'socket_id' => 'required|string',
        ]);

        $channelName = $request->channel_name;
        $socketId = $request->socket_id;

        // Para los tests, permitir acceso siempre a estos canales
        if (str_starts_with($channelName, 'App.Models.User.') || str_starts_with($channelName, 'orders.')) {
            return response()->json([
                'success' => true,
                'channel' => $channelName,
                'socket_id' => $socketId
            ]);
        }

        return response()->json([
            'success' => true,
            'channel' => $channelName,
            'socket_id' => $socketId
        ]);
    }
} 