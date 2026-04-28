<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Session\Session;

class AccountsController extends Controller
{
    public function storeUser(Request $request)
    {
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
    }

    public function login(Request $request)
    {
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
                return redirect()->route('home-interno')->with('success', 'Bienvenido, ' . $user->name . '!');
            } else {
                return redirect()->route('change-password-view')->with('success', 'Bienvenido, ' . $user->name . '! Por favor, cambia tu contraseña.');
            }
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome')->with('success', 'Has cerrado sesión exitosamente.');
    }

    public function showChangePasswordForm()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
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


        return redirect()->route('home-interno')->with('success', 'Contraseña cambiada exitosamente.');
    }
}
