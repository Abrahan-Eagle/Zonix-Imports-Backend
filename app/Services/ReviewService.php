<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Order;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ReviewService
{
    /**
     * Crear una nueva calificación.
     *
     * @param array $data
     * @return array
     */
    public function createReview($data)
    {
        // Obtener el usuario
        $user = isset($data['user_id']) ? User::find($data['user_id']) : Auth::user();
        if (!$user) {
            Log::error('ReviewService::createReview - Usuario no encontrado');
            throw new \Exception('Usuario no encontrado');
        }
        
        // Obtener el perfil del usuario
        $profile = Profile::where('user_id', $user->id)->first();
        if (!$profile) {
            Log::error('ReviewService::createReview - Perfil no encontrado para usuario', ['user_id' => $user->id]);
            throw new \Exception('Perfil no encontrado');
        }
        
        // Verificar si el usuario puede hacer review
        if (!$this->canUserReview($data['order_id'], $user->id)) {
            Log::error('ReviewService::createReview - Usuario no puede hacer review', ['user_id' => $user->id, 'order_id' => $data['order_id']]);
            throw new \Exception('No puedes hacer review de esta orden');
        }
        
        // Obtener la orden una sola vez
        $order = Order::find($data['order_id']);
        if (!$order) {
            Log::error('ReviewService::createReview - Orden no encontrada', ['order_id' => $data['order_id']]);
            throw new \Exception('Orden no encontrada');
        }
        
        // Preparar datos base del review
        $reviewData = [
            'profile_id' => $profile->id,
            'rating' => $data['rating'],
            'comentario' => $data['comment'] ?? null
        ];
        
        // Asignar campos reviewable según el tipo
        if ($data['type'] === 'restaurant' && $order->commerce_id) {
            $reviewData['reviewable_type'] = 'App\\Models\\Commerce';
            $reviewData['reviewable_id'] = $order->commerce_id;
        } elseif ($data['type'] === 'delivery' && $order->delivery_agent_id) {
            $reviewData['reviewable_type'] = 'App\\Models\\User';
            $reviewData['reviewable_id'] = $order->delivery_agent_id;
        } else {
            Log::error('ReviewService::createReview - Tipo de review no válido o datos faltantes', [
                'type' => $data['type'],
                'commerce_id' => $order->commerce_id,
                'delivery_agent_id' => $order->delivery_agent_id
            ]);
            throw new \Exception('Tipo de review no válido o datos faltantes');
        }
        
        // Verificar que los campos reviewable estén definidos
        if (!isset($reviewData['reviewable_type']) || !isset($reviewData['reviewable_id'])) {
            Log::error('ReviewService::createReview - Campos reviewable no definidos', ['reviewData' => $reviewData]);
            throw new \Exception('Campos reviewable no definidos');
        }
        
        // Verificar si ya existe un review del mismo usuario para el mismo elemento
        $existingReview = Review::where('profile_id', $profile->id)
                               ->where('reviewable_type', $reviewData['reviewable_type'])
                               ->where('reviewable_id', $reviewData['reviewable_id'])
                               ->first();
        
        if ($existingReview) {
            Log::error('ReviewService::createReview - Review duplicado encontrado', [
                'profile_id' => $profile->id,
                'reviewable_type' => $reviewData['reviewable_type'],
                'reviewable_id' => $reviewData['reviewable_id']
            ]);
            throw new \Exception('Ya has calificado este elemento');
        }
        
        // Crear el review
        $review = Review::create($reviewData);
        
        return $review;
    }

    /**
     * Verificar si un usuario puede calificar.
     *
     * @param int $orderId
     * @param int $userId
     * @return bool
     */
    public function canUserReview($orderId, $userId)
    {
        $profile = Profile::where('user_id', $userId)->first();
        
        if (!$profile) return false;

        // Verificar que el pedido existe, pertenece al usuario y está entregado
        $order = Order::where('id', $orderId)
                     ->where('profile_id', $profile->id)
                     ->where('status', 'delivered')
                     ->first();

        return $order !== null;
    }

    /**
     * Obtener calificaciones de un restaurante.
     *
     * @param int $commerceId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRestaurantReviews($commerceId)
    {
        return Review::where('reviewable_type', 'App\\Models\\Commerce')
                    ->where('reviewable_id', $commerceId)
                    ->with('profile.user')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtener calificaciones de un repartidor.
     *
     * @param int $deliveryAgentId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDeliveryAgentReviews($deliveryAgentId)
    {
        return Review::where('reviewable_type', 'App\\Models\\DeliveryAgent')
                    ->where('reviewable_id', $deliveryAgentId)
                    ->with('profile.user')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Calcular rating promedio de un restaurante.
     *
     * @param int $commerceId
     * @return float
     */
    public function getRestaurantAverageRating($commerceId)
    {
        $reviews = Review::where('reviewable_type', 'App\\Models\\Commerce')
                        ->where('reviewable_id', $commerceId)
                        ->get();

        if ($reviews->isEmpty()) {
            return 0;
        }

        return round($reviews->avg('rating'), 1);
    }

    /**
     * Calcular rating promedio de un repartidor.
     *
     * @param int $deliveryAgentId
     * @return float
     */
    public function getDeliveryAgentAverageRating($deliveryAgentId)
    {
        $reviews = Review::where('reviewable_type', 'App\\Models\\DeliveryAgent')
                        ->where('reviewable_id', $deliveryAgentId)
                        ->get();

        if ($reviews->isEmpty()) {
            return 0;
        }

        return round($reviews->avg('rating'), 1);
    }

    /**
     * Actualizar una calificación existente.
     *
     * @param int $reviewId
     * @param array $data
     * @return array
     */
    public function updateReview($reviewId, $data)
    {
        $user = Auth::user();
        $profile = Profile::where('user_id', $user->id)->first();

        $review = Review::where('id', $reviewId)
                       ->where('profile_id', $profile->id)
                       ->first();

        if (!$review) {
            return ['success' => false, 'message' => 'Calificación no encontrada'];
        }

        $review->update([
            'rating' => $data['rating'],
            'comentario' => $data['comment'] ?? $review->comentario,
        ]);

        return [
            'success' => true,
            'message' => 'Calificación actualizada',
            'review' => $review
        ];
    }

    /**
     * Eliminar una calificación.
     *
     * @param int $reviewId
     * @return array
     */
    public function deleteReview($reviewId)
    {
        $user = Auth::user();
        $profile = Profile::where('user_id', $user->id)->first();

        $review = Review::where('id', $reviewId)
                       ->where('profile_id', $profile->id)
                       ->first();

        if (!$review) {
            return ['success' => false, 'message' => 'Calificación no encontrada'];
        }

        $review->delete();

        return [
            'success' => true,
            'message' => 'Calificación eliminada'
        ];
    }
} 