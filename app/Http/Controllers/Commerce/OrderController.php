<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->profile) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
        $commerce = $user->commerce;
        if (!$commerce) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $orders = Order::query()
            ->where('commerce_id', $commerce->id)
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
        if (!$user || !$user->profile || !$user->commerce) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $order = Order::findOrFail($id);
        if ((int) $order->commerce_id !== (int) $user->commerce->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $order->status = $request->input('status');
        $order->save();

        return response()->json(['message' => 'Status updated', 'order' => $order]);
    }
}


