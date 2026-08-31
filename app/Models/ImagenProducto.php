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
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * URL a usar donde la imagen se muestra chica (grids, miniaturas).
     *
     * Centraliza el fallback a la imagen grande para que ninguna vista tenga
     * que repetirlo: sin thumb la página se ve igual, sólo pesa más.
     *
     * Ojo: si la consulta no seleccionó `ruta_thumb`, esto devuelve `ruta` sin
     * avisar y se pierde la optimización. Los select() de UsoExternoController
     * la incluyen por eso.
     */
    protected function rutaMiniatura(): Attribute
    {
        return Attribute::get(fn() => $this->ruta_thumb ?: $this->ruta);
    }
}
