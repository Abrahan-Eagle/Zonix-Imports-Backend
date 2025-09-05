<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // TODO: Implementar lógica para mostrar reportes
        return response()->json(['message' => 'Listado de reportes']);
    }
}
