<?php

namespace App\Models;

use App\Models\Commerce;
use App\Models\Order;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Modelo User: representa a los usuarios de la app (clientes, comercios, repartidores, admin).
     * Incluye relaciones con comercios, órdenes, likes, etc.
     */

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'remember_token'
    ];

    /**
     * Atributos que deberían ocultarse para arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que deberían ser tratados como fechas.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Verificar si el usuario tiene un rol específico a través del perfil
     */
    public function hasRole($role)
    {
        // Usar solo users.role
        $effectiveRole = $this->attributes['role'] ?? null;
        return $effectiveRole === $role;
    }

    /**
     * Verificar si el usuario tiene cualquiera de los roles especificados
     */
    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }
        
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener el rol del usuario a través del perfil
     */
    public function getRole()
    {
        // Usar solo users.role; default 'buyer'
        return !empty($this->attributes['role']) ? $this->attributes['role'] : 'buyer';
    }

    // Relación con Profile
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Relación con comercio a través del perfil (1:1)
     */
    public function commerce()
    {
        return $this->hasOneThrough(Commerce::class, Profile::class);
    }

    /**
     * Relación con órdenes como comprador a través del perfil
     */
    public function orders()
    {
        return $this->hasManyThrough(Order::class, Profile::class);
    }

    /**
     * Relación con teléfonos a través del perfil
     */
    public function phones()
    {
        return $this->hasManyThrough(Phone::class, Profile::class);
    }

    /**
     * Relación con direcciones a través del perfil
     */
    public function addresses()
    {
        return $this->hasManyThrough(Address::class, Profile::class);
    }

    /**
     * Relación con documentos a través del perfil
     */
    public function documents()
    {
        return $this->hasManyThrough(Document::class, Profile::class);
    }

    /**
     * Relación con notificaciones a través del perfil
     */
    public function notifications()
    {
        return $this->hasManyThrough(Notification::class, Profile::class);
    }

    /**
     * Relación con carrito a través del perfil
     */
    public function cartItems()
    {
        return $this->hasManyThrough(CartItem::class, Profile::class);
    }
}
