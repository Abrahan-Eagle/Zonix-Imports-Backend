<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Services\PostLikeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador para gestionar likes de posts desde el lado del comprador.
 *
 * Métodos principales:
 * - like(): Dar like a un post.
 * - unlike(): Quitar like a un post.
 */
class PostLikeController extends Controller
{
    /**
     * Servicio de likes de posts.
     * @var PostLikeService
     */
    protected $postLikeService;

    /**
     * Inyecta el servicio de likes de posts.
     * @param PostLikeService $postLikeService
     */
    public function __construct(PostLikeService $postLikeService)
    {
        $this->postLikeService = $postLikeService;
    }

    /**
     * Dar like a un post.
     * @param int $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function like($postId)
    {
        $this->postLikeService->like($postId);
        return response()->json(['message' => 'Post liked']);
    }

    /**
     * Quitar like a un post.
     * @param int $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlike($postId)
    {
        $this->postLikeService->unlike($postId);
        return response()->json(['message' => 'Post unliked']);
    }
}
