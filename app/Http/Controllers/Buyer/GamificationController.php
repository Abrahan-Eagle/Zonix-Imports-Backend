<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Profile;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class GamificationController extends Controller
{
    /**
     * Obtener puntos y nivel del usuario
     */
    public function getUserPoints()
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        // Calcular puntos basados en pedidos completados
        $completedOrders = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        
        $points = $completedOrders * 10; // 10 puntos por pedido
        $level = floor($points / 100) + 1; // 1 nivel por cada 100 puntos
        
        return response()->json([
            'points' => $points,
            'level' => $level,
            'completed_orders' => $completedOrders,
            'points_to_next_level' => 100 - ($points % 100)
        ]);
    }

    /**
     * Obtener recompensas disponibles
     */
    public function getAvailableRewards()
    {
        $user = Auth::user();
        $profile = $user->profile;
        
        // Calcular puntos actuales
        $completedOrders = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        $points = $completedOrders * 10;
        
        $rewards = [
            [
                'id' => 1,
                'name' => 'Descuento 5%',
                'description' => '5% de descuento en tu próximo pedido',
                'points_required' => 50,
                'available' => $points >= 50,
                'type' => 'discount'
            ],
            [
                'id' => 2,
                'name' => 'Envío Gratis',
                'description' => 'Envío gratis en tu próximo pedido',
                'points_required' => 100,
                'available' => $points >= 100,
                'type' => 'free_shipping'
            ],
            [
                'id' => 3,
                'name' => 'Descuento 10%',
                'description' => '10% de descuento en tu próximo pedido',
                'points_required' => 200,
                'available' => $points >= 200,
                'type' => 'discount'
            ],
            [
                'id' => 4,
                'name' => 'Pedido Gratis',
                'description' => 'Pedido gratis hasta $20',
                'points_required' => 500,
                'available' => $points >= 500,
                'type' => 'free_order'
            ]
        ];
        
        return response()->json([
            'rewards' => $rewards,
            'current_points' => $points
        ]);
    }

    /**
     * Canjear recompensa
     */
    public function redeemReward(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|integer'
        ]);
        
        $user = Auth::user();
        
        // Calcular puntos actuales
        $completedOrders = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        $points = $completedOrders * 10;
        
        $rewards = [
            1 => ['points' => 50, 'type' => 'discount', 'value' => 5],
            2 => ['points' => 100, 'type' => 'free_shipping', 'value' => 0],
            3 => ['points' => 200, 'type' => 'discount', 'value' => 10],
            4 => ['points' => 500, 'type' => 'free_order', 'value' => 20]
        ];
        
        if (!isset($rewards[$request->reward_id])) {
            return response()->json(['error' => 'Recompensa no válida'], 400);
        }
        
        $reward = $rewards[$request->reward_id];
        
        if ($points < $reward['points']) {
            return response()->json(['error' => 'Puntos insuficientes'], 400);
        }
        
        // Aquí se generaría un código de descuento o se aplicaría la recompensa
        $couponCode = 'REWARD_' . strtoupper(uniqid());
        
        return response()->json([
            'message' => 'Recompensa canjeada exitosamente',
            'coupon_code' => $couponCode,
            'reward_type' => $reward['type'],
            'reward_value' => $reward['value'],
            'points_used' => $reward['points'],
            'remaining_points' => $points - $reward['points']
        ]);
    }

    /**
     * Obtener badges del usuario
     */
    public function getUserBadges()
    {
        $user = Auth::user();
        
        $completedOrders = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        
        $totalSpent = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->sum('total');
        
        $badges = [
            [
                'id' => 1,
                'name' => 'Primer Pedido',
                'description' => 'Completaste tu primer pedido',
                'icon' => '🎉',
                'unlocked' => $completedOrders >= 1,
                'unlocked_at' => $completedOrders >= 1 ? now()->toISOString() : null
            ],
            [
                'id' => 2,
                'name' => 'Cliente Frecuente',
                'description' => 'Completaste 10 pedidos',
                'icon' => '⭐',
                'unlocked' => $completedOrders >= 10,
                'unlocked_at' => $completedOrders >= 10 ? now()->toISOString() : null
            ],
            [
                'id' => 3,
                'name' => 'Gran Cliente',
                'description' => 'Completaste 50 pedidos',
                'icon' => '👑',
                'unlocked' => $completedOrders >= 50,
                'unlocked_at' => $completedOrders >= 50 ? now()->toISOString() : null
            ],
            [
                'id' => 4,
                'name' => 'Gastador',
                'description' => 'Gastaste más de $100',
                'icon' => '💰',
                'unlocked' => $totalSpent >= 100,
                'unlocked_at' => $totalSpent >= 100 ? now()->toISOString() : null
            ],
            [
                'id' => 5,
                'name' => 'Maestro Gastador',
                'description' => 'Gastaste más de $500',
                'icon' => '💎',
                'unlocked' => $totalSpent >= 500,
                'unlocked_at' => $totalSpent >= 500 ? now()->toISOString() : null
            ]
        ];
        
        return response()->json([
            'badges' => $badges,
            'total_badges' => count($badges),
            'unlocked_badges' => count(array_filter($badges, fn($badge) => $badge['unlocked']))
        ]);
    }

    /**
     * Obtener leaderboard
     */
    public function getLeaderboard()
    {
        $leaderboard = DB::table('users')
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->leftJoin('orders', 'users.id', '=', 'orders.buyer_id')
            ->where('orders.status', 'delivered')
            ->select(
                'users.id',
                'users.name',
                'profiles.profile_photo',
                DB::raw('COUNT(orders.id) as completed_orders'),
                DB::raw('COUNT(orders.id) * 10 as points')
            )
            ->groupBy('users.id', 'users.name', 'profiles.profile_photo')
            ->orderBy('points', 'desc')
            ->limit(20)
            ->get();
        
        return response()->json([
            'leaderboard' => $leaderboard,
            'user_position' => $this->getUserPosition()
        ]);
    }

    /**
     * Obtener posición del usuario en el leaderboard
     */
    private function getUserPosition()
    {
        $user = Auth::user();
        
        $userPoints = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->count() * 10;
        
        $position = DB::table('users')
            ->leftJoin('orders', 'users.id', '=', 'orders.buyer_id')
            ->where('orders.status', 'delivered')
            ->select(DB::raw('COUNT(orders.id) * 10 as points'))
            ->groupBy('users.id')
            ->having('points', '>', $userPoints)
            ->count() + 1;
        
        return $position;
    }

    /**
     * Obtener estadísticas de gamificación
     */
    public function getGamificationStats()
    {
        $user = Auth::user();
        
        $completedOrders = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        
        $points = $completedOrders * 10;
        $level = floor($points / 100) + 1;
        
        $totalSpent = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->sum('total');
        
        $rewardsRedeemed = 0; // Esto se implementaría con una tabla de recompensas canjeadas
        
        return response()->json([
            'total_points' => $points,
            'current_level' => $level,
            'completed_orders' => $completedOrders,
            'total_spent' => $totalSpent,
            'rewards_redeemed' => $rewardsRedeemed,
            'points_to_next_level' => 100 - ($points % 100),
            'level_progress' => ($points % 100) / 100 * 100
        ]);
    }
} 