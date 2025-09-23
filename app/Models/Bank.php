<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'swift_code',
        'is_active',
    ];

    // Uso MVP: tabla de bancos referenciada por Pago Móvil/Zelle (catálogo)
} 