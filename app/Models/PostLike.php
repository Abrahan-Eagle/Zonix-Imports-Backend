<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo PostLike: gestiona los likes de los usuarios a los posts.
 * Relacionado con Profile, User y Post.
 */
class PostLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'post_id',
        // 'user_id',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function user()
    {
        return $this->profile->user();
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
