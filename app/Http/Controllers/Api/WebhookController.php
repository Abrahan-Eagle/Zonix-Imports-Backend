<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcessedWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function handle(Request $request, string $provider): JsonResponse
    {
        $eventId = $request->header('X-Event-Id') ?? $request->input('event_id');
        if (!$eventId) {
            return response()->json(['message' => 'Missing event id'], 400);
        }

        $already = ProcessedWebhookEvent::where('provider', $provider)->where('event_id', $eventId)->first();
        if ($already) {
            return response()->json(['message' => 'Event already processed'], 200);
        }

        // TODO: aquí se enruta a servicios Stripe/PayPal/Binance según $provider

        ProcessedWebhookEvent::create([
            'provider' => $provider,
            'event_id' => $eventId,
        ]);

        return response()->json(['success' => true], 200);
    }
}


