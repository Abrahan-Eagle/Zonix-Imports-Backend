<?php

namespace App\Http\Controllers;

use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Crear una nueva calificación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reviewable_id' => 'required|integer',
            'reviewable_type' => 'required|string|in:App\Models\Commerce,App\Models\Product',
            'rating' => 'required|integer|between:1,5',
            'comentario' => 'nullable|string|max:500',
        ]);

        $result = $this->reviewService->createReview($validated);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Obtener calificaciones de un elemento.
     *
     * @param int $reviewableId
     * @param string $reviewableType
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($reviewableId, $reviewableType)
    {
        $reviews = $this->reviewService->getReviews($reviewableId, $reviewableType);
        $averageRating = $this->reviewService->getAverageRating($reviewableId, $reviewableType);

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
            'average_rating' => $averageRating,
            'total_reviews' => $reviews->count(),
        ]);
    }

    /**
     * Actualizar una calificación.
     *
     * @param Request $request
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $reviewId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comentario' => 'nullable|string|max:500',
        ]);

        $result = $this->reviewService->updateReview($reviewId, $validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Eliminar una calificación.
     *
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($reviewId)
    {
        $result = $this->reviewService->deleteReview($reviewId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Verificar si un usuario puede calificar.
     *
     * @param int $reviewableId
     * @param string $reviewableType
     * @return \Illuminate\Http\JsonResponse
     */
    public function canReview($reviewableId, $reviewableType)
    {
        $user = auth()->user();
        $canReview = $this->reviewService->canUserReview($user->id, $reviewableId, $reviewableType);

        return response()->json([
            'success' => true,
            'can_review' => $canReview,
        ]);
    }
} 