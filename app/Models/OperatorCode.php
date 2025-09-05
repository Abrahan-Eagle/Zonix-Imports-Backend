<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorCode extends Model
{
    use HasFactory;


    protected $table = 'operator_codes';

    // Ajuste en la clave foránea: 'user_id'
    protected $fillable = ['id', 'code', 'name'];

    // Aquí puede ser algo simple si el operador tiene una relación simple con Phone
    public function phones()
    {
        return $this->hasMany(Phone::class);
    }

}
