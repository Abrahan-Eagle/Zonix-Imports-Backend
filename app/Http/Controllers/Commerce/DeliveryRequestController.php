<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryRequestController extends Controller
{
    public function store(Request $request)
    {
        // TODO: Implementar lógica para solicitar delivery
        return response()->json(['message' => 'Solicitud de delivery creada']);
    }
}
