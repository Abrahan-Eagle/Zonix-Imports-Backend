<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Commerce;
use Illuminate\Support\Collection;

/**
 * Servicio para la gestión de productos.
 * Permite obtener, listar y buscar productos de comercios.
 */
class ProductService
{
    /**
     * Obtener un producto por su ID.
     *
     * @param int $id
     * @return Product|null
     */
    public function getProductById($id)
    {
        return Product::find($id);
    }

    /**
     * Listar todos los productos de un comercio.
     *
     * @param int $commerceId
     * @return Collection<Product>
     */
    public function getProductsByCommerce($commerceId)
    {
        return Product::where('commerce_id', $commerceId)->get();
    }

    /**
     * Buscar productos disponibles (opcionalmente por nombre).
     * 
     * @param string|null $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchAvailableProducts($search = null)
    {
        $query = Product::where('available', true);
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }
        return $query->get();
    }
}
