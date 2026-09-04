<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Reemplaza a la regla de imagen "image|mimes:...|max:...|dimensions:..." con
 * una que arma el mensaje de error con el nombre original del archivo: con 5
 * imágenes en un mismo request, el mensaje genérico no permite saber cuál de
 * todas hay que corregir.
 *
 * Los topes son los mismos que tenía GestorImagenesProducto::REGLAS_IMAGEN:
 * 5 MB de peso y 8000x8000 px, este último por el consumo de memoria de GD
 * al decodificar (ver comentario en OptimizadorImagen).
 */
class ImagenProductoValida implements ValidationRule
{
    private const MIMES_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp'];

    private const PESO_MAX_KB = 5120;

    private const LADO_MAX_PX = 8000;

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('Cada archivo debe ser una imagen.');
            return;
        }

        $nombre = $value->getClientOriginalName();

        if (!$value->isValid() || !in_array($value->getMimeType(), self::MIMES_PERMITIDOS, true)) {
            $fail("«{$nombre}» no es una imagen válida: debe ser JPG, PNG o WebP.");
            return;
        }

        if ($value->getSize() > self::PESO_MAX_KB * 1024) {
            $pesoMb = round($value->getSize() / 1024 / 1024, 1);
            $fail("«{$nombre}» pesa {$pesoMb} MB; el máximo permitido es 5 MB.");
            return;
        }

        $dimensiones = @getimagesize($value->getRealPath());
        if ($dimensiones && ($dimensiones[0] > self::LADO_MAX_PX || $dimensiones[1] > self::LADO_MAX_PX)) {
            $fail("«{$nombre}» mide {$dimensiones[0]}x{$dimensiones[1]} px; el máximo permitido es "
                . self::LADO_MAX_PX . 'x' . self::LADO_MAX_PX . ' px.');
        }
    }
}
