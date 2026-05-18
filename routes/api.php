<?php

use App\Http\Controllers\UsoInternoController;
use Illuminate\Support\Facades\Route;

Route::get('/categorias/{id}/variantes', [UsoInternoController::class, 'getVariantesByCategoria'])
    ->name('api.categorias.variantes');
