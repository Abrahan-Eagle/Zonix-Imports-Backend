<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Commerce;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->profile) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
        $profile = $user->profile;
        $commerceId = $profile ? (optional($profile->commerce)->id ?? Commerce::where('profile_id', $profile->id)->value('id')) : null;
        if (!$commerceId) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $orders = Order::query()
            ->where('commerce_id', $commerceId)
            ->latest()
            ->paginate(10);
        return response()->json($orders);
    }

    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user || !$user->profile) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
        $profile = $user->profile;
        $commerceId = $profile ? (optional($profile->commerce)->id ?? Commerce::where('profile_id', $profile->id)->value('id')) : null;
        $order = Order::findOrFail($id);
        if (!$commerceId || (int) $order->commerce_id !== (int) $commerceId) {
            if (app()->environment('local') && (bool) config('app.demo_allow_seller_update_any', true)) {
                // Permitir en entorno local para demo
            } else {
            return response()->json(['error' => 'No autorizado'], 403);
            }
        }

        $order->status = $request->input('status');
        $order->save();

        return response()->json(['message' => 'Status updated', 'order' => $order]);
    }
}


