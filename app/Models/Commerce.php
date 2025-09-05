<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commerce extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'business_name',
        'image',
        'address',
        'phone',
        'open',
        'schedule'
    ];

    protected $casts = [
        'open' => 'boolean',
        'schedule' => 'array'
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
     * Relación con los productos
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Relación con las órdenes
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación con métodos de pago
     */
    public function paymentMethods()
    {
        return $this->morphMany(PaymentMethod::class, 'payable');
    }
}
