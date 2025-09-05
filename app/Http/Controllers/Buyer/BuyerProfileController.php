<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerProfileController extends Controller
{
    /**
     * Mostrar el perfil del usuario
     */
    public function show(Profile $profile)
    {
        // Verificar que el usuario solo puede acceder a su propio perfil
        if ($profile->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($profile->load('user'));
    }

    /**
     * Actualizar el perfil del usuario
     */
    public function update(Request $request, Profile $profile)
    {
        // Verificar que el usuario solo puede actualizar su propio perfil
        if ($profile->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
        ]);

        $profile->update($request->only([
            'first_name', 'last_name', 'phone', 'address'
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->fresh()
        ]);
    }
} 