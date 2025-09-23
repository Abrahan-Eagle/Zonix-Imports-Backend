<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'title',
        'body',
        'type',
        'is_read',
        'priority',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    /**
     * Relación con el perfil
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope para notificaciones leídas
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope para notificaciones por prioridad
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}
