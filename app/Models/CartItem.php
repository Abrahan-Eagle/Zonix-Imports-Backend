<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'product_id',
        'quantity',
        'modality',
        'unit_price',
        'subtotal'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación con el perfil
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calcular subtotal automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($cartItem) {
            $cartItem->subtotal = $cartItem->quantity * $cartItem->unit_price;
        });
    }
}
