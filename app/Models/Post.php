<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Post: publicaciones de comercios (promos, novedades, etc.).
 * Relacionado con Commerce y PostLike.
 */
class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'commerce_id', 'tipo', 'media_url', 'description', 'name', 'price'
    ];

    public function commerce()
    {
        return $this->belongsTo(Commerce::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }
}
