<?php

use App\Http\Controllers\UsoInternoController;
use Illuminate\Support\Facades\Route;

Route::get('/categorias/{id}/variantes', [UsoInternoController::class, 'getVariantesByCategoria'])
    ->name('api.categorias.variantes');

Route::post('/categorias', [UsoInternoController::class, 'storeCategoriaJson'])
    ->name('api.categorias.store');
