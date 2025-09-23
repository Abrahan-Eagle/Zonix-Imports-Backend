<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Profile: almacena información extendida de los usuarios (datos personales, empresa, etc.).
 * Relacionado con User y otras entidades.
 */

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    protected $fillable = [
        'user_id',
        'firstName',
        'middleName',
        'lastName',
        'secondLastName',
        'photo_users',
        'date_of_birth',
        'role',
        'status',
        'is_verified',
        'phone',
        'rif',
        'bank_account'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_verified' => 'boolean',
    ];

    /**
     * Relación con el usuario (1:1)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con comercio (1:1)
     */
    public function commerce()
    {
        return $this->hasOne(Commerce::class);
    }

    /**
     * Relación con órdenes como comprador
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación con direcciones
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Relación con teléfonos
     */
    public function phones()
    {
        return $this->hasMany(Phone::class);
    }

    /**
     * Relación con documentos
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Relación con notificaciones
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Relación con carrito
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Relación con referidos como referidor
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_profile_id');
    }

    /**
     * Relación con movimientos de inventario
     */
    public function inventoryMovements()
    {
        return $this->hasManyThrough(InventoryMovement::class, User::class);
    }
}