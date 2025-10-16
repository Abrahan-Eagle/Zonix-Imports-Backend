<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class OrderService
{
    /**
     * Obtener órdenes del comprador
     * 
     * @param Profile $profile
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getBuyerOrders(Profile $profile, array $filters = []): LengthAwarePaginator
    {
        $query = Order::with([
            'orderItems.product',
            'commerce',
            'shippingAddress.city.state',
            'payments'
        ])->where('profile_id', $profile->id);

        // Filtros
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['modality'])) {
            $query->where('modality', $filters['modality']);
        }

        // Ordenar por más reciente
        $query->orderBy('created_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Obtener detalle de una orden (buyer)
     * 
     * @param int $orderId
     * @param Profile $profile
     * @return Order
     * @throws Exception
     */
    public function getBuyerOrderDetail(int $orderId, Profile $profile): Order
    {
        $order = Order::with([
            'orderItems.product.commerce',
            'commerce',
            'shippingAddress.city.state',
            'billingAddress.city.state',
            'payments'
        ])->find($orderId);

        if (!$order) {
            throw new Exception('Orden no encontrada');
        }

        if ($order->profile_id !== $profile->id) {
            throw new Exception('No tienes permiso para ver esta orden');
        }

        return $order;
    }

    /**
     * Obtener órdenes del vendedor (seller)
     * 
     * @param Profile $profile
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getSellerOrders(Profile $profile, array $filters = []): LengthAwarePaginator
    {
        // Obtener el commerce del seller
        $commerce = $profile->commerce;

        if (!$commerce) {
            throw new Exception('No tienes un comercio asociado');
        }

        $query = Order::with([
            'orderItems.product',
            'profile.user',
            'shippingAddress.city.state',
            'payments'
        ])->where('commerce_id', $commerce->id);

        // Filtros
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['delivery_type'])) {
            $query->where('delivery_type', $filters['delivery_type']);
        }

        // Ordenar por más reciente
        $query->orderBy('created_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Obtener detalle de una orden (seller)
     * 
     * @param int $orderId
     * @param Profile $profile
     * @return Order
     * @throws Exception
     */
    public function getSellerOrderDetail(int $orderId, Profile $profile): Order
    {
        // Obtener el commerce del seller
        $commerce = $profile->commerce;

        if (!$commerce) {
            throw new Exception('No tienes un comercio asociado');
        }

        $order = Order::with([
            'orderItems.product',
            'profile.user',
            'shippingAddress.city.state',
            'billingAddress.city.state',
            'payments'
        ])->find($orderId);

        if (!$order) {
            throw new Exception('Orden no encontrada');
        }

        if ($order->commerce_id !== $commerce->id) {
            throw new Exception('No tienes permiso para ver esta orden');
        }

        return $order;
    }

    /**
     * Actualizar estado de la orden (seller)
     * 
     * @param int $orderId
     * @param Profile $profile
     * @param string $newStatus
     * @param string|null $trackingNumber
     * @return Order
     * @throws Exception
     */
    public function updateOrderStatus(
        int $orderId,
        Profile $profile,
        string $newStatus,
        ?string $trackingNumber = null
    ): Order {
        $order = $this->getSellerOrderDetail($orderId, $profile);

        // Validar estados permitidos
        $validStatuses = ['paid', 'preparing', 'on_way', 'delivered', 'cancelled'];
        
        if (!in_array($newStatus, $validStatuses)) {
            throw new Exception('Estado inválido');
        }

        // Validar transiciones de estado
        if (!$this->isValidStatusTransition($order->status, $newStatus)) {
            throw new Exception("No se puede cambiar de '{$order->status}' a '$newStatus'");
        }

        $order->status = $newStatus;
        
        if ($trackingNumber) {
            $order->tracking_number = $trackingNumber;
        }

        $order->save();

        Log::info('Estado de orden actualizado', [
            'order_id' => $order->id,
            'old_status' => $order->getOriginal('status'),
            'new_status' => $newStatus,
            'seller_id' => $profile->id
        ]);

        return $order;
    }

    /**
     * Validar transición de estado
     * 
     * @param string $currentStatus
     * @param string $newStatus
     * @return bool
     */
    protected function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $transitions = [
            'pending_payment' => ['paid', 'partially_paid', 'cancelled'],
            'partially_paid' => ['paid', 'cancelled'],
            'paid' => ['preparing', 'cancelled'],
            'preparing' => ['on_way', 'cancelled'],
            'on_way' => ['delivered', 'cancelled'],
            'delivered' => [], // Estado final
            'cancelled' => [] // Estado final
        ];

        return in_array($newStatus, $transitions[$currentStatus] ?? []);
    }

    /**
     * Obtener tracking de una orden
     * 
     * @param int $orderId
     * @param Profile $profile
     * @return array
     * @throws Exception
     */
    public function getOrderTracking(int $orderId, Profile $profile): array
    {
        $order = $this->getBuyerOrderDetail($orderId, $profile);

        $timeline = [
            [
                'status' => 'pending_payment',
                'label' => 'Orden Creada',
                'completed' => true,
                'date' => $order->created_at->format('Y-m-d H:i:s')
            ]
        ];

        $statusOrder = ['pending_payment', 'partially_paid', 'paid', 'preparing', 'on_way', 'delivered'];
        $currentIndex = array_search($order->status, $statusOrder);

        $statusLabels = [
            'pending_payment' => 'Pendiente de Pago',
            'partially_paid' => 'Pago Parcial',
            'paid' => 'Pagado',
            'preparing' => 'Preparando',
            'on_way' => 'En Camino',
            'delivered' => 'Entregado'
        ];

        foreach ($statusOrder as $index => $status) {
            if ($status === 'pending_payment') continue; // Ya agregado

            $timeline[] = [
                'status' => $status,
                'label' => $statusLabels[$status],
                'completed' => $index <= $currentIndex,
                'date' => $index <= $currentIndex ? ($order->updated_at->format('Y-m-d H:i:s')) : null
            ];
        }

        return [
            'order_id' => $order->id,
            'current_status' => $order->status,
            'tracking_number' => $order->tracking_number,
            'estimated_delivery' => $order->estimated_delivery?->format('Y-m-d'),
            'timeline' => $timeline
        ];
    }
}
