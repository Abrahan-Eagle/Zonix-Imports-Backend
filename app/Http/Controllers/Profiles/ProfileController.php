<?php

namespace App\Http\Controllers\Profiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Listar todos los perfiles.
     */
    public function index()
    {
        $profiles = Profile::with(['user', 'addresses'])->get();
        return response()->json($profiles);
    }

    /**
     * Crear un nuevo perfil.
     */
    public function store(Request $request)
    {
        // Validación de los datos de entrada.
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'firstName' => 'required|string|max:255',
            'middleName' => 'nullable|string|max:255',
            'lastName' => 'required|string|max:255',
            'secondLastName' => 'nullable|string|max:255',
            'photo_users' => 'nullable|image|mimes:jpeg,png,jpg',
            'date_of_birth' => 'required|date',
            'maritalStatus' => 'required|in:married,divorced,single',
            'sex' => 'required|in:F,M',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

                // Verificar si ya existe un perfil para el usuario.
        $existingProfile = Profile::where('user_id', $request->user_id)->first();

        if ($existingProfile) {
            return response()->json([
                'message' => 'Ya existe un perfil asociado a este usuario.',
                'profile' => $existingProfile
            ], 409); // Código de estado HTTP 409: Conflicto
        }



        $profileData = $request->only([
            'user_id', 'firstName', 'lastName', 'date_of_birth', 'maritalStatus', 'sex'
        ]);

          // Establecer valores predeterminados para campos opcionales.
        $profileData['middleName'] = $request->middleName ?? '';
        $profileData['secondLastName'] = $request->secondLastName ?? '';
        $profileData['status'] = 'notverified'; // Estado inicial.

        // Manejar la carga de la imagen.
        if ($request->hasFile('photo_users')) {
            // Obtener la URL base según el entorno.
            $baseUrl = env('APP_ENV') === 'production'
                ? env('APP_URL_PRODUCTION')
                : env('APP_URL_LOCAL');

            // Guardar la nueva imagen en el disco público.
            $path = $request->file('photo_users')->store('profile_images', 'public');
            $profileData['photo_users'] = $baseUrl . '/storage/' . $path; // Guarda la URL pública.
        }

        // Crear el perfil.
        $profile = Profile::create($profileData);

        return response()->json([
            'message' => 'Perfil creado exitosamente.',
            'profile' => $profile
        ], 201);
    }

    /**
     * Mostrar un perfil específico.
     */
    public function show($id)
    {
        $profile = Profile::with(['user', 'addresses'])->find($id);

        if (!$profile) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        return response()->json($profile);
    }
    public function update(Request $request, $id)
{
    // Buscar el perfil por ID o devolver error 404.
    $profile = Profile::findOrFail($id);

    // Validar los datos recibidos, incluyendo el formato correcto para la fecha.
    $validatedData = $request->validate([
        'firstName' => 'required|string|max:255',
        'middleName' => 'nullable|string|max:255',
        'lastName' => 'required|string|max:255',
        'secondLastName' => 'nullable|string|max:255',
        'photo_users' => 'nullable|image|mimes:jpeg,png,jpg',
        'date_of_birth' => 'required|date',
        'maritalStatus' => 'required|in:married,divorced,single',
        'sex' => 'required|in:F,M',
    ]);



    // Log para depurar la fecha recibida y asegurar que esté en el formato correcto
    Log::info('Fecha recibida: ' . $validatedData['date_of_birth']);  // Verificar que esté en formato Y-m-d

    // Obtener el nombre del perfil y la fecha de creación
    $created_at = $profile->created_at->format('YmdHis');  // Formato de fecha
    $date_of_birth = Carbon::parse($validatedData['date_of_birth'])->format('Ymd'); // Formato de fecha de nacimiento (Ymd)
    $firstName = $validatedData['firstName'];
    $lastName = $validatedData['lastName'];
    $randomDigits = strtoupper(substr(md5(mt_rand()), 0, 7));  // Generar 7 caracteres aleatorios

    // Establecer valores predeterminados para campos opcionales
    $validatedData['middleName'] = $request->middleName ?? '';  // Asegurar que 'middleName' no sea null
    $validatedData['secondLastName'] = $request->secondLastName ?? '';  // Asegurar que 'secondLastName' no sea null


    // Crear el nuevo nombre de la imagen
    $newImageName = "photo_users-{$created_at}-{$date_of_birth}-{$firstName}-{$lastName}-{$randomDigits}.jpg";

    // Obtener la URL base según el entorno
    $baseUrl = env('APP_ENV') === 'production'
        ? env('APP_URL_PRODUCTION')
        : env('APP_URL_LOCAL');

    // Mantener la URL de la foto anterior (si existe)
    $photo_usersxxx = $profile->photo_users;

    // Actualizar los campos del perfil
    $profile->fill($validatedData);

    // Manejo del archivo (si se sube uno nuevo)
    if ($request->hasFile('photo_users')) {
        // Eliminar la imagen anterior si existe
        if ($profile->photo_users) {
            // Log de la imagen anterior desde la base de datos
            Storage::disk('public')->delete(str_replace($baseUrl . '/storage/', '', $photo_usersxxx));
        } else {
            Log::info('No hay imagen anterior para eliminar.');
        }

        // Guardar la nueva imagen en el disco público
        $path = $request->file('photo_users')->storeAs('profile_images', $newImageName, 'public');
        $profile->photo_users = $baseUrl . '/storage/' . $path;
    }

    // Guardar los cambios en el perfil
    $profile->save();

    return response()->json([
        'message' => 'Perfil actualizado exitosamente.',
        'profile' => $profile,
        'isSuccess' => true
    ], 200);
}


    /**
     * Eliminar un perfil.
     */
    public function destroy($id)
    {
        $profile = Profile::find($id);

        if (!$profile) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        // Eliminar la imagen asociada si existe.
        if ($profile->photo_users) {
            $baseUrl = env('APP_ENV') === 'production'
                ? env('APP_URL_PRODUCTION')
                : env('APP_URL_LOCAL');
            Storage::disk('public')->delete(str_replace($baseUrl . '/storage/', '', $profile->photo_users));
        }

        $profile->delete();

        return response()->json(['message' => 'Perfil eliminado exitosamente']);
    }


