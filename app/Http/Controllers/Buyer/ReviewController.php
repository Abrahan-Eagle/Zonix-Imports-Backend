<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\Review;
use App\Models\Commerce;
use App\Models\DeliveryAgent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    /**
     * Calificar restaurante
     */
    public function rateRestaurant(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:500',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:2048'
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
            
            // Verificar que el pedido pertenece al usuario
            if ($order->profile_id !== auth()->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para calificar este pedido'
                ], 403);
            }

            // Verificar que el pedido está entregado
            if ($order->status !== 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes calificar pedidos entregados'
                ], 400);
            }

            // Verificar que no se haya calificado antes
            $existingReview = Review::where('order_id', $order->id)
                ->where('type', 'restaurant')
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has calificado este restaurante'
                ], 400);
            }

            // Procesar fotos si las hay
            $photoUrls = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('reviews', 'public');
                    $photoUrls[] = Storage::url($path);
                }
            }

            // Crear la reseña
            $review = Review::create([
                'order_id' => $order->id,
                'commerce_id' => $order->commerce_id,
                'profile_id' => $order->profile_id,
                'type' => 'restaurant',
                'rating' => $request->rating,
                'comment' => $request->comment,
                'photos' => json_encode($photoUrls),
                'created_at' => now()
            ]);

            // Actualizar calificación promedio del restaurante
            $this->updateCommerceRating($order->commerce_id);

            return response()->json([
                'success' => true,
                'message' => 'Calificación enviada exitosamente',
                'data' => [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'photos' => $photoUrls
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error rating restaurant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la calificación'
            ], 500);
        }
    }

    /**
     * Calificar repartidor
     */
    public function rateDeliveryAgent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::with('deliveryAgent')->findOrFail($request->order_id);
            
            // Verificar que el pedido pertenece al usuario
            if ($order->profile_id !== auth()->user()->profile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para calificar este pedido'
                ], 403);
            }

            // Verificar que el pedido está entregado
            if ($order->status !== 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes calificar pedidos entregados'
                ], 400);
            }

            // Verificar que hay un repartidor asignado
            if (!$order->deliveryAgent) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay repartidor asignado a este pedido'
                ], 400);
            }

            // Verificar que no se haya calificado antes
            $existingReview = Review::where('order_id', $order->id)
                ->where('type', 'delivery_agent')
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has calificado este repartidor'
                ], 400);
            }

            // Crear la reseña
            $review = Review::create([
                'order_id' => $order->id,
                'delivery_agent_id' => $order->delivery_agent_id,
                'profile_id' => $order->profile_id,
                'type' => 'delivery_agent',
                'rating' => $request->rating,
                'comment' => $request->comment,
                'created_at' => now()
            ]);

            // Actualizar calificación promedio del repartidor
            $this->updateDeliveryAgentRating($order->delivery_agent_id);

            return response()->json([
                'success' => true,
                'message' => 'Calificación enviada exitosamente',
                'data' => [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error rating delivery agent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la calificación'
            ], 500);
        }
    }

    /**
     * Obtener reseñas de un restaurante
     */
    public function getRestaurantReviews($commerceId): JsonResponse
    {
        try {
            $reviews = Review::with(['profile'])
                ->where('commerce_id', $commerceId)
                ->where('type', 'restaurant')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $reviewsData = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'photos' => json_decode($review->photos, true) ?? [],
                    'customer_name' => $review->profile->full_name ?? 'Cliente',
                    'customer_avatar' => $review->profile->avatar_url ?? null,
                    'created_at' => $review->created_at->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'reviews' => $reviewsData,
                    'pagination' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'per_page' => $reviews->perPage(),
                        'total' => $reviews->total()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting restaurant reviews: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reseñas'
            ], 500);
        }
    }

    /**
     * Obtener reseñas de un repartidor
     */
    public function getDeliveryAgentReviews($agentId): JsonResponse
    {
        try {
            $reviews = Review::with(['profile'])
                ->where('delivery_agent_id', $agentId)
                ->where('type', 'delivery_agent')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $reviewsData = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'customer_name' => $review->profile->full_name ?? 'Cliente',
                    'customer_avatar' => $review->profile->avatar_url ?? null,
                    'created_at' => $review->created_at->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'reviews' => $reviewsData,
                    'pagination' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'per_page' => $reviews->perPage(),
                        'total' => $reviews->total()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting delivery agent reviews: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reseñas'
            ], 500);
        }
    }

    /**
     * Actualizar calificación promedio del restaurante
     */
    private function updateCommerceRating($commerceId): void
    {
        $averageRating = Review::where('commerce_id', $commerceId)
            ->where('type', 'restaurant')
            ->avg('rating');

        Commerce::where('id', $commerceId)->update([
            'average_rating' => round($averageRating, 1),
            'total_reviews' => Review::where('commerce_id', $commerceId)
                ->where('type', 'restaurant')
                ->count()
        ]);
    }

    /**
     * Actualizar calificación promedio del repartidor
     */
    private function updateDeliveryAgentRating($agentId): void
    {
        $averageRating = Review::where('delivery_agent_id', $agentId)
            ->where('type', 'delivery_agent')
            ->avg('rating');

        DeliveryAgent::where('id', $agentId)->update([
            'average_rating' => round($averageRating, 1),
            'total_reviews' => Review::where('delivery_agent_id', $agentId)
                ->where('type', 'delivery_agent')
                ->count()
        ]);
    }
} 