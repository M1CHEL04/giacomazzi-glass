<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Session\Session;

class AccountsController extends Controller
{
    public function loginView()
    {
        return view('UsoInterno.User.login');
    }

    public function changePasswordView()
    {
        return view('UsoInterno.User.changePassword');
    }

    public function forgotPasswordView()
    {
        return view('UsoInterno.User.forgotPassword');
    }

    public function storeUser(Request $request)
    {
        try {
            // Validar los datos de entrada
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.string' => 'El nombre debe ser un texto válido.',
                'name.max' => 'El nombre no puede superar los 255 caracteres.',
                'email.required' => 'El correo es obligatorio.',
                'email.string' => 'El correo debe ser un texto válido.',
                'email.email' => 'El correo debe tener un formato válido.',
                'email.max' => 'El correo no puede superar los 255 caracteres.',
                'email.unique' => 'El correo ya está registrado.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.string' => 'La contraseña debe ser un texto válido.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            ]);

            // Crear el nuevo usuario
            $user = \App\Models\User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // Redirigir o devolver una respuesta
            return redirect()->route('home')->with('success', 'Usuario creado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear usuario: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error al crear usuario.'], 500);
            }
            return back()->with('error', 'Error al crear usuario.');
        }
    }

    public function login(Request $request)
    {
        try {
            // Validar los datos de entrada
            $credentials = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ], [
                'email.required' => 'El correo es obligatorio.',
                'email.string' => 'El correo debe ser un texto válido.',
                'email.email' => 'El correo debe tener un formato válido.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.string' => 'La contraseña debe ser un texto válido.',
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || !Hash::check($request->password, $user->password)) {
                return back()->with(
                    'error',
                    'Las credenciales no son correctas.',
                );
            } else {
                Auth::login($user);
                $request->session()->regenerate();

                session([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'rol' => 'Admin'
                ]);

                if ($user->cambio_contraseña) {
                    return redirect()->route('uso-interno.home-interno')->with('success', 'Bienvenido/a, ' . $user->name . '!');
                } else {
                    return redirect()->route('change-password-view')->with('success', 'Bienvenido, ' . $user->name . '! Por favor, cambia tu contraseña.');
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al iniciar sesion: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error al iniciar sesión.'], 500);
            }
            return back()->with('error', 'Error al iniciar sesión.');
        }
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('welcome')->with('success', 'Has cerrado sesión exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al cerrar sesion: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error al cerrar sesión.'], 500);
            }
            return back()->with('error', 'Error al cerrar sesión.');
        }
    }

    public function showChangePasswordForm()
    {
        try {
            return view('auth.change-password');
        } catch (\Exception $e) {
            Log::error('Error al mostrar formulario de cambio de contraseña: ' . $e->getMessage());
            return back()->with('error', 'Error al mostrar el formulario.');
        }
    }

    public function changePassword(Request $request)
    {
        try {
            // Validar los datos de entrada
            $validatedData = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ], [
                'current_password.required' => 'La contraseña actual es obligatoria.',
                'current_password.string' => 'La contraseña actual debe ser un texto válido.',
                'new_password.required' => 'La nueva contraseña es obligatoria.',
                'new_password.string' => 'La nueva contraseña debe ser un texto válido.',
                'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
                'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            ]);

            $user = User::where('email', session('user_email'))->first();

            if (!$user || !Hash::check($validatedData['current_password'], $user->password)) {
                return back()->with('error', 'La contraseña actual no es correcta.');
            }

            $user->password = Hash::make($validatedData['new_password']);
            $user->cambio_contraseña = true;
            $user->save();

            return redirect()->route('uso-interno.home-interno')->with('success', 'Contraseña cambiada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al cambiar contraseña: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error al cambiar contraseña.'], 500);
            }
            return back()->with('error', 'Error al cambiar contraseña.');
        }
    }

    public function sendVerifyCode()
    {
        try {
            $validatedData = request()->validate([
                'email' => 'required|string|email',
            ], [
                'email.required' => 'El correo es obligatorio.',
                'email.string' => 'El correo debe ser un texto valido.',
                'email.email' => 'El correo debe tener un formato valido.',
            ]);

            $user = User::where('email', $validatedData['email'])->first();

            if (!$user) {
                Log::warning('Intento de recuperación de contraseña para un correo no registrado: ' . $validatedData['email']);
                if (request()->expectsJson()) {
                    return response()->json(['message' => 'Usuario no encontrado.'], 404);
                }
                return back()->with('error', 'Usuario no encontrado.');
            }

            $verificationCode = rand(100000, 999999);
            $user->verification_code = Hash::make($verificationCode);
            $user->save();

            request()->session()->put('user_email', $user->email);

            Mail::to($user->email)->send(new \App\Mail\RecuperacionCodigo($verificationCode));

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Código de verificación enviado a tu correo.']);
            }

            return back()->with('success', 'Código de verificación enviado a tu correo.');
        } catch (\Exception $e) {
            Log::error('Error al enviar el correo de recuperación: ' . $e->getMessage());
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Error al enviar el correo.'], 500);
            }
            return back()->with('error', 'Error al enviar el correo.');
        }
    }

    public function verifyCode(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'verification_code' => 'required|string',
            ], [
                'verification_code.required' => 'El código de verificación es obligatorio.',
                'verification_code.string' => 'El código de verificación debe ser un texto válido.',
            ]);

            $user = User::where('email', session('user_email'))->first();

            if (!$user || !Hash::check($validatedData['verification_code'], $user->verification_code)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'El código de verificación no es correcto.'], 422);
                }
                return back()->with('error', 'El código de verificación no es correcto.');
            }

            $user->verification_code = null;
            $user->save();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Código de verificación correcto.']);
            }

            return redirect()->route('uso-interno.home-interno')->with('success', 'Código de verificación correcto. Bienvenido, ' . $user->name . '!');
        } catch (\Exception $e) {
            Log::error('Error al verificar código: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error al verificar código.'], 500);
            }
            return back()->with('error', 'Error al verificar código.');
        }
    }

    public function changePasswordAfterCode(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'new_password' => 'required|string|min:8|confirmed',
            ], [
                'new_password.required' => 'La nueva contraseña es obligatoria.',
                'new_password.string' => 'La nueva contraseña debe ser un texto válido.',
                'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
                'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            ]);

            $user = User::where('email', session('user_email'))->first();

            if (!$user) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Usuario no encontrado.'], 404);
                }
                return back()->with('error', 'Usuario no encontrado.');
            }

            $user->password = Hash::make($validatedData['new_password']);
            $user->cambio_contraseña = true;
            $user->save();

            $successMessage = 'Contraseña cambiada exitosamente.';

            if ($request->expectsJson()) {
                $request->session()->flash('success', $successMessage);
                return response()->json([
                    'message' => $successMessage,
                    'redirect_url' => route('login-view'),
                ]);
            }

            return redirect()->route('login-view')->with('success', $successMessage);
        } catch (\Exception $e) {
            Log::error('Error al cambiar contraseña con código: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Error al cambiar contraseña.'], 500);
            }
            return back()->with('error', 'Error al cambiar contraseña.');
        }
    }
}
