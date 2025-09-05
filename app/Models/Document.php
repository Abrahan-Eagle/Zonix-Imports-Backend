<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Document extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar másivamente.
     */
    protected $fillable = [
        'profile_id',
        'type',
        'number_ci',
        'RECEIPT_N',
        'sky',
        'rif_url',
        'taxDomicile',
        'commune_register',
        'community_rif',
        'front_image',
        'issued_at',
        'expires_at',
        'approved',
        'status',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'approved' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Relación con el modelo Profile.
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Mutador para la ruta de la imagen frontal.
     */
    protected function frontImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? url("storage/{$value}") : null,
        );
    }

    /**
     * Mutador para la ruta de la imagen trasera.
     */
    protected function backImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? url("storage/{$value}") : null,
        );
    }

}
