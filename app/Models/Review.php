<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comentario'
    ];

    protected $casts = [
        'rating' => 'integer'
    ];

    public function reviewable()
    {
        return $this->morphTo();
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
