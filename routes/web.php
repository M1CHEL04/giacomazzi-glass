<?php

use App\Http\Controllers\UsoInternoController;
use App\Http\Controllers\UsoExternoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('UsoExterno.welcome');
})->name('welcome');

Route::get('/contacto', function () {
    return view('UsoExterno.contacto');
})->name('contacto');

Route::get('/productos/categoria/{id}', [UsoExternoController::class, 'indexCategoria'])->name('productos.categoria');

/*
* 
* Login y Manejo de cuenta 
*
*/
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
Route::prefix('uso-interno')->name('uso-interno.')->middleware(['admin'])->group(function () {

    //Home interno
    Route::get('/panel-interno', function () {
        return view('UsoInterno.index');
    })->name('home-interno');

    //Mi perfil
    Route::get('/mi-perfil', function () {
        return view('UsoInterno.User.myProfile');
    })->name('profile');

    //Categorias
    Route::get('/categorias', [UsoInternoController::class, 'indexCategorias'])->name('categorias.index');
    Route::get('/create-categoria', [UsoInternoController::class, 'createCategoria'])->name('categorias.create');
    Route::get('/edit-categoria/{id}', [UsoInternoController::class, 'editCategoria'])->name('categorias.edit');
    Route::post('/store-categoria', [UsoInternoController::class, 'storeCategoria'])->name('categorias.store');
    Route::post('/update-categoria/{id}', [UsoInternoController::class, 'updateCategoria'])->name('categorias.update');

    //Productos
    Route::get('/productos', [UsoInternoController::class, 'indexProductos'])->name('productos.index');
    Route::get('/show-producto/{id}', [UsoInternoController::class, 'showProducto'])->name('productos.show');
    Route::get('/create-producto', [UsoInternoController::class, 'createProducto'])->name('productos.create');
    Route::get('/edit-producto/{id}', [UsoInternoController::class, 'editProducto'])->name('productos.edit');
    Route::post('/store-producto', [UsoInternoController::class, 'storeProducto'])->name('productos.store');
    Route::post('/update-producto/{id}', [UsoInternoController::class, 'updateProducto'])->name('productos.update');
});
