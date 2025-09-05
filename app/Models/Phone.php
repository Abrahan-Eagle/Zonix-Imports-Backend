<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phone extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'profile_id',
        'operator_code_id',
        'number',
        'is_primary',
        'status',
    ];

    /**
     * Definición de las relaciones.
     */

    // Relación con Profile: Un teléfono pertenece a un perfil.
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    // Relación con OperatorCode: Un teléfono pertenece a un código de operador.
    // public function operatorCode()
    // {
    //     return $this->belongsTo(OperatorCode::class, 'operator_code_id');
    // }

    // public function operator_code()
    // {
    //     return $this->belongsTo(OperatorCode::class);
    // }

    public function operatorCode()
    {
        return $this->belongsTo(OperatorCode::class, 'operator_code_id');
    }




    /**
     * Scope para obtener solo teléfonos principales.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Asegura que solo un teléfono sea principal por perfil.
     */
    public static function boot()
    {
        parent::boot();

        // Escucha el evento 'creating' y 'updating' para manejar el teléfono principal.
        static::saving(function ($phone) {
            if ($phone->is_primary) {
                // Desmarcar otros teléfonos principales del mismo perfil.
                Phone::where('profile_id', $phone->profile_id)
                    ->where('id', '!=', $phone->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
