<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    protected $table = 'unidades_medida';

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'requiere_alto',
        'requiere_ancho',
    ];

    protected $casts = [
        'requiere_alto'  => 'boolean',
        'requiere_ancho' => 'boolean',
    ];

    /** Se cotiza por superficie (m²): necesita alto y ancho. */
    public function esSuperficie(): bool
    {
        return $this->requiere_alto && $this->requiere_ancho;
    }

    /** Se cotiza por medida (lineal o superficie), no por pieza. */
    public function requiereMedidas(): bool
    {
        return $this->requiere_alto || $this->requiere_ancho;
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'unidad_id');
    }
}
