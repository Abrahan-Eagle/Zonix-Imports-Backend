<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'referrer_profile_id',
        'percentage',
        'commission_earned',
        'link',
        'active',
        'expires_at'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'commission_earned' => 'decimal:2',
        'active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con el perfil referidor
     */
    public function referrerProfile()
    {
        return $this->belongsTo(Profile::class, 'referrer_profile_id');
    }

    /**
     * Generar link único
     */
    public static function generateUniqueLink()
    {
        do {
            $link = 'ref_' . bin2hex(random_bytes(16));
        } while (static::where('link', $link)->exists());

        return $link;
    }
}
