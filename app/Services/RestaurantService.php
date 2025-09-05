<?php

namespace App\Services;

use App\Models\Commerce;

class RestaurantService
{
    /**
     * Obtener todos los restaurantes (comercios).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllRestaurants()
    {
        return Commerce::with('profile')->get();
    }

    /**
     * Obtener un restaurante específico por ID.
     *
     * @param int $id
     * @return Commerce|null
     */
    public function getRestaurantById($id)
    {
        return Commerce::with(['profile', 'products'])->find($id);
    }
}
