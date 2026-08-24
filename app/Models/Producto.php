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

    /**
     * Las dos relaciones devuelven sólo imágenes activas.
     *
     * Eliminar una imagen es una baja lógica (activa = false): la fila queda
     * en la tabla con el archivo ya subido. Si la relación las trajera, cada
     * consumidor tendría que acordarse de filtrar por su cuenta —y el que se
     * olvidara contaría imágenes que el usuario ya borró—. Con el filtro acá,
     * lo que se muestra y lo que se cuenta son siempre lo mismo.
     *
     * Para llegar a las dadas de baja hay que ir por ImagenProducto, que es
     * lo que hace el propio borrado.
     */
    public function imagenes()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id')
            ->where('es_tecnica', false)
            ->where('activa', true);
    }

    public function imagenesTecnicas()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id')
            ->where('es_tecnica', true)
            ->where('activa', true);
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class, 'producto_id');
    }
}
