<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index()
    {
        try {
            $banks = Bank::where('is_active', true)->orderBy('name')->get();
            return response()->json(['success' => true, 'data' => $banks]);
        } catch (\Exception $e) {
            \Log::error('Error al listar bancos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno al listar bancos'], 500);
        }
    }
} 