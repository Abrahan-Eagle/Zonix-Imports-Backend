<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Services\TrackingService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    protected $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Obtener información de tracking para una orden.
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderTracking($orderId)
    {
        // En producción, obtendrías los datos de la orden desde la base de datos
        $orderData = [
            'id' => $orderId,
            'status' => 'in_delivery',
            'commerce_lat' => 19.4326,
            'commerce_lon' => -99.1332,
            'delivery_lat' => 19.4340,
            'delivery_lon' => -99.1340,
            'customer_lat' => 19.4350,
            'customer_lon' => -99.1350,
        ];

        $tracking = $this->trackingService->getOrderTracking($orderData);

        return response()->json([
            'success' => true,
            'tracking' => $tracking,
        ]);
    }

    /**
     * Actualizar ubicación del delivery.
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDeliveryLocation(Request $request, $orderId)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        // En producción, actualizarías la ubicación en la base de datos
        // y emitirías un evento para notificar al usuario

        return response()->json([
            'success' => true,
            'message' => 'Ubicación actualizada',
            'location' => $validated,
        ]);
    }
} 