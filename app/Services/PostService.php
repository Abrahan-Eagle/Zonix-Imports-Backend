<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Support\Facades\DB;

class PostService
{
    /**
     * Obtener todos los posts.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllPosts()
    {
        return Post::latest()->get();
    }

    /**
     * Obtener posts con filtros y búsqueda.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPostsWithFilters($filters)
    {
        $query = Post::query();

        // Búsqueda por nombre o descripción
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        // Filtro por categoría
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filtro por precio mínimo
        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        // Filtro por precio máximo
        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Filtro por rating
        if (!empty($filters['rating'])) {
            $query->where('rating', '>=', $filters['rating']);
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    /**
     * Obtener un post por su ID.
     *
     * @param int $id
     * @return Post|null
     */
    public function getPostById($id)
    {
        return Post::find($id);
    }

    /**
     * Agregar/remover post de favoritos.
     *
     * @param int $postId
     * @param int $userId
     * @return array
     */
    public function toggleFavorite($postId, $userId)
    {
        $profile = \App\Models\Profile::where('user_id', $userId)->first();
        
        if (!$profile) {
            return ['message' => 'Perfil no encontrado', 'is_favorite' => false];
        }
        
        $existing = PostLike::where('post_id', $postId)
                           ->where('profile_id', $profile->id)
                           ->first();

        if ($existing) {
            $existing->delete();
            return ['message' => 'Removido de favoritos', 'is_favorite' => false];
        } else {
            PostLike::create([
                'post_id' => $postId,
                'profile_id' => $profile->id,
            ]);
            return ['message' => 'Agregado a favoritos', 'is_favorite' => true];
        }
    }

    /**
     * Obtener posts favoritos del usuario.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserFavorites($userId)
    {
        $profile = \App\Models\Profile::where('user_id', $userId)->first();
        
        if (!$profile) {
            return collect();
        }
        
        return Post::whereHas('likes', function($query) use ($profile) {
            $query->where('profile_id', $profile->id);
        })->get();
    }
}
