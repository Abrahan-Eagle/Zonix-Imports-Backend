<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'commerce_id',
        'delivery_type',
        'status',
        'total',
        'receipt_url',
        'notes',
        'payment_proof',
        'payment_method',
        'reference_number',
        'payment_validated_at',
        'cancellation_reason',
        'delivery_address'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'payment_validated_at' => 'datetime'
    ];

    /**
     * Relación con el perfil
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Relación con el usuario a través del perfil
     */
    public function user()
    {
        return $this->hasOneThrough(User::class, Profile::class);
    }

    /**
     * Relación con el comercio
     */
    public function commerce()
    {
        return $this->belongsTo(Commerce::class);
    }

    /**
     * Relación con los productos a través de order_items
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot('quantity', 'unit_price')
                    ->withTimestamps();
    }

    /**
     * Relación con los items de orden
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relación con la entrega de la orden
     */
    public function orderDelivery()
    {
        return $this->hasOne(OrderDelivery::class);
    }

    public function delivery()
    {
        return $this->hasOne(\App\Models\OrderDelivery::class);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\OrderItem::class);
    }
}
