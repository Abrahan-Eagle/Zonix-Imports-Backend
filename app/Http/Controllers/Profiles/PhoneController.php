<?php

namespace App\Http\Controllers\Profiles;

use App\Http\Controllers\Controller;
use App\Models\OperatorCode;
use Illuminate\Http\Request;
use App\Models\Phone;
use App\Models\Profile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PhoneController extends Controller
{
    /**
     * Display a listing of the phones.
     */
    public function index()
    {
        // Obtener el usuario autenticado
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Buscar el perfil del usuario
        $profile = Profile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            return response()->json(['error' => 'Perfil no encontrado'], 404);
        }

        // Obtener todos los teléfonos del perfil
        $phones = Phone::with(['profile', 'operatorCode'])
            ->where('profile_id', $profile->id)
            ->get();

        return response()->json($phones);
    }

    /**
     * Get operator codes for dropdown
     */
    public function getOperatorCodes()
    {
        $operatorCodes = OperatorCode::all();
        return response()->json($operatorCodes);
    }

    public function store(Request $request)
    {
        // Log::info('Datos recibidos:', $request->all());
        // local.INFO: Datos recibidos: {"id":0,"profile_id":3,"operator_code_id":1,"operator_code_name":null,"number":"1234567","is_primary":1,"status":1}


        $validator = Validator::make($request->all(), [
            'profile_id' => 'required|exists:profiles,user_id',
            'operator_code_id' => 'required|exists:operator_codes,id',
            'number' => 'required|string|min:7|max:15|unique:phones,number',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $profile = Profile::where('user_id', $request->profile_id)->firstOrFail();

        if ($request->is_primary) {
            Phone::where('profile_id', $profile->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $phone = Phone::create([
            'profile_id' => $profile->id,
            'operator_code_id' => $request->operator_code_id, // Agregar este campo si es necesario
            'number' => $request->number,
            'is_primary' => $request->is_primary ?? false,
        ]);

        return response()->json(['message' => 'Phone created successfully', 'phone' => $phone], 201);
    }

    /**
     * Display the specified phone.
     */
    // public function show($id)
    // {

    //     Log::info('Datos recibidos:', $request->all());

    //     $profile = Profile::where('user_id', $id)->firstOrFail();

    //     $phone = Phone::with(['profile', 'operatorCode'])->where('profile_id', $profile->id)->where('status', true)->get();

    //     if (!$phone) {
    //         return response()->json(['message' => 'Phone not found'], 404);
    //     }

    //     return response()->json($phone);
    // }

        public function show(Request $request, $id)
        {
            Log::info('Datos recibidos:', $request->all());

            $profile = Profile::where('user_id', $id)->firstOrFail();

            $phone = Phone::with(['profile', 'operatorCode'])
                ->where('profile_id', $profile->id)
                ->where('status', true)
                ->get();

            if (!$phone) {
                return response()->json(['message' => 'Phone not found'], 404);
            }

            return response()->json($phone);
        }


    /**
     * Update the specified phone in storage.
     */
    public function update(Request $request, $id)
    {
        $phone = Phone::find($id);

        if (!$phone) {
            return response()->json(['message' => 'Phone not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'operator_code_id' => 'sometimes|exists:operator_codes,id',
            'number' => 'sometimes|string|min:7|max:15|unique:phones,number,' . $id,
            'is_primary' => 'sometimes|boolean',
            'status' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Si se marca como principal, desmarcar otros teléfonos principales del mismo perfil
        if ($request->has('is_primary') && $request->is_primary) {
            Phone::where('profile_id', $phone->profile_id)
                ->where('id', '!=', $phone->id)
                ->update(['is_primary' => false]);
        }

        // Actualizar los campos permitidos
        if ($request->has('operator_code_id')) {
            $phone->operator_code_id = $request->operator_code_id;
        }
        
        if ($request->has('number')) {
            $phone->number = $request->number;
        }
        
        if ($request->has('is_primary')) {
            $phone->is_primary = $request->is_primary;
        }
        
        if ($request->has('status')) {
            $phone->status = $request->status;
        }

        $phone->save();

        // Cargar las relaciones para la respuesta
        $phone->load(['profile', 'operatorCode']);

        return response()->json(['message' => 'Phone updated successfully', 'phone' => $phone]);
    }

    /**
     * Remove the specified phone from storage.
     */
    public function destroy($id)
    {
        $phone = Phone::find($id);

        if (!$phone) {
            return response()->json(['message' => 'Phone not found'], 404);
        }

        $phone->status = false;
        $phone->save();

        return response()->json(['message' => 'Phone deleted successfully']);
    }
}
