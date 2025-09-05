<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Actualizar ubicación del usuario
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        $user = Auth::user();
        $profile = $user->profile;

        // TODO: Implementar almacenamiento de ubicación en base de datos
        // Por ahora solo loggeamos la ubicación
        Log::info('User location updated', [
            'user_id' => $user->id,
            'profile_id' => $profile->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'timestamp' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Obtener lugares cercanos (restaurantes, etc.)
     */
    public function getNearbyPlaces(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:50', // km
            'type' => 'nullable|string|in:restaurant,store,gas_station,pharmacy',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->get('radius', 5); // 5km por defecto
        $type = $request->get('type', 'restaurant');

        // TODO: Implementar búsqueda real en base de datos con cálculo de distancia
        // Por ahora devolvemos datos mock
        $nearbyPlaces = [
            [
                'id' => 1,
                'name' => 'Pizza Express',
                'type' => 'restaurant',
                'latitude' => $latitude + 0.001,
                'longitude' => $longitude + 0.001,
                'distance' => 0.1, // km
                'rating' => 4.5,
                'address' => 'Av. Principal 123',
                'phone' => '+51 123 456 789',
            ],
            [
                'id' => 2,
                'name' => 'Burger House',
                'type' => 'restaurant',
                'latitude' => $latitude - 0.002,
                'longitude' => $longitude + 0.003,
                'distance' => 0.3, // km
                'rating' => 4.2,
                'address' => 'Calle Comercial 456',
                'phone' => '+51 987 654 321',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $nearbyPlaces,
            'meta' => [
                'center' => ['lat' => $latitude, 'lng' => $longitude],
                'radius' => $radius,
                'count' => count($nearbyPlaces),
            ]
        ]);
    }

    /**
     * Calcular ruta entre dos puntos
     */
    public function calculateRoute(Request $request)
    {
        $request->validate([
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lng' => 'required|numeric|between:-180,180',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lng' => 'required|numeric|between:-180,180',
            'mode' => 'nullable|string|in:driving,walking,bicycling,transit',
        ]);

        $originLat = $request->origin_lat;
        $originLng = $request->origin_lng;
        $destLat = $request->destination_lat;
        $destLng = $request->destination_lng;
        $mode = $request->get('mode', 'driving');

        // TODO: Implementar cálculo real de ruta con Google Maps API
        // Por ahora devolvemos datos mock
        $route = [
            'origin' => ['lat' => $originLat, 'lng' => $originLng],
            'destination' => ['lat' => $destLat, 'lng' => $destLng],
            'mode' => $mode,
            'distance' => 2.5, // km
            'duration' => 8, // minutos
            'polyline' => [
                ['lat' => $originLat, 'lng' => $originLng],
                ['lat' => ($originLat + $destLat) / 2, 'lng' => ($originLng + $destLng) / 2],
                ['lat' => $destLat, 'lng' => $destLng],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $route,
        ]);
    }

    /**
     * Geocodificación: convertir dirección a coordenadas
     */
    public function getCoordinatesFromAddress(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:500',
        ]);

        $address = $request->address;

        // TODO: Implementar geocodificación real con Google Maps API
        // Por ahora devolvemos coordenadas mock para Lima, Perú
        $coordinates = [
            'latitude' => -12.0464,
            'longitude' => -77.0428,
            'formatted_address' => 'Lima, Perú',
            'accuracy' => 'APPROXIMATE',
        ];

        return response()->json([
            'success' => true,
            'data' => $coordinates,
        ]);
    }

    /**
     * Obtener zonas de entrega
     */
    public function getDeliveryZones()
    {
        // TODO: Implementar obtención real de zonas de entrega
        // Por ahora devolvemos zonas mock
        $zones = [
            [
                'id' => 1,
                'name' => 'Zona Centro',
                'center' => ['lat' => -12.0464, 'lng' => -77.0428],
                'radius' => 5, // km
                'delivery_fee' => 3.00,
                'delivery_time' => 30, // minutos
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Zona Norte',
                'center' => ['lat' => -12.0564, 'lng' => -77.0328],
                'radius' => 7, // km
                'delivery_fee' => 5.00,
                'delivery_time' => 45, // minutos
                'is_active' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }
} 