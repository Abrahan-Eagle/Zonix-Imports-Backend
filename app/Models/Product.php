<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'commerce_id',
        'name',
        'description',
        'price',
        'image',
        'available'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'available' => 'boolean'
    ];

    /**
     * Relación con el comercio
     */
    public function commerce()
    {
        return $this->belongsTo(Commerce::class);
    }

    /**
     * Relación con los items de orden
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relación con las órdenes a través de order_items
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items');
    }
}
