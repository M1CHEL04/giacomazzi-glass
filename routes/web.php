<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\UsoExternoController;
use App\Http\Controllers\UsoInternoController;
use App\Http\Controllers\UsoInternoEspecialesController;
use Illuminate\Support\Facades\Route;

// ── Imágenes (file server) ───────────────────────────────────────────────────
Route::get('/imagen/{imagenProducto}', [ImagenController::class, 'show'])->name('imagen.show');

// ── Uso Externo ──────────────────────────────────────────────────────────────
Route::get('/',         [UsoExternoController::class, 'welcome'])->name('welcome');
Route::get('/nosotros', [UsoExternoController::class, 'nosotros'])->name('nosotros');
// Contacto se unificó dentro de Nosotros. La ruta sobrevive como redirect
// porque route('contacto') se usa en varias vistas y en links ya publicados;
// el ancla deja al visitante justo en el mapa y las direcciones.
Route::redirect('/contacto', '/nosotros#donde-estamos')->name('contacto');
Route::get('/productos',                [UsoExternoController::class, 'indexTodos'])->name('productos.todos');
Route::get('/productos/categoria/{id}', [UsoExternoController::class, 'indexCategoria'])->name('productos.categoria');
// Productos a medida: ficha propia, sin carrito. No chocan con /productos/{id}
// porque esa ruta sólo acepta un número.
Route::get('/productos/especiales',                [UsoExternoController::class, 'indexEspeciales'])->name('productos.especiales');
Route::get('/productos/especiales/categoria/{id}', [UsoExternoController::class, 'indexEspecialesCategoria'])->whereNumber('id')->name('productos.especial.categoria');
Route::get('/productos/especiales/{id}',           [UsoExternoController::class, 'showEspecial'])->whereNumber('id')->name('productos.especial.show');
Route::get('/productos/{id}',           [UsoExternoController::class, 'showProducto'])->whereNumber('id')->name('productos.show');

// ── Carrito ───────────────────────────────────────────────────────────────────
Route::get('/carrito',           [CarritoController::class, 'obtener'])->name('carrito.obtener');
Route::post('/carrito/agregar',  [CarritoController::class, 'agregar'])->name('carrito.agregar');
Route::post('/carrito/eliminar', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');
Route::post('/carrito/cantidad', [CarritoController::class, 'actualizarCantidad'])->name('carrito.cantidad');
Route::post('/carrito/vaciar',   [CarritoController::class, 'vaciar'])->name('carrito.vaciar');
Route::post('/carrito/cotizar',  [CarritoController::class, 'cotizar'])->name('carrito.cotizar');

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

    Route::get('/estadisticas', [UsoInternoController::class, 'estadisticas'])->name('estadisticas');
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
    // El id viaja en el body (hidden del modal), no en la URL.
    Route::post('/activar-producto',    [UsoInternoController::class, 'activarProducto'])->name('productos.activar');
    Route::post('/desactivar-producto', [UsoInternoController::class, 'desactivarProducto'])->name('productos.desactivar');

    // Productos especiales (a medida). El prefijo de nombre es `especiales.` y
    // no `productos.` a propósito: si no, el routeIs del sidebar los mezcla.
    Route::get('/productos-especiales',          [UsoInternoEspecialesController::class, 'index'])->name('especiales.index');
    Route::get('/show-producto-especial/{id}',   [UsoInternoEspecialesController::class, 'show'])->name('especiales.show');
    Route::get('/create-producto-especial',      [UsoInternoEspecialesController::class, 'create'])->name('especiales.create');
    Route::get('/edit-producto-especial/{id}',   [UsoInternoEspecialesController::class, 'edit'])->name('especiales.edit');
    Route::post('/store-producto-especial',      [UsoInternoEspecialesController::class, 'store'])->name('especiales.store');
    Route::post('/update-producto-especial/{id}', [UsoInternoEspecialesController::class, 'update'])->name('especiales.update');
    Route::post('/activar-producto-especial',    [UsoInternoEspecialesController::class, 'activar'])->name('especiales.activar');
    Route::post('/desactivar-producto-especial', [UsoInternoEspecialesController::class, 'desactivar'])->name('especiales.desactivar');
});
