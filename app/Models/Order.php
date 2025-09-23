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
        'modality',
        'delivery_type',
        'status',
        'subtotal',
        'discount_total',
        'shipping_total',
        'total',
        'shipping_address_id',
        'billing_address_id',
        'tracking_number',
        'estimated_delivery',
        'receipt_url',
        'notes'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'total' => 'decimal:2',
        'estimated_delivery' => 'datetime'
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
        return $this->hasOneThrough(User::class, Profile::class, 'id', 'id', 'profile_id', 'user_id');
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
                    ->withPivot('quantity', 'unit_price', 'subtotal')
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
     * Relación con pagos
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relación con dirección de envío
     */
    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Relación con dirección de facturación
     */
    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }
}
