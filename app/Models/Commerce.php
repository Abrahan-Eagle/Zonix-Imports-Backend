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
        'business_type',
        'image',
        'phone',
        'rif',
        'bank_account',
        'is_verified',
        'open',
        'payment_methods',
        'schedule'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'open' => 'boolean',
        'payment_methods' => 'array',
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
        return $this->hasOneThrough(User::class, Profile::class, 'id', 'id', 'profile_id', 'user_id');
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
     * Relación con pagos
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Order::class, 'commerce_id', 'order_id');
    }

    /**
     * Scope para tiendas verificadas
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope para tiendas abiertas
     */
    public function scopeOpen($query)
    {
        return $query->where('open', true);
    }

    /**
     * Scope para búsqueda por nombre
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('business_name', 'LIKE', "%{$search}%");
    }
}
