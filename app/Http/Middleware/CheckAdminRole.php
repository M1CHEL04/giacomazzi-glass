<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            return redirect()->route('login-view')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        // La sesión puede haber quedado huérfana (usuario borrado) — forzar login.
        $user = Auth::user() ?? User::where('email', session('user_email'))->first();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            return redirect()->route('login-view')->with('error', 'Debes iniciar sesión para acceder.');
        }

        if (!$user->cambio_contraseña) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Debes cambiar tu contraseña antes de continuar.'], 403);
            }
            return redirect()->route('change-password-view')
                ->with('error', 'Debés cambiar tu contraseña antes de continuar.');
        }

        return $next($request);
    }
}
