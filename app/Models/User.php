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

    // Relación con Profile
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

   
}
