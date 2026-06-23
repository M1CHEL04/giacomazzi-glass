<?php

namespace App\Http\Controllers;

use App\Models\ImagenProducto;
use Illuminate\Http\RedirectResponse;

class ImagenController extends Controller
{
    public function show(ImagenProducto $imagenProducto): RedirectResponse
    {
        abort_if(!$imagenProducto->activa || empty($imagenProducto->ruta), 404);

        return redirect($imagenProducto->ruta, 301);
    }
}
