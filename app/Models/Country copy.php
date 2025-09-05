<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = ['name', 'short_code'];

    // Un país tiene muchos estados
    public function states()
    {
        return $this->hasMany(State::class);
    }
}
