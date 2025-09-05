<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Events\NotificationCreated;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Listar notificaciones del usuario autenticado
    public function getNotifications()
    {
        $profile = Auth::user()->profile;
        $notifications = Notification::where('profile_id', $profile->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['success' => true, 'data' => $notifications]);
    }

    // Marcar una notificación como leída
    public function markAsRead($notificationId)
    {
        $profile = Auth::user()->profile;
        $notification = Notification::where('id', $notificationId)
            ->where('profile_id', $profile->id)
            ->firstOrFail();
        $notification->read_at = now();
        $notification->save();
        return response()->json(['success' => true]);
    }

    // Crear notificación (para pruebas/admin)
    public function store(Request $request)
    {
        $profile = Auth::user()->profile;
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|string|max:50',
            'data' => 'nullable|array',
        ]);
        $notification = Notification::create([
            'profile_id' => $profile->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? null,
            'data' => $data['data'] ?? null,
        ]);

        // Emitir evento para WebSocket
        event(new NotificationCreated($notification));

        return response()->json(['success' => true, 'data' => $notification]);
    }

    // Eliminar notificación
    public function delete($notificationId)
    {
        $profile = Auth::user()->profile;
        $notification = Notification::where('id', $notificationId)
            ->where('profile_id', $profile->id)
            ->firstOrFail();
        $notification->delete();
        return response()->json(['success' => true]);
    }
} 