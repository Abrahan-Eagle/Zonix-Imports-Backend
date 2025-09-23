<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
 public function index()
    {
        return User::with('profile')->get();
    }

    public function show($id)
    {
        return User::with('profile')->findOrFail($id);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|in:buyer,seller,admin',
        ]);

        $user = User::findOrFail($id);
        if ($user->profile) {
            $user->profile->role = $request->role;
            $user->profile->save();
        }

        return response()->json(['message' => 'Rol actualizado correctamente']);
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'Usuario eliminado']);
    }
}
