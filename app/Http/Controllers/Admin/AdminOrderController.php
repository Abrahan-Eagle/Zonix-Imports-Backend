<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Commerce;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index()
    {
        return response()->json(Order::all());
    }

    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:pending_payment,paid,preparing,on_way,delivered,cancelled'
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->input('status');
        $order->save();
        return response()->json(['message' => 'Estado actualizado', 'order' => $order]);
    }

    public function commerces()
    {
        return response()->json(Commerce::all());
    }
}
