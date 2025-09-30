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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function searchAvailableProducts($search = null)
    {
        $query = Product::with(['commerce', 'category', 'images'])
                       ->where('available', true)
                       ->where('stock', '>', 0);
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
        }
        
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Obtener productos destacados.
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedProducts($limit = 10)
    {
        return Product::with(['commerce', 'category', 'images'])
                     ->where('available', true)
                     ->where('stock', '>', 0)
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Buscar productos con paginación.
     *
     * @param string $query
     * @param int $page
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchProducts($query, $page = 1, $perPage = 20)
    {
        return Product::with(['commerce', 'category', 'images'])
                     ->where('available', true)
                     ->where('stock', '>', 0)
                     ->where(function($q) use ($query) {
                         $q->where('name', 'like', "%$query%")
                           ->orWhere('description', 'like', "%$query%");
                     })
                     ->orderBy('created_at', 'desc')
                     ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Obtener productos relacionados.
     *
     * @param int $productId
     * @param int $limit
     * @return Collection
     */
    public function getRelatedProducts($productId, $limit = 5)
    {
        $product = Product::find($productId);
        if (!$product) {
            return collect();
        }

        return Product::with(['commerce', 'category', 'images'])
                     ->where('available', true)
                     ->where('stock', '>', 0)
                     ->where('id', '!=', $productId)
                     ->where('category_id', $product->category_id)
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obtener marcas populares.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularBrands($limit = 10)
    {
        // Como no hay campo brand, retornamos marcas ficticias basadas en comercios
        return Commerce::withCount('products')
                      ->having('products_count', '>', 0)
                      ->orderBy('products_count', 'desc')
                      ->limit($limit)
                      ->pluck('name');
    }
}