// En tu controlador (UserController)
        public function getProfileId($id)
        {

            $profile = Profile::where('user_id', $id)->first();
            if ($profile) {
                return response()->json(['profileId' => $profile->id], 200);
            } else {
                return response()->json(['error' => 'User profile not found'], 404);
            }
        }

    /**
     * Crear un perfil de delivery agent.
     */
    public function createDeliveryAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'maritalStatus' => 'required|in:married,divorced,single',
            'sex' => 'required|in:F,M',
            'vehicle_type' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'company_id' => 'nullable|exists:delivery_companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Verificar si ya existe un perfil para el usuario
        $existingProfile = Profile::where('user_id', $request->user_id)->first();

        if ($existingProfile) {
            return response()->json([
                'message' => 'Ya existe un perfil asociado a este usuario.',
                'profile' => $existingProfile
            ], 409);
        }

        $profileData = $request->only([
            'user_id', 'firstName', 'lastName', 'date_of_birth', 'maritalStatus', 'sex'
        ]);

        $profileData['middleName'] = $request->middleName ?? '';
        $profileData['secondLastName'] = $request->secondLastName ?? '';
        $profileData['status'] = 'notverified';

        // Crear el perfil
        $profile = Profile::create($profileData);

        // Crear el delivery agent asociado
        $deliveryAgentData = [
            'profile_id' => $profile->id,
            'vehicle_type' => $request->vehicle_type,
            'phone' => $request->phone,
            'status' => 'activo',
            'working' => false,
        ];

        // Si se proporciona company_id, agregarlo
        if ($request->has('company_id') && $request->company_id) {
            $deliveryAgentData['company_id'] = $request->company_id;
        }

        $deliveryAgent = \App\Models\DeliveryAgent::create($deliveryAgentData);

        return response()->json([
            'success' => true,
            'message' => 'Delivery agent profile created successfully',
            'data' => [
                'profile' => $profile,
                'delivery_agent' => $deliveryAgent
            ]
        ], 201);
    }

    /**
     * Crear un perfil de commerce.
     */
    public function createCommerce(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'maritalStatus' => 'required|in:married,divorced,single',
            'sex' => 'required|in:F,M',
            'business_name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'is_open' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Verificar si ya existe un perfil para el usuario
        $existingProfile = Profile::where('user_id', $request->user_id)->first();
        if ($existingProfile) {
            return response()->json([
                'message' => 'Ya existe un perfil asociado a este usuario.',
                'profile' => $existingProfile
            ], 409);
        }

        $profileData = $request->only([
            'user_id', 'firstName', 'lastName', 'date_of_birth', 'maritalStatus', 'sex'
        ]);
        $profileData['middleName'] = $request->middleName ?? '';
        $profileData['secondLastName'] = $request->secondLastName ?? '';
        $profileData['status'] = 'notverified';

        // Crear el perfil
        $profile = Profile::create($profileData);

        // Crear el commerce asociado
        $commerce = \App\Models\Commerce::create([
            'profile_id' => $profile->id,
            'business_name' => $request->business_name,
            'description' => $request->description,
            'address' => $request->address,
            'phone' => $request->phone,
            'open' => $request->is_open,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $commerce->id,
                'business_name' => $commerce->business_name,
                'description' => $commerce->description,
                'address' => $commerce->address,
                'phone' => $commerce->phone,
                'open' => $commerce->open,
                'mobile_payment_id' => null, // Agregado para el test
                'mobile_payment_bank' => null, // Agregado para el test
                'mobile_payment_phone' => null // Agregado para el test
            ]
        ], 201);
    }

    /**
     * Crear un perfil de delivery company.
     */
    public function createDeliveryCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'maritalStatus' => 'required|in:married,divorced,single',
            'sex' => 'required|in:F,M',
            'company_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Verificar si ya existe un perfil para el usuario
        $existingProfile = Profile::where('user_id', $request->user_id)->first();

        if ($existingProfile) {
            return response()->json([
                'message' => 'Ya existe un perfil asociado a este usuario.',
                'profile' => $existingProfile
            ], 409);
        }

        $profileData = $request->only([
            'user_id', 'firstName', 'lastName', 'date_of_birth', 'maritalStatus', 'sex'
        ]);

        $profileData['middleName'] = $request->middleName ?? '';
        $profileData['secondLastName'] = $request->secondLastName ?? '';
        $profileData['status'] = 'notverified';

        // Crear el perfil
        $profile = Profile::create($profileData);

        // Crear la delivery company asociada
        $deliveryCompany = \App\Models\DeliveryCompany::create([
            'profile_id' => $profile->id,
            'name' => $request->company_name,
            'tax_id' => $request->ci ?? '00000000000',
            'phone' => $request->phone,
            'address' => $request->address,
            'activo' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery company profile created successfully',
            'data' => [
                'profile' => $profile,
                'delivery_company' => $deliveryCompany
            ]
        ], 201);
    }
}
