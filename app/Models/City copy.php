<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = ['state_id', 'name', 'county', 'latitude', 'longitude'];

    // Una ciudad pertenece a un estado
    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
