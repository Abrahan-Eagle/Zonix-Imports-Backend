<?php

namespace App\Http\Controllers\Authenticator;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Maneja la autenticación de usuario usando Google.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleUser(Request $request)
    {
        // Validación de los datos de entrada
        $validator = Validator::make($request->all(), [
            'token' => 'nullable|string',
            'data' => 'nullable|array',
            'data.sub' => 'nullable|string',
            'data.name' => 'nullable|string',
            'data.given_name' => 'nullable|string',
            'data.family_name' => 'nullable|string',
            'data.picture' => 'nullable|url',
            'data.email' => 'nullable|email',
            'data.email_verified' => 'nullable|boolean',
            // Campos alternativos para tests
            'google_id' => 'nullable|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string',
        ]);

        // Si la validación falla, devolver un error
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Extraer los datos del request validado
            $validatedData = $validator->validated();
            
            // Manejar diferentes formatos de datos
            if (isset($validatedData['data'])) {
                $data = $validatedData['data'];
                $googleId = $data['sub'] ?? null;
                $email = $data['email'] ?? null;
                $name = $data['name'] ?? null;
            } else {
                // Formato alternativo para tests
                $googleId = $validatedData['google_id'] ?? null;
                $email = $validatedData['email'] ?? null;
                $name = $validatedData['name'] ?? null;
            }

            if (!$email) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email is required'
                ], 422);
            }

            // Buscar o crear el usuario con la información proporcionada
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name ?? 'User',
                    'google_id' => $googleId,
                    'given_name' => $validatedData['data']['given_name'] ?? null,
                    'family_name' => $validatedData['data']['family_name'] ?? null,
                    'profile_pic' => $validatedData['data']['picture'] ?? null,
                    'completed_onboarding' => false,
                ]
            );

            // Si el usuario ya existe, actualizar información si es necesario
            if ($user->wasRecentlyCreated === false && $name) {
                $user->update(['name' => $name]);
            }

            // Crear el token de Sanctum
            $token = $user->createToken('GoogleToken')->plainTextToken;

            // Responder con los datos del usuario y el token
            return response()->json([
                'success' => true,
                'message' => 'User authenticated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'profile_pic' => $user->profile_pic,
                        'completed_onboarding' => $user->completed_onboarding,
                    ],
                    'token' => $token
                ]
            ], 200);
        } catch (\Throwable $th) {
            // Manejo de errores en caso de excepciones
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Cierra la sesión del usuario autenticado.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Invalidar todos los tokens del usuario
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'User logged out successfully'
        ]);
    }

    /**
     * Devuelve la información del usuario autenticado.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'google_id' => $user->google_id,
                'completed_onboarding' => $user->completed_onboarding,
                'created_at' => $user->created_at->toISOString(),
            ]
        ]);
    }


     // Actualizar una user el campo  completed_onboarding
     public function update(Request $request, $id)
     {
         // Busca al usuario por ID y verifica si existe
         $user = User::find($id);

         if (!$user) {
             return response()->json(['error' => 'Usuario no encontrado'], 404);
         }

         // Valida el campo 'completed_onboarding' como booleano
         $validated = $request->validate([
             'completed_onboarding' => 'required|boolean',
         ]);

         // Actualiza el usuario con el campo validado
         $user->update($validated);

         return response()->json($user, 200);
     }

    /**
     * Registra un nuevo usuario
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:users,commerce,delivery,admin',
            'google_id' => 'nullable|string'
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'completed_onboarding' => false,
        ];

        // Solo agregar password si se proporciona
        if ($request->password) {
            $userData['password'] = bcrypt($request->password);
        }

        // Agregar google_id si se proporciona
        if ($request->google_id) {
            $userData['google_id'] = $request->google_id;
        }

        $user = User::create($userData);

        $token = $user->createToken('AuthToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'completed_onboarding' => $user->completed_onboarding,
                    'created_at' => $user->created_at->toISOString(),
                ],
                'token' => $token
            ]
        ], 201);
    }

    /**
     * Login con email y password
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('AuthToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User logged in successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'completed_onboarding' => $user->completed_onboarding,
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Actualiza el perfil del usuario
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,' . $user->id,
            'profile_pic' => 'nullable|url'
        ]);

        $user->update($request->only(['name', 'email', 'profile_pic']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile_pic' => $user->profile_pic,
                'completed_onboarding' => $user->completed_onboarding,
            ]
        ]);
    }

    /**
     * Cambia la contraseña del usuario
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        $user = $request->user();

        if (!password_verify($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'current_password' => ['The current password field is incorrect.']
                ]
            ], 422);
        }

        $user->update([
            'password' => bcrypt($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Refresca el token del usuario
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        
        // Revocar token actual
        $user->tokens()->delete();
        
        // Crear nuevo token
        $token = $user->createToken('AuthToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token
            ]
        ]);
    }

}
