<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // TODO: Implementar lógica para el dashboard de comercio
        return response()->json(['message' => 'Dashboard de comercio']);
    }
}
