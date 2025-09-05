<?php

namespace App\Http\Middleware;

use App\Models\Commerce;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCommerceOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $commerce = Commerce::find($request->commerce_id);

        if (!$commerce->abierto) {
            return response()->json(['error' => 'El comercio no está abierto'], 403);
        }

        return $next($request);
    }
}
