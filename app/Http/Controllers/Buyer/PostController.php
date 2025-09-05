<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Services\PostService;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar posts desde el lado del comprador.
 *
 * Métodos principales:
 * - index(): Listar todos los posts.
 * - show(): Mostrar detalles de un post específico.
 */
class PostController extends Controller
{
    /**
     * Servicio de posts.
     * @var PostService
     */
    protected $postService;

    /**
     * Inyecta el servicio de posts.
     * @param PostService $postService
     */
    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Listar todos los posts con filtros y búsqueda.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'category' => $request->get('category'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'rating' => $request->get('rating'),
            'sort_by' => $request->get('sort_by', 'name'),
            'sort_order' => $request->get('sort_order', 'asc'),
        ];

        $posts = $this->postService->getPostsWithFilters($filters);
        return response()->json($posts);
    }

    /**
     * Mostrar detalles de un post específico.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $post = $this->postService->getPostById($id);
        if (!$post) {
            return response()->json(['message' => 'Post no encontrado'], 404);
        }
        return response()->json($post);
    }

    /**
     * Agregar/remover post de favoritos.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFavorite($id)
    {
        $result = $this->postService->toggleFavorite($id, auth()->id());
        return response()->json($result);
    }

    /**
     * Obtener posts favoritos del usuario.
     * @return \Illuminate\Http\JsonResponse
     */
    public function favorites()
    {
        $favorites = $this->postService->getUserFavorites(auth()->id());
        return response()->json($favorites);
    }
}
