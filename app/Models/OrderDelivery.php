<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo OrderDelivery: gestiona la relación entre órdenes y agentes de delivery.
 * Incluye estado y costo del envío.
 */
class OrderDelivery extends Model
{
    use HasFactory;

    protected $table = 'order_delivery'; // Especifica el nombre correcto de la tabla

    protected $fillable = [
        'order_id',
        'agent_id',
        'status',
        'costo_envio',
        'notas'
    ];

    protected $casts = [
        'costo_envio' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function agent()
    {
        return $this->belongsTo(DeliveryAgent::class, 'agent_id');
    }
}
