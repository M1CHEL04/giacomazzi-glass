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

    /**
     * Estándar y especial conviven en la misma tabla: el catálogo cotizable
     * son los estándar, y hay que decirlo explícitamente en cada consulta que
     * termine en el carrito, en las variantes o en el panel de productos.
     *
     * Las consultas que muestran ambos tipos (el index de /productos) no usan
     * ninguno de los dos scopes; las que sólo quieren especiales van por
     * ProductoEspecial, que ya los filtra con un scope global.
     */
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
