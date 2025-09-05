<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $table = 'states';

    protected $fillable = ['code', 'name', 'country_id'];

    // Un estado tiene muchas ciudades
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    // Un estado pertenece a un país
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
