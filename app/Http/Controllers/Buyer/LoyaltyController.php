<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Profile;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class LoyaltyController extends Controller
{
    /**
     * Obtener información del programa de lealtad
     */
    public function getLoyaltyInfo()
    {
        $user = Auth::user();
        
        $completedOrders = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        
        $totalSpent = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->sum('total');
        
        // Calcular nivel de lealtad basado en gastos
        $loyaltyLevel = $this->calculateLoyaltyLevel($totalSpent);
        
        return response()->json([
            'loyalty_level' => $loyaltyLevel['level'],
            'level_name' => $loyaltyLevel['name'],
            'total_spent' => $totalSpent,
            'completed_orders' => $completedOrders,
            'next_level_threshold' => $loyaltyLevel['next_threshold'],
            'spent_to_next_level' => $loyaltyLevel['next_threshold'] - $totalSpent,
            'benefits' => $loyaltyLevel['benefits']
        ]);
    }

    /**
     * Calcular nivel de lealtad
     */
    private function calculateLoyaltyLevel($totalSpent)
    {
        if ($totalSpent >= 1000) {
            return [
                'level' => 4,
                'name' => 'Diamante',
                'next_threshold' => null,
                'benefits' => [
                    'Descuento 15% en todos los pedidos',
                    'Envío gratis siempre',
                    'Acceso prioritario a promociones',
                    'Soporte VIP'
                ]
            ];
        } elseif ($totalSpent >= 500) {
            return [
                'level' => 3,
                'name' => 'Oro',
                'next_threshold' => 1000,
                'benefits' => [
                    'Descuento 10% en todos los pedidos',
                    'Envío gratis en pedidos > $30',
                    'Acceso a promociones exclusivas'
                ]
            ];
        } elseif ($totalSpent >= 200) {
            return [
                'level' => 2,
                'name' => 'Plata',
                'next_threshold' => 500,
                'benefits' => [
                    'Descuento 5% en todos los pedidos',
                    'Envío gratis en pedidos > $50'
                ]
            ];
        } else {
            return [
                'level' => 1,
                'name' => 'Bronce',
                'next_threshold' => 200,
                'benefits' => [
                    'Descuento 2% en todos los pedidos'
                ]
            ];
        }
    }

    /**
     * Obtener descuentos por volumen
     */
    public function getVolumeDiscounts()
    {
        $user = Auth::user();
        
        $monthlySpent = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->whereMonth('created_at', now()->month)
            ->sum('total');
        
        $discounts = [
            [
                'threshold' => 100,
                'discount_percentage' => 5,
                'achieved' => $monthlySpent >= 100,
                'current_amount' => $monthlySpent,
                'remaining' => max(0, 100 - $monthlySpent)
            ],
            [
                'threshold' => 300,
                'discount_percentage' => 10,
                'achieved' => $monthlySpent >= 300,
                'current_amount' => $monthlySpent,
                'remaining' => max(0, 300 - $monthlySpent)
            ],
            [
                'threshold' => 500,
                'discount_percentage' => 15,
                'achieved' => $monthlySpent >= 500,
                'current_amount' => $monthlySpent,
                'remaining' => max(0, 500 - $monthlySpent)
            ]
        ];
        
        return response()->json([
            'monthly_spent' => $monthlySpent,
            'discounts' => $discounts,
            'current_discount' => $this->getCurrentDiscount($monthlySpent)
        ]);
    }

    /**
     * Obtener descuento actual basado en gasto mensual
     */
    private function getCurrentDiscount($monthlySpent)
    {
        if ($monthlySpent >= 500) return 15;
        if ($monthlySpent >= 300) return 10;
        if ($monthlySpent >= 100) return 5;
        return 0;
    }

    /**
     * Generar código de referido
     */
    public function generateReferralCode()
    {
        $user = Auth::user();
        
        $referralCode = 'REF_' . strtoupper(substr($user->name, 0, 3)) . $user->id;
        
        return response()->json([
            'referral_code' => $referralCode,
            'referral_link' => url('/register?ref=' . $referralCode),
            'rewards_earned' => 0, // Se implementaría con tabla de referidos
            'total_referrals' => 0
        ]);
    }

    /**
     * Aplicar código de referido
     */
    public function applyReferralCode(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string|min:6'
        ]);
        
        $user = Auth::user();
        
        // Verificar si el código existe
        $referrerId = $this->getUserIdFromReferralCode($request->referral_code);
        
        if (!$referrerId) {
            return response()->json(['error' => 'Código de referido inválido'], 400);
        }
        
        if ($referrerId == $user->id) {
            return response()->json(['error' => 'No puedes referirte a ti mismo'], 400);
        }
        
        // Aquí se aplicaría la lógica de recompensas por referido
        $reward = [
            'referrer_reward' => 500, // Puntos para quien refiere
            'referred_reward' => 200, // Puntos para quien es referido
            'message' => 'Código de referido aplicado exitosamente'
        ];
        
        return response()->json($reward);
    }

    /**
     * Obtener ID de usuario desde código de referido
     */
    private function getUserIdFromReferralCode($code)
    {
        // Simular extracción de ID desde código
        if (preg_match('/REF_[A-Z]{3}(\d+)/', $code, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Obtener historial de beneficios
     */
    public function getBenefitsHistory()
    {
        $user = Auth::user();
        
        $benefits = [
            [
                'id' => 1,
                'type' => 'loyalty_discount',
                'description' => 'Descuento por nivel de lealtad',
                'amount' => 5.00,
                'applied_at' => now()->subDays(2)->toISOString(),
                'order_id' => 123
            ],
            [
                'id' => 2,
                'type' => 'volume_discount',
                'description' => 'Descuento por volumen mensual',
                'amount' => 10.00,
                'applied_at' => now()->subDays(5)->toISOString(),
                'order_id' => 120
            ],
            [
                'id' => 3,
                'type' => 'referral_reward',
                'description' => 'Recompensa por referido',
                'amount' => 200,
                'applied_at' => now()->subDays(10)->toISOString(),
                'order_id' => null
            ]
        ];
        
        $totalBenefits = array_sum(array_column($benefits, 'amount'));
        
        return response()->json([
            'benefits' => $benefits,
            'total_benefits' => $totalBenefits,
            'total_count' => count($benefits)
        ]);
    }

    /**
     * Obtener estadísticas de fidelización
     */
    public function getLoyaltyStats()
    {
        $user = Auth::user();
        
        $totalSpent = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->sum('total');
        
        $monthlySpent = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->whereMonth('created_at', now()->month)
            ->sum('total');
        
        $loyaltyLevel = $this->calculateLoyaltyLevel($totalSpent);
        
        return response()->json([
            'total_spent' => $totalSpent,
            'monthly_spent' => $monthlySpent,
            'loyalty_level' => $loyaltyLevel['level'],
            'level_name' => $loyaltyLevel['name'],
            'current_discount' => $this->getCurrentDiscount($monthlySpent),
            'loyalty_discount' => $loyaltyLevel['level'] * 2, // 2% por nivel
            'total_discount_earned' => $totalSpent * 0.05, // Estimado
            'referrals_count' => 0,
            'referral_rewards' => 0
        ]);
    }

    /**
     * Obtener próximos beneficios disponibles
     */
    public function getUpcomingBenefits()
    {
        $user = Auth::user();
        
        $totalSpent = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->sum('total');
        
        $monthlySpent = Order::where('buyer_id', $user->id)
            ->where('status', 'delivered')
            ->whereMonth('created_at', now()->month)
            ->sum('total');
        
        $upcoming = [];
        
        // Beneficios por nivel de lealtad
        if ($totalSpent < 200) {
            $upcoming[] = [
                'type' => 'loyalty_level',
                'name' => 'Nivel Plata',
                'description' => 'Descuento 5% en todos los pedidos',
                'threshold' => 200,
                'current' => $totalSpent,
                'remaining' => 200 - $totalSpent
            ];
        }
        
        // Beneficios por volumen mensual
        if ($monthlySpent < 100) {
            $upcoming[] = [
                'type' => 'volume_discount',
                'name' => 'Descuento 5% mensual',
                'description' => 'Descuento por gastar $100 en el mes',
                'threshold' => 100,
                'current' => $monthlySpent,
                'remaining' => 100 - $monthlySpent
            ];
        }
        
        return response()->json([
            'upcoming_benefits' => $upcoming,
            'total_upcoming' => count($upcoming)
        ]);
    }
} 