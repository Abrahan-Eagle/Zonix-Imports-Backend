<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commerce;
use App\Models\Bank;
use App\Models\OperatorCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    /**
     * Obtener métodos de pago disponibles por vendedor
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            $commerceId = $request->get('commerce_id');
            $commerce = null;

            if ($commerceId) {
                // Obtener métodos de pago de un vendedor específico
                $commerce = Commerce::findOrFail($commerceId);
            } else {
                // Obtener métodos de pago del vendedor autenticado
                $commerce = $profile->commerce;
            }

            if (!$commerce) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comercio no encontrado'
                ], 404);
            }

            // Métodos de pago habilitados por el vendedor
            $enabledMethods = $commerce->payment_methods ?? [];

            // Métodos de pago disponibles globalmente
            $availableMethods = [
                'api' => [
                    'stripe' => [
                        'name' => 'Stripe',
                        'enabled' => in_array('stripe', $enabledMethods),
                        'description' => 'Tarjeta de crédito/débito',
                        'icon' => 'credit_card',
                        'fees' => '2.9% + $0.30'
                    ],
                    'paypal' => [
                        'name' => 'PayPal',
                        'enabled' => in_array('paypal', $enabledMethods),
                        'description' => 'PayPal Wallet',
                        'icon' => 'paypal',
                        'fees' => '2.9% + $0.30'
                    ],
                    'binance' => [
                        'name' => 'Binance Pay',
                        'enabled' => in_array('binance', $enabledMethods),
                        'description' => 'Crypto payments (USDT)',
                        'icon' => 'crypto',
                        'fees' => '0.1%'
                    ]
                ],
                'manual' => [
                    'pago_movil' => [
                        'name' => 'Pago Móvil',
                        'enabled' => in_array('pago_movil', $enabledMethods),
                        'description' => 'Transferencia bancaria local',
                        'icon' => 'phone_android',
                        'fees' => 'Sin comisión',
                        'banks' => $this->getAvailableBanks(),
                        'operators' => $this->getAvailableOperators()
                    ],
                    'zelle' => [
                        'name' => 'Zelle',
                        'enabled' => in_array('zelle', $enabledMethods),
                        'description' => 'Transferencia bancaria internacional',
                        'icon' => 'account_balance',
                        'fees' => 'Sin comisión'
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Métodos de pago obtenidos exitosamente',
                'data' => [
                    'commerce_id' => $commerce->id,
                    'commerce_name' => $commerce->business_name,
                    'methods' => $availableMethods,
                    'default_currency' => 'USD',
                    'supported_currencies' => ['USD', 'VES']
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comercio no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos de pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener bancos disponibles para Pago Móvil
     */
    private function getAvailableBanks()
    {
        return Bank::select('id', 'name', 'code', 'active')
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener operadoras disponibles para Pago Móvil
     */
    private function getAvailableOperators()
    {
        return OperatorCode::select('id', 'name', 'code', 'active')
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Actualizar métodos de pago habilitados (solo para vendedores)
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;
            $commerce = $profile->commerce;

            if (!$commerce) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un comercio asociado'
                ], 403);
            }

            $validated = $request->validate([
                'payment_methods' => 'required|array',
                'payment_methods.*' => 'in:stripe,paypal,binance,pago_movil,zelle'
            ]);

            $commerce->update([
                'payment_methods' => $validated['payment_methods']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Métodos de pago actualizados exitosamente',
                'data' => [
                    'commerce_id' => $commerce->id,
                    'enabled_methods' => $commerce->payment_methods
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar métodos de pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
