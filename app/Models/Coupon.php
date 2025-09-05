<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'minimum_order',
        'maximum_discount',
        'usage_limit',
        'start_date',
        'end_date',
        'terms_conditions',
        'is_public',
        'assigned_to_profile_id',
        'is_active'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_order' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_public' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function assignedToProfile()
    {
        return $this->belongsTo(Profile::class, 'assigned_to_profile_id');
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }
} 