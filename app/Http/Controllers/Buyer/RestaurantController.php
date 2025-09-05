<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Services\RestaurantService;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar restaurantes (comercios).
 *
 * Métodos principales:
 * - index(): Listar todos los restaurantes.
 * - show(): Mostrar detalles de un restaurante específico.
 */
class RestaurantController extends Controller
{
    /**
     * Servicio de restaurantes.
     * @var RestaurantService
     */
    protected $restaurantService;

    /**
     * Inyecta el servicio de restaurantes.
     * @param RestaurantService $restaurantService
     */
    public function __construct(RestaurantService $restaurantService)
    {
        $this->restaurantService = $restaurantService;
    }

    /**
     * Listar todos los restaurantes.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $restaurants = $this->restaurantService->getAllRestaurants();
        return response()->json($restaurants);
    }

    /**
     * Mostrar detalles de un restaurante específico.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $restaurant = $this->restaurantService->getRestaurantById($id);
        if (!$restaurant) {
            return response()->json(['message' => 'Restaurante no encontrado'], 404);
        }
        return response()->json($restaurant);
    }
}
