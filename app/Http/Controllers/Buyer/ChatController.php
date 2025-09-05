<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\ChatMessage;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Obtener mensajes del chat de un pedido
     */
    public function getChatMessages($orderId): JsonResponse
    {
        try {
            $order = Order::with(['commerce', 'deliveryAgent'])
                ->findOrFail($orderId);

            // Verificar que el usuario tiene acceso al chat
            if ($order->profile_id !== auth()->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a este chat'
                ], 403);
            }

            $messages = ChatMessage::with(['sender'])
                ->where('order_id', $orderId)
                ->orderBy('created_at', 'asc')
                ->get();

            $messagesData = $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'type' => $message->type, // text, image, location
                    'sender_type' => $message->sender_type, // customer, restaurant, delivery_agent
                    'sender_name' => $message->sender->full_name ?? 'Usuario',
                    'sender_avatar' => $message->sender->avatar_url ?? null,
                    'is_own_message' => $message->sender_id === auth()->user()->profile->id,
                    'created_at' => $message->created_at->format('H:i'),
                    'timestamp' => $message->created_at->toISOString()
                ];
            });

            $chatInfo = [
                'order_id' => $order->id,
                'restaurant_name' => $order->commerce->name ?? 'Restaurante',
                'delivery_agent_name' => $order->deliveryAgent->name ?? null,
                'participants' => [
                    'customer' => [
                        'name' => auth()->user()->profile->full_name ?? 'Cliente',
                        'avatar' => auth()->user()->profile->avatar_url ?? null
                    ],
                    'restaurant' => [
                        'name' => $order->commerce->name ?? 'Restaurante',
                        'avatar' => $order->commerce->logo_url ?? null
                    ]
                ]
            ];

            if ($order->deliveryAgent) {
                $chatInfo['participants']['delivery_agent'] = [
                    'name' => $order->deliveryAgent->name ?? 'Repartidor',
                    'avatar' => $order->deliveryAgent->avatar_url ?? null
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chat_info' => $chatInfo,
                    'messages' => $messagesData
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting chat messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mensajes'
            ], 500);
        }
    }

    /**
     * Enviar mensaje
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'content' => 'required|string|max:1000',
            'type' => 'required|in:text,image,location',
            'recipient_type' => 'required|in:restaurant,delivery_agent,all'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);

            // Verificar que el usuario tiene acceso al chat
            if ($order->profile_id !== auth()->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para enviar mensajes en este chat'
                ], 403);
            }

            // Crear el mensaje
            $message = ChatMessage::create([
                'order_id' => $order->id,
                'sender_id' => auth()->user()->profile->id,
                'sender_type' => 'customer',
                'recipient_type' => $request->recipient_type,
                'content' => $request->content,
                'type' => $request->type,
                'created_at' => now()
            ]);

            // Cargar la relación del remitente
            $message->load('sender');

            $messageData = [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender->full_name ?? 'Cliente',
                'sender_avatar' => $message->sender->avatar_url ?? null,
                'is_own_message' => true,
                'created_at' => $message->created_at->format('H:i'),
                'timestamp' => $message->created_at->toISOString()
            ];

            // Aquí se enviaría la notificación push al destinatario
            $this->sendPushNotification($order, $message);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado exitosamente',
                'data' => $messageData
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje'
            ], 500);
        }
    }

    /**
     * Marcar mensajes como leídos
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:chat_messages,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);

            // Verificar que el usuario tiene acceso al chat
            if ($order->profile_id !== auth()->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a este chat'
                ], 403);
            }

            // Marcar mensajes como leídos
            ChatMessage::whereIn('id', $request->message_ids)
                ->where('sender_id', '!=', auth()->user()->profile->id)
                ->update([
                    'read_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensajes marcados como leídos'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking messages as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar los mensajes como leídos'
            ], 500);
        }
    }

    /**
     * Obtener mensajes no leídos
     */
    public function getUnreadMessages($orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            // Verificar que el usuario tiene acceso al chat
            if ($order->profile_id !== auth()->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a este chat'
                ], 403);
            }

            $unreadMessages = ChatMessage::where('order_id', $orderId)
                ->where('sender_id', '!=', auth()->user()->profile->id)
                ->whereNull('read_at')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadMessages
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting unread messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mensajes no leídos'
            ], 500);
        }
    }

    /**
     * Enviar notificación push (simulado)
     */
    private function sendPushNotification(Order $order, ChatMessage $message): void
    {
        // Aquí se implementaría el envío real de notificaciones push
        // Por ahora solo se registra en el log
        Log::info('Push notification sent', [
            'order_id' => $order->id,
            'message_id' => $message->id,
            'recipient_type' => $message->recipient_type
        ]);
    }
} 