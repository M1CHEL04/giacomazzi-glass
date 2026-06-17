<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\UsoExternoController;
use App\Http\Controllers\UsoInternoController;
use Illuminate\Support\Facades\Route;

// ── Uso Externo ──────────────────────────────────────────────────────────────
Route::get('/',         [UsoExternoController::class, 'welcome'])->name('welcome');
Route::get('/contacto', [UsoExternoController::class, 'contacto'])->name('contacto');
Route::get('/productos',                [UsoExternoController::class, 'indexTodos'])->name('productos.todos');
Route::get('/productos/categoria/{id}', [UsoExternoController::class, 'indexCategoria'])->name('productos.categoria');
Route::get('/productos/{id}',           [UsoExternoController::class, 'showProducto'])->whereNumber('id')->name('productos.show');

// ── Carrito ───────────────────────────────────────────────────────────────────
Route::get('/carrito',           [CarritoController::class, 'obtener'])->name('carrito.obtener');
Route::post('/carrito/agregar',  [CarritoController::class, 'agregar'])->name('carrito.agregar');
Route::post('/carrito/eliminar', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');
Route::post('/carrito/vaciar',   [CarritoController::class, 'vaciar'])->name('carrito.vaciar');

// ── Autenticación ─────────────────────────────────────────────────────────────
Route::get('/login',              [AccountsController::class, 'loginView'])->name('login-view');
Route::post('/login-form',        [AccountsController::class, 'login'])->name('login');
Route::post('/logout',            [AccountsController::class, 'logout'])->name('logout');
Route::post('/cambiar-contrasena-form', [AccountsController::class, 'changePassword'])->name('change-password');
Route::get('/cambiar-contraseña', [AccountsController::class, 'changePasswordView'])->name('change-password-view');
Route::get('/olvido-contrasena',  [AccountsController::class, 'forgotPasswordView'])->name('forgot-password-view');
Route::post('/enviar-codigo-verificacion', [AccountsController::class, 'sendVerifyCode'])->name('send-verify-code');
Route::post('/verificar-codigo',           [AccountsController::class, 'verifyCode'])->name('verify-code');
Route::post('/cambiar-contrasena-codigo',  [AccountsController::class, 'changePasswordAfterCode'])->name('change-password-after-code');

// ── Uso Interno ───────────────────────────────────────────────────────────────
Route::prefix('uso-interno')->name('uso-interno.')->middleware(['admin'])->group(function () {

    Route::get('/panel-interno', [UsoInternoController::class, 'homeInterno'])->name('home-interno');
    Route::get('/mi-perfil',     [UsoInternoController::class, 'miPerfil'])->name('profile');

    // Categorías
    Route::get('/categorias',          [UsoInternoController::class, 'indexCategorias'])->name('categorias.index');
    Route::get('/create-categoria',    [UsoInternoController::class, 'createCategoria'])->name('categorias.create');
    Route::get('/edit-categoria/{id}', [UsoInternoController::class, 'editCategoria'])->name('categorias.edit');
    Route::post('/store-categoria',    [UsoInternoController::class, 'storeCategoria'])->name('categorias.store');
    Route::post('/update-categoria/{id}', [UsoInternoController::class, 'updateCategoria'])->name('categorias.update');

    // Productos
    Route::get('/productos',           [UsoInternoController::class, 'indexProductos'])->name('productos.index');
    Route::get('/show-producto/{id}',  [UsoInternoController::class, 'showProducto'])->name('productos.show');
    Route::get('/create-producto',     [UsoInternoController::class, 'createProducto'])->name('productos.create');
    Route::get('/edit-producto/{id}',  [UsoInternoController::class, 'editProducto'])->name('productos.edit');
    Route::post('/store-producto',     [UsoInternoController::class, 'storeProducto'])->name('productos.store');
    Route::post('/update-producto/{id}', [UsoInternoController::class, 'updateProducto'])->name('productos.update');
});
