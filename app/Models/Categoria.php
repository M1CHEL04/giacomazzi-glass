<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'activo',
        'imagen_hero',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    public function variantes()
    {
        return $this->belongsToMany(Variante::class, 'categorias_variantes', 'categoria_id', 'variante_id');
    }
}
