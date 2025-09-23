<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'status',
        'reference',
        'external_id',
        'currency',
        'processed_at',
        'receipt_url',
        'failure_reason',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relación con la orden
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
