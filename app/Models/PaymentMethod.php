<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_type',
        'payable_id',
        'bank_id',
        'type',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'cardholder_name',
        'account_number',
        'phone',
        'email',
        'reference_info',
        'owner_name',
        'owner_id',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'reference_info' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'exp_month' => 'integer',
        'exp_year' => 'integer',
    ];

    /**
     * Relación polimórfica - puede pertenecer a User, Commerce, o DeliveryAgent
     */
    public function payable()
    {
        return $this->morphTo();
    }

    /**
     * Relación con el banco
     */
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Scope para métodos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para métodos por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para método por defecto
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Verificar si es método por defecto
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * Verificar si está activo
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Obtener información de la tarjeta (últimos 4 dígitos)
     */
    public function getLastFourDigits()
    {
        return $this->last4;
    }

    /**
     * Obtener información de expiración
     */
    public function getExpirationInfo()
    {
        if ($this->exp_month && $this->exp_year) {
            return sprintf('%02d/%d', $this->exp_month, $this->exp_year);
        }
        return null;
    }

    /**
     * Verificar si la tarjeta está expirada
     */
    public function isExpired()
    {
        if (!$this->exp_month || !$this->exp_year) {
            return false;
        }
        
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        
        return $this->exp_year < $currentYear || 
               ($this->exp_year == $currentYear && $this->exp_month < $currentMonth);
    }
} 