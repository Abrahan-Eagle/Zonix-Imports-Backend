<?php

namespace App\Services;

use App\Models\DeliveryAgent;
use App\Models\Order;

class DeliveryAssignmentService
{
    protected $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Asignar automáticamente un delivery a una orden.
     *
     * @param int $orderId
     * @return DeliveryAgent|null
     */
    public function assignDeliveryToOrder($orderId)
    {
        $order = Order::with(['commerce', 'delivery_address'])->find($orderId);
        
        if (!$order) {
            return null;
        }

        // Obtener delivery agents disponibles
        $availableAgents = DeliveryAgent::where('working', true)
                                       ->where('status', 'active')
                                       ->get();

        if ($availableAgents->isEmpty()) {
            return null;
        }

        // Calcular distancias y asignar el más cercano
        $bestAgent = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($availableAgents as $agent) {
            $distance = $this->trackingService->calculateDistance(
                $order->commerce->lat ?? 0,
                $order->commerce->lng ?? 0,
                $agent->lat ?? 0,
                $agent->lng ?? 0
            );

            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $bestAgent = $agent;
            }
        }

        if ($bestAgent) {
            // Asignar el delivery
            $order->delivery_agent_id = $bestAgent->id;
            $order->status = 'assigned';
            $order->save();

            // Marcar al agente como ocupado
            $bestAgent->working = false;
            $bestAgent->save();

            return $bestAgent;
        }

        return null;
    }

    /**
     * Liberar un delivery agent cuando completa una entrega.
     *
     * @param int $agentId
     * @return bool
     */
    public function releaseDeliveryAgent($agentId)
    {
        $agent = DeliveryAgent::find($agentId);
        
        if ($agent) {
            $agent->working = true;
            $agent->save();
            return true;
        }

        return false;
    }

    /**
     * Obtener delivery agents cercanos a una ubicación.
     *
     * @param float $lat
     * @param float $lng
     * @param float $maxDistance Distancia máxima en km
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNearbyAgents($lat, $lng, $maxDistance = 10)
    {
        $agents = DeliveryAgent::where('working', true)
                               ->where('status', 'active')
                               ->get();

        $nearbyAgents = collect();

        foreach ($agents as $agent) {
            $distance = $this->trackingService->calculateDistance(
                $lat, $lng,
                $agent->lat ?? 0, $agent->lng ?? 0
            );

            if ($distance <= $maxDistance) {
                $agent->distance = round($distance, 2);
                $nearbyAgents->push($agent);
            }
        }

        return $nearbyAgents->sortBy('distance');
    }

    /**
     * Reasignar ordenes si un delivery agent no está disponible.
     *
     * @param int $agentId
     * @return array
     */
    public function reassignOrdersFromAgent($agentId)
    {
        $orders = Order::where('delivery_agent_id', $agentId)
                      ->whereIn('status', ['assigned', 'in_transit'])
                      ->get();

        $reassigned = [];

        foreach ($orders as $order) {
            $newAgent = $this->assignDeliveryToOrder($order->id);
            
            if ($newAgent) {
                $reassigned[] = [
                    'order_id' => $order->id,
                    'new_agent_id' => $newAgent->id,
                    'new_agent_name' => $newAgent->name,
                ];
            }
        }

        return $reassigned;
    }
} 