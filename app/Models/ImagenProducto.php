<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ImagenProducto extends Model
{
    protected $table = 'imagenes_producto';

    protected $fillable = [
        'producto_id',
        'ruta',
        'ruta_thumb',
        'nombre_imagen',
        'es_principal',
        'es_tecnica',
        'activa',
        'orden',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'es_tecnica'   => 'boolean',
        'activa'       => 'boolean',
        'orden'        => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    protected function rutaMiniatura(): Attribute
    {
        return Attribute::get(fn() => $this->ruta_thumb ?: $this->ruta);
    }
}
