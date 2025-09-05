<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    /**
     * Obtener historial de actividad del usuario
     */
    public function getUserActivityHistory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100',
                'activity_type' => 'string|in:login,order_placed,order_cancelled,profile_updated,review_posted',
                'start_date' => 'date',
                'end_date' => 'date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $activityType = $request->get('activity_type');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // Simular datos de actividad (en producción esto vendría de una tabla real)
            $activities = $this->generateMockActivities($user->id, $activityType, $startDate, $endDate);

            // Aplicar paginación
            $total = count($activities);
            $offset = ($page - 1) * $limit;
            $paginatedActivities = array_slice($activities, $offset, $limit);

            return response()->json([
                'success' => true,
                'data' => $paginatedActivities,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit),
                    'from' => $offset + 1,
                    'to' => min($offset + $limit, $total),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de actividad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de actividad
     */
    public function getActivityStats()
    {
        try {
            $user = Auth::user();

            // Simular estadísticas (en producción esto vendría de consultas reales)
            $stats = $this->generateMockStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar datos mock de actividad
     */
    private function generateMockActivities($userId, $activityType = null, $startDate = null, $endDate = null)
    {
        $activities = [
            [
                'id' => 1,
                'user_id' => $userId,
                'activity_type' => 'login',
                'description' => 'Inicio de sesión exitoso',
                'metadata' => [
                    'ip' => '192.168.1.100',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'location' => 'Madrid, España'
                ],
                'created_at' => now()->subHours(2)->toISOString(),
                'updated_at' => now()->subHours(2)->toISOString(),
            ],
            [
                'id' => 2,
                'user_id' => $userId,
                'activity_type' => 'order_placed',
                'description' => 'Pedido #ORD-001 realizado',
                'metadata' => [
                    'order_id' => 'ORD-001',
                    'total_amount' => 25.50,
                    'restaurant' => 'Restaurante Ejemplo',
                    'items_count' => 3
                ],
                'created_at' => now()->subDays(1)->toISOString(),
                'updated_at' => now()->subDays(1)->toISOString(),
            ],
            [
                'id' => 3,
                'user_id' => $userId,
                'activity_type' => 'profile_updated',
                'description' => 'Perfil actualizado',
                'metadata' => [
                    'updated_fields' => ['name', 'email'],
                    'previous_values' => ['Juan', 'juan@old.com'],
                    'new_values' => ['Juan Carlos', 'juan@new.com']
                ],
                'created_at' => now()->subDays(2)->toISOString(),
                'updated_at' => now()->subDays(2)->toISOString(),
            ],
            [
                'id' => 4,
                'user_id' => $userId,
                'activity_type' => 'review_posted',
                'description' => 'Reseña publicada para pedido #ORD-001',
                'metadata' => [
                    'order_id' => 'ORD-001',
                    'rating' => 5,
                    'comment' => 'Excelente servicio y comida deliciosa',
                    'restaurant' => 'Restaurante Ejemplo'
                ],
                'created_at' => now()->subDays(3)->toISOString(),
                'updated_at' => now()->subDays(3)->toISOString(),
            ],
            [
                'id' => 5,
                'user_id' => $userId,
                'activity_type' => 'order_cancelled',
                'description' => 'Pedido #ORD-002 cancelado',
                'metadata' => [
                    'order_id' => 'ORD-002',
                    'cancellation_reason' => 'Cambio de planes',
                    'refund_amount' => 15.75
                ],
                'created_at' => now()->subDays(4)->toISOString(),
                'updated_at' => now()->subDays(4)->toISOString(),
            ],
        ];

        // Filtrar por tipo de actividad
        if ($activityType) {
            $activities = array_filter($activities, function($activity) use ($activityType) {
                return $activity['activity_type'] === $activityType;
            });
        }

        // Filtrar por fecha
        if ($startDate || $endDate) {
            $activities = array_filter($activities, function($activity) use ($startDate, $endDate) {
                $activityDate = \Carbon\Carbon::parse($activity['created_at']);
                
                if ($startDate && $activityDate < \Carbon\Carbon::parse($startDate)) {
                    return false;
                }
                
                if ($endDate && $activityDate > \Carbon\Carbon::parse($endDate)) {
                    return false;
                }
                
                return true;
            });
        }

        return array_values($activities);
    }

    /**
     * Generar estadísticas mock
     */
    private function generateMockStats($userId)
    {
        return [
            'total_activities' => 45,
            'this_month' => 12,
            'this_week' => 3,
            'this_day' => 1,
            'activity_breakdown' => [
                'login' => 15,
                'order_placed' => 20,
                'order_cancelled' => 3,
                'profile_updated' => 5,
                'review_posted' => 2,
            ],
            'most_active_day' => 'Lunes',
            'average_activities_per_day' => 2.1,
        ];
    }
}
