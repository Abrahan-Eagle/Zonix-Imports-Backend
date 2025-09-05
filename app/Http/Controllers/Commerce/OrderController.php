<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PaymentValidated;
use App\Events\OrderStatusChanged;

class OrderController extends Controller
{
  public function index(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            \Log::error('Error al listar órdenes de comercio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno al listar órdenes'], 500);
        }
    }

    public function show(Order $order)
    {
        try {
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
        } catch (\Exception $e) {
            \Log::error('Error al mostrar orden de comercio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno al mostrar orden'], 500);
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        try {
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
                'status' => 'required|in:pending_payment,paid,preparing,on_way,delivered,cancelled'
            ]);

            $order->update(['status' => $request->status]);

            // Notificación: estado de orden actualizado
            // Notification::send($order->profile->user, new OrderStatusUpdated($order));
            return response()->json(['success' => true, 'message' => 'Estado de la orden actualizado']);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar estado de orden de comercio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno al actualizar estado de orden'], 500);
        }
    }

    /**
     * Validar o rechazar comprobante de pago de una orden.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function validarComprobante(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'is_valid' => 'required|boolean',
                'rejection_reason' => 'nullable|string|max:500',
            ]);

            $order = Order::findOrFail($id);
            $user = Auth::user();

            // Verificar que el usuario es el dueño del comercio
            if ($order->commerce_id !== $user->commerce?->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para validar esta orden'
                ], 403);
            }

            if ($validated['is_valid']) {
                $order->estado = 'pagado';
                $order->save();
                
                // Emitir eventos
                event(new PaymentValidated($order, true, $user->id));
                event(new OrderStatusChanged($order));
                
                $message = 'Pago validado correctamente';
            } else {
                $order->estado = 'pago_rechazado';
                $order->notas = $validated['rejection_reason'] ?? 'Pago rechazado por el comercio';
                $order->save();
                
                // Emitir eventos
                event(new PaymentValidated($order, false, $user->id));
                event(new OrderStatusChanged($order));
                
                $message = 'Pago rechazado';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'order' => $order
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar el comprobante: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar o rechazar comprobante de pago de una orden.
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function validatePayment(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'is_valid' => 'required|boolean',
                'rejection_reason' => 'nullable|string|max:500',
            ]);

            $order = Order::findOrFail($id);
            $user = Auth::user();
            $profile = $user->profile;

            // Verificar que el usuario es el dueño del comercio
            if (!$profile || !$profile->commerce || $order->commerce_id !== $profile->commerce->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para validar esta orden'
                ], 403);
            }

            if ($validated['is_valid']) {
                $order->update([
                    'status' => 'paid',
                    'payment_validated_at' => now(),
                    'cancellation_reason' => null
                ]);
                
                $message = 'Pago validado correctamente';
            } else {
                $order->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $validated['rejection_reason'] ?? 'Pago rechazado por el comercio',
                    'payment_validated_at' => null
                ]);
                
                $message = 'Pago rechazado';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'order' => $order
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar el comprobante: ' . $e->getMessage()
            ], 500);
        }
    }
}
