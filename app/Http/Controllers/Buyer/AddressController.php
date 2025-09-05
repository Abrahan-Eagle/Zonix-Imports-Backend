<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Address;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Obtener direcciones del usuario
     */
    public function getUserAddresses(): JsonResponse
    {
        try {
            $profile = auth()->user()->profile;
            
            $addresses = Address::where('profile_id', $profile->id)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $addressesData = $addresses->map(function ($address) {
                return [
                    'id' => $address->id,
                    'name' => $address->name,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'is_default' => $address->is_default,
                    'delivery_instructions' => $address->delivery_instructions,
                    'formatted_address' => $this->formatAddress($address)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $addressesData
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user addresses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las direcciones'
            ], 500);
        }
    }

    /**
     * Crear nueva dirección
     */
    public function createAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'boolean',
            'delivery_instructions' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $profile = auth()->user()->profile;

            // Si se marca como predeterminada, quitar la marca de las demás
            if ($request->is_default) {
                Address::where('profile_id', $profile->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $address = Address::create([
                'profile_id' => $profile->id,
                'name' => $request->name,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_default' => $request->is_default ?? false,
                'delivery_instructions' => $request->delivery_instructions
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dirección creada exitosamente',
                'data' => [
                    'id' => $address->id,
                    'name' => $address->name,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'is_default' => $address->is_default,
                    'delivery_instructions' => $address->delivery_instructions,
                    'formatted_address' => $this->formatAddress($address)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la dirección'
            ], 500);
        }
    }

    /**
     * Actualizar dirección
     */
    public function updateAddress(Request $request, $addressId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'boolean',
            'delivery_instructions' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $profile = auth()->user()->profile;
            $address = Address::where('id', $addressId)
                ->where('profile_id', $profile->id)
                ->firstOrFail();

            // Si se marca como predeterminada, quitar la marca de las demás
            if ($request->is_default) {
                Address::where('profile_id', $profile->id)
                    ->where('id', '!=', $addressId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $address->update([
                'name' => $request->name,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_default' => $request->is_default ?? $address->is_default,
                'delivery_instructions' => $request->delivery_instructions
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dirección actualizada exitosamente',
                'data' => [
                    'id' => $address->id,
                    'name' => $address->name,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'is_default' => $address->is_default,
                    'delivery_instructions' => $address->delivery_instructions,
                    'formatted_address' => $this->formatAddress($address)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la dirección'
            ], 500);
        }
    }

    /**
     * Eliminar dirección
     */
    public function deleteAddress($addressId): JsonResponse
    {
        try {
            $profile = auth()->user()->profile;
            $address = Address::where('id', $addressId)
                ->where('profile_id', $profile->id)
                ->firstOrFail();

            // No permitir eliminar la dirección predeterminada si es la única
            if ($address->is_default) {
                $totalAddresses = Address::where('profile_id', $profile->id)->count();
                if ($totalAddresses === 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No puedes eliminar la única dirección disponible'
                    ], 400);
                }
            }

            $address->delete();

            // Si se eliminó la dirección predeterminada, marcar otra como predeterminada
            if ($address->is_default) {
                $newDefault = Address::where('profile_id', $profile->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Dirección eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la dirección'
            ], 500);
        }
    }

    /**
     * Establecer dirección como predeterminada
     */
    public function setDefaultAddress($addressId): JsonResponse
    {
        try {
            $profile = auth()->user()->profile;
            $address = Address::where('id', $addressId)
                ->where('profile_id', $profile->id)
                ->firstOrFail();

            // Quitar la marca de predeterminada de todas las direcciones
            Address::where('profile_id', $profile->id)
                ->update(['is_default' => false]);

            // Marcar esta dirección como predeterminada
            $address->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Dirección establecida como predeterminada'
            ]);
        } catch (\Exception $e) {
            Log::error('Error setting default address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al establecer la dirección predeterminada'
            ], 500);
        }
    }

    /**
     * Obtener dirección predeterminada
     */
    public function getDefaultAddress(): JsonResponse
    {
        try {
            $profile = auth()->user()->profile;
            
            $address = Address::where('profile_id', $profile->id)
                ->where('is_default', true)
                ->first();

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay dirección predeterminada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $address->id,
                    'name' => $address->name,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'is_default' => $address->is_default,
                    'delivery_instructions' => $address->delivery_instructions,
                    'formatted_address' => $this->formatAddress($address)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting default address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la dirección predeterminada'
            ], 500);
        }
    }

    /**
     * Formatear dirección para mostrar
     */
    private function formatAddress(Address $address): string
    {
        $parts = [
            $address->address_line_1,
            $address->address_line_2,
            $address->city,
            $address->state,
            $address->postal_code,
            $address->country
        ];

        return implode(', ', array_filter($parts));
    }
} 