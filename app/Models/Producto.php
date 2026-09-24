<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    public const MAX_DESCRIPCION = 255;
    public const MAX_DESCRIPCION_TECNICA = 5000;

    public const MAX_IMAGENES = 5;
    public const MAX_IMAGENES_TECNICAS = 5;

    protected $fillable = [
        'categoria_id',
        'unidad_id',
        'nombre',
        'descripcion',
        'descripcion_tecnica',
        'codigo',
        'activo',
        'es_especial',
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'es_especial' => 'boolean',
    ];

    public function scopeEstandar($query)
    {
        return $query->where('es_especial', false);
    }

    public function scopeEspecial($query)
    {
        return $query->where('es_especial', true);
    }

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
        return $this->hasMany(ImagenProducto::class, 'producto_id')
            ->where('es_tecnica', false)
            ->where('activa', true)
            ->orderBy('orden')
            ->orderBy('id');
    }

    public function imagenesTecnicas()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id')
            ->where('es_tecnica', true)
            ->where('activa', true)
            ->orderBy('id');
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class, 'producto_id');
    }
}
