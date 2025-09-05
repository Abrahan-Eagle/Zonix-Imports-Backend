<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Profile: almacena información extendida de los usuarios (datos personales, empresa, etc.).
 * Relacionado con User y otras entidades.
 */

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    // Definir los campos que se pueden llenar de forma masiva
       protected $fillable = [
        'user_id',
        'firstName',
        'middleName',
        'lastName',
        'secondLastName',
        'photo_users',
        'date_of_birth',
        'maritalStatus',
        'sex',
        'status',
        'phone',
        'address',
        'business_name',
        'business_type',
        'tax_id',
        'vehicle_type',
        'license_number'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'status' => 'string',
    ];


 public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relaciones con otros modelos
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

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }





// Relación uno a muchos con el modelo Address
    public function addresses() {
        return $this->hasMany(Address::class);
    }



}
