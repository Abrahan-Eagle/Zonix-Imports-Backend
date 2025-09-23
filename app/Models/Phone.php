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
        'operator_code',
        'country_code',
        'number',
        'is_primary',
        'is_active'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con el perfil
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }




    /**
     * Scope para obtener solo teléfonos principales
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope para obtener solo teléfonos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Asegura que solo un teléfono sea principal por perfil
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($phone) {
            if ($phone->is_primary) {
                // Desmarcar otros teléfonos principales del mismo perfil
                Phone::where('profile_id', $phone->profile_id)
                    ->where('id', '!=', $phone->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
