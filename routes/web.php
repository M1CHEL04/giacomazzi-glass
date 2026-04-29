<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/login', function () {
    return view('UsoInterno.User.login');
})->name('login-view');

Route::post('/login-form', [\App\Http\Controllers\AccountsController::class, 'login'])->name('login');
Route::post('/logout', [\App\Http\Controllers\AccountsController::class, 'logout'])->name('logout');
Route::post('/cambiar-contrasena-form', [\App\Http\Controllers\AccountsController::class, 'changePassword'])->name('change-password');
Route::get('/cambiar-contraseña', function () {
    return view('UsoInterno.User.changePassword');
})->name('change-password-view');
Route::get('/olvido-contrasena', function () {
    return view('UsoInterno.User.forgotPassword');
})->name('forgot-password-view');
Route::post('/enviar-codigo-verificacion', [\App\Http\Controllers\AccountsController::class, 'sendVerifyCode'])
    ->name('send-verify-code');
Route::post('/verificar-codigo', [\App\Http\Controllers\AccountsController::class, 'verifyCode'])
    ->name('verify-code');
Route::post('/cambiar-contrasena-codigo', [\App\Http\Controllers\AccountsController::class, 'changePasswordAfterCode'])
    ->name('change-password-after-code');


/*
* 
* Uso Interno
*
*/
Route::get('/panel-interno', function () {
    return view('UsoInterno.index');
})->name('home-interno');
Route::get('/mi-perfil', function () {
    return view('UsoInterno.User.myProfile');
})->name('profile');
