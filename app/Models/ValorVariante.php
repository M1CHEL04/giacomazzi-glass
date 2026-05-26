<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValorVariante extends Model
{
    protected $table = 'valores_variante';

    protected $fillable = [
        'variante_id',
        'valor',
        'codigo',
    ];

    public function variante()
    {
        return $this->belongsTo(Variante::class, 'variante_id');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'productos_valores_variantes', 'valor_variante_id', 'producto_id');
    }
}
