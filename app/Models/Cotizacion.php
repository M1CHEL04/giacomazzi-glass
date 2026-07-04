<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'cantidad_items',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
