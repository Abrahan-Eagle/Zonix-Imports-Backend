<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessedWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_id'
    ];

    /**
     * Verificar si un evento ya fue procesado
     */
    public static function isProcessed($provider, $eventId)
    {
        return static::where('provider', $provider)
                    ->where('event_id', $eventId)
                    ->exists();
    }

    /**
     * Marcar un evento como procesado
     */
    public static function markAsProcessed($provider, $eventId)
    {
        return static::create([
            'provider' => $provider,
            'event_id' => $eventId
        ]);
    }
}
