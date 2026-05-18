<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario tiene sesión activa
        if (!session()->has('user_email')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            return redirect()->route('login-view')->with('error', 'Debes iniciar sesión para acceder.');
        }

        if (!session()->has('rol') || session('rol') !== 'Admin') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Sin permisos'], 403);
            }
            return redirect()->route('uso-interno.home-interno')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
