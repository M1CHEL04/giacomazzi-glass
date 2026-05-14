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
        if (!session()->has('user')) {
            return redirect()->route('login-view')->with('error', 'Debes iniciar sesión para acceder.');
        }

        // Verificar si el rol es Admin
        $user = session('user');
        if (!isset($user['Rol']) || $user['Rol'] !== 'Admin') {
            return redirect()->route('uso-interno.home-interno')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
