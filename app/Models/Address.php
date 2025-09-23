<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'city_id',
        'street',
        'house_number',
        'address_line_2',
        'reference',
        'postal_code',
        'latitude',
        'longitude',
        'status',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Relación con el perfil
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Relación con la ciudad
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Relación con órdenes como dirección de envío
     */
    public function shippingOrders()
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    /**
     * Relación con órdenes como dirección de facturación
     */
    public function billingOrders()
    {
        return $this->hasMany(Order::class, 'billing_address_id');
    }
}
