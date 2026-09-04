<?php

namespace App\Models;

/**
 * Producto a medida.
 *
 * Vive en la misma tabla que Producto —de ahí la herencia— porque comparte
 * todo: categoría, imágenes de galería y técnicas, y la baja lógica por
 * `activo`. Lo que cambia es que no se cotiza (no tiene unidad ni variantes)
 * y que se consulta por WhatsApp, así que tiene su propio CRUD y su propia
 * ficha pública.
 *
 * El scope global es la garantía de que ninguna consulta de especiales
 * devuelva un estándar por olvido; el evento de creación es lo que hace que
 * el flag no dependa de que el controlador se acuerde de mandarlo.
 */
class ProductoEspecial extends Producto
{
    /**
     * No llama a parent::booted() a propósito: los scopes globales se guardan
     * por clase, así que este modelo arranca sólo con el suyo.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('especial', fn ($query) => $query->where('es_especial', true));

        static::creating(function ($producto) {
            $producto->es_especial = true;
        });
    }
}
