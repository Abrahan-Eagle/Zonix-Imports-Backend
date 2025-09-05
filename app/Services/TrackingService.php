<?php

namespace App\Services;

class TrackingService
{
    /**
     * Calcular distancia entre dos puntos usando la fórmula de Haversine.
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distancia en kilómetros
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la Tierra en kilómetros

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calcular tiempo estimado de entrega.
     *
     * @param float $distance Distancia en kilómetros
     * @param string $vehicleType Tipo de vehículo (bike, car, motorcycle)
     * @return int Tiempo estimado en minutos
     */
    public function calculateEstimatedTime($distance, $vehicleType = 'bike')
    {
        $averageSpeeds = [
            'bike' => 15,      // 15 km/h en bicicleta
            'motorcycle' => 25, // 25 km/h en moto
            'car' => 30,        // 30 km/h en ciudad
        ];

        $speed = $averageSpeeds[$vehicleType] ?? 15;
        $timeInHours = $distance / $speed;
        
        // Agregar tiempo extra para tráfico, semáforos, etc.
        $timeInHours *= 1.3;
        
        return (int) round($timeInHours * 60); // Convertir a minutos
    }

    /**
     * Generar coordenadas de ruta simulada.
     *
     * @param float $startLat
     * @param float $startLon
     * @param float $endLat
     * @param float $endLon
     * @param int $steps Número de puntos intermedios
     * @return array Array de coordenadas
     */
    public function generateRouteCoordinates($startLat, $startLon, $endLat, $endLon, $steps = 10)
    {
        $coordinates = [];
        
        for ($i = 0; $i <= $steps; $i++) {
            $ratio = $i / $steps;
            
            $lat = $startLat + ($endLat - $startLat) * $ratio;
            $lon = $startLon + ($endLon - $startLon) * $ratio;
            
            // Agregar pequeñas variaciones para simular ruta real
            $lat += (rand(-100, 100) / 10000); // ±0.01 grados
            $lon += (rand(-100, 100) / 10000);
            
            $coordinates[] = [
                'lat' => round($lat, 6),
                'lng' => round($lon, 6),
                'timestamp' => time() + ($i * 60), // Simular progreso en el tiempo
            ];
        }
        
        return $coordinates;
    }

    /**
     * Obtener información completa de tracking para una orden.
     *
     * @param array $orderData
     * @return array
     */
    public function getOrderTracking($orderData)
    {
        // Coordenadas simuladas (en producción vendrían de la base de datos)
        $commerceLocation = [
            'lat' => $orderData['commerce_lat'] ?? 19.4326,
            'lng' => $orderData['commerce_lon'] ?? -99.1332,
        ];
        
        $deliveryLocation = [
            'lat' => $orderData['delivery_lat'] ?? 19.4326,
            'lng' => $orderData['delivery_lon'] ?? -99.1332,
        ];
        
        $customerLocation = [
            'lat' => $orderData['customer_lat'] ?? 19.4326,
            'lng' => $orderData['customer_lon'] ?? -99.1332,
        ];

        // Calcular distancias
        $commerceToDelivery = $this->calculateDistance(
            $commerceLocation['lat'], $commerceLocation['lng'],
            $deliveryLocation['lat'], $deliveryLocation['lng']
        );
        
        $deliveryToCustomer = $this->calculateDistance(
            $deliveryLocation['lat'], $deliveryLocation['lng'],
            $customerLocation['lat'], $customerLocation['lng']
        );

        // Calcular tiempos estimados
        $timeToDelivery = $this->calculateEstimatedTime($commerceToDelivery);
        $timeToCustomer = $this->calculateEstimatedTime($deliveryToCustomer);

        // Generar rutas
        $routeToDelivery = $this->generateRouteCoordinates(
            $commerceLocation['lat'], $commerceLocation['lng'],
            $deliveryLocation['lat'], $deliveryLocation['lng']
        );
        
        $routeToCustomer = $this->generateRouteCoordinates(
            $deliveryLocation['lat'], $deliveryLocation['lng'],
            $customerLocation['lat'], $customerLocation['lng']
        );

        return [
            'commerce_location' => $commerceLocation,
            'delivery_location' => $deliveryLocation,
            'customer_location' => $customerLocation,
            'distances' => [
                'commerce_to_delivery' => round($commerceToDelivery, 2),
                'delivery_to_customer' => round($deliveryToCustomer, 2),
                'total' => round($commerceToDelivery + $deliveryToCustomer, 2),
            ],
            'estimated_times' => [
                'to_delivery' => $timeToDelivery,
                'to_customer' => $timeToCustomer,
                'total' => $timeToDelivery + $timeToCustomer,
            ],
            'routes' => [
                'to_delivery' => $routeToDelivery,
                'to_customer' => $routeToCustomer,
            ],
            'current_status' => $orderData['status'] ?? 'pending',
            'last_updated' => now()->toISOString(),
        ];
    }
} 