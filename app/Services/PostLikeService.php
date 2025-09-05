<?php

namespace App\Services;

use App\Models\PostLike;
use Illuminate\Support\Facades\Auth;

class PostLikeService
{
    /**
     * Dar like a un post.
     *
     * @param int $postId
     * @return void
     */
    public function like($postId)
    {
        PostLike::firstOrCreate([
            'user_id' => Auth::id(),
            'post_id' => $postId,
        ]);
    }

    /**
     * Quitar like a un post.
     *
     * @param int $postId
     * @return void
     */
    public function unlike($postId)
    {
        PostLike::where('user_id', Auth::id())
            ->where('post_id', $postId)
            ->delete();
    }
}
