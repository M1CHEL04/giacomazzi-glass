<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    /** `descripcion` es un varchar(255); `descripcion_tecnica` es un TEXT. */
    public const MAX_DESCRIPCION = 255;
    public const MAX_DESCRIPCION_TECNICA = 5000;

    protected $fillable = [
        'categoria_id',
        'unidad_id',
        'nombre',
        'descripcion',
        'descripcion_tecnica',
        'codigo',
        'activo',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function unidad()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_id');
    }

    public function valoresVariantes()
    {
        return $this->belongsToMany(ValorVariante::class, 'productos_valores_variantes', 'producto_id', 'valor_variante_id');
    }

    public function imagenes()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id');
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class, 'producto_id');
    }
}
