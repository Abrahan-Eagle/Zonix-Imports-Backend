<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $trackingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trackingService = new TrackingService();
    }

    public function test_calculate_distance()
    {
        // Coordenadas de ejemplo (Ciudad de México)
        $lat1 = 19.4326;
        $lon1 = -99.1332;
        $lat2 = 19.4340;
        $lon2 = -99.1340;

        $distance = $this->trackingService->calculateDistance($lat1, $lon1, $lat2, $lon2);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(1, $distance); // Debe ser menos de 1 km para estas coordenadas cercanas
    }

    public function test_calculate_estimated_time()
    {
        $distance = 5.0; // 5 km

        $timeBike = $this->trackingService->calculateEstimatedTime($distance, 'bike');
        $timeMotorcycle = $this->trackingService->calculateEstimatedTime($distance, 'motorcycle');
        $timeCar = $this->trackingService->calculateEstimatedTime($distance, 'car');

        $this->assertIsInt($timeBike);
        $this->assertIsInt($timeMotorcycle);
        $this->assertIsInt($timeCar);

        // La moto debe ser más rápida que la bicicleta
        $this->assertLessThan($timeBike, $timeMotorcycle);
        // El carro debe ser más rápido que la moto
        $this->assertLessThan($timeMotorcycle, $timeCar);
    }

    public function test_generate_route_coordinates()
    {
        $startLat = 19.4326;
        $startLon = -99.1332;
        $endLat = 19.4340;
        $endLon = -99.1340;
        $steps = 5;

        $coordinates = $this->trackingService->generateRouteCoordinates($startLat, $startLon, $endLat, $endLon, $steps);

        $this->assertCount($steps + 1, $coordinates); // +1 porque incluye el punto final

        foreach ($coordinates as $coord) {
            $this->assertArrayHasKey('lat', $coord);
            $this->assertArrayHasKey('lng', $coord);
            $this->assertArrayHasKey('timestamp', $coord);
            $this->assertIsFloat($coord['lat']);
            $this->assertIsFloat($coord['lng']);
            $this->assertIsInt($coord['timestamp']);
        }
    }

    public function test_get_order_tracking()
    {
        $orderData = [
            'id' => 1,
            'status' => 'in_delivery',
            'commerce_lat' => 19.4326,
            'commerce_lon' => -99.1332,
            'delivery_lat' => 19.4340,
            'delivery_lon' => -99.1340,
            'customer_lat' => 19.4350,
            'customer_lon' => -99.1350,
        ];

        $tracking = $this->trackingService->getOrderTracking($orderData);

        $this->assertArrayHasKey('commerce_location', $tracking);
        $this->assertArrayHasKey('delivery_location', $tracking);
        $this->assertArrayHasKey('customer_location', $tracking);
        $this->assertArrayHasKey('distances', $tracking);
        $this->assertArrayHasKey('estimated_times', $tracking);
        $this->assertArrayHasKey('routes', $tracking);
        $this->assertArrayHasKey('current_status', $tracking);
        $this->assertArrayHasKey('last_updated', $tracking);

        // Verificar que las distancias son números positivos
        $this->assertGreaterThan(0, $tracking['distances']['commerce_to_delivery']);
        $this->assertGreaterThan(0, $tracking['distances']['delivery_to_customer']);
        $this->assertGreaterThan(0, $tracking['distances']['total']);

        // Verificar que los tiempos son números positivos
        $this->assertGreaterThan(0, $tracking['estimated_times']['to_delivery']);
        $this->assertGreaterThan(0, $tracking['estimated_times']['to_customer']);
        $this->assertGreaterThan(0, $tracking['estimated_times']['total']);
    }
} 