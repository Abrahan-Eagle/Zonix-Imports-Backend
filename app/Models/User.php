<?php

namespace App\Models;

use App\Models\Commerce;
use App\Models\DeliveryAgent;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\PostLike;
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
        'google_id',        // ID único proporcionado por Google
        'given_name',       // Nombre de pila
        'family_name',      // Apellido
        'profile_pic',      // URL de la imagen de perfil de Google
        'AccessToken',
        'role',  // Rol del usuario (admin, cliente, etc.
        'completed_onboarding'
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
     * Relación para obtener los roles del usuario.
     * Si quieres manejar varios roles por usuario, puedes usar una tabla pivot.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->role === $role || $this->roles->contains('name', $role);
        }
        return $this->roles->contains($role);
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
     * Obtener todos los roles asociados al usuario
     */
    public function getAllRoles()
    {
        $roles = collect([$this->role]);
        if ($this->roles->isNotEmpty()) {
            $roles = $roles->merge($this->roles->pluck('name'));
        }
        return $roles->unique()->values();
    }

    // Relación con Profile
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function commerce()
    {
        return $this->hasOne(Commerce::class);
    }

    public function deliveryCompany()
    {
        return $this->hasOne(DeliveryCompany::class);
    }

    public function deliveryAgent()
    {
        return $this->hasOne(DeliveryAgent::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function postLikes()
    {
        return $this->hasMany(PostLike::class);
    }

    /**
     * Relación con órdenes como comprador
     */
    public function buyerOrders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    /**
     * Relación con órdenes como repartidor
     */
    public function deliveryOrders()
    {
        return $this->hasManyThrough(
            Order::class,
            DeliveryAgent::class,
            'profile_id', // Clave foránea en delivery_agents
            'id', // Clave foránea en orders
            'id', // Clave local en users
            'id' // Clave local en delivery_agents
        )->whereHas('orderDelivery', function($query) {
            $query->where('agent_id', $this->profile->deliveryAgent->id ?? 0);
        });
    }

    public function paymentMethods()
    {
        return $this->morphMany(PaymentMethod::class, 'payable');
    }
}
