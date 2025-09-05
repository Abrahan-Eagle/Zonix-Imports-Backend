<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commerce;
use Illuminate\Http\Request;

class CommerceController extends Controller
{
 public function index()
    {
        return Commerce::with('user')->get();
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'activo' => 'required|boolean',
        ]);

        $commerce = Commerce::findOrFail($id);
        $commerce->activo = $request->activo;
        $commerce->save();

        return response()->json(['message' => 'Estado del comercio actualizado']);
    }
}
