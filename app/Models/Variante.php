<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variante extends Model
{
    protected $table = 'variantes';

    protected $fillable = [
        'nombre',
    ];

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categorias_variantes', 'variante_id', 'categoria_id');
    }

    public function valores()
    {
        return $this->hasMany(ValorVariante::class, 'variante_id');
    }
}
