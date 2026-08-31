<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class OptimizadorImagen
{
    /** Ancho máximo de la imagen que se muestra grande (show, lightbox). */
    private const ANCHO_FULL = 1600;

    /** Ancho máximo de la variante para grids y miniaturas. */
    private const ANCHO_THUMB = 600;

    private const CALIDAD = 85;

    /**
     * Techo de memoria mientras dura la conversión, sólo para este servicio.
     *
     * Medido: 8000x6000 (48 MP) pica en 212 MB y 8000x8000 —el tope que deja
     * pasar la validación— en 280 MB. Si se sube el tope de `dimensions` en
     * UsoInternoController hay que volver a medir y ajustar esto.
     */
    private const MEMORIA = '512M';

    private const CALIDAD_THUMB = 80;

    /**
     * @return array{full: string, thumb: string} binarios WebP listos para subir
     */
    public function variantes(UploadedFile $archivo): array
    {
        $limiteOriginal = ini_get('memory_limit');
        ini_set('memory_limit', self::MEMORIA);

        try {
            // Intervention se usa sólo para decodificar y aplicar la orientación
            // EXIF. El resize se hace con imagescale porque su ResizeModifier de
            // GD va por imagecopyresampled: medido sobre un JPEG de 5712x4284,
            // 1348 ms contra 466 ms. Requiere ext-exif para que orient() haga algo.
            $original = (new ImageManager(new Driver()))
                ->read($archivo->getRealPath())
                ->orient()
                ->core()
                ->native();

            $full = $this->escalar($original, self::ANCHO_FULL);

            // El original es el recurso más pesado (una foto de 24 MP son ~100 MB
            // en memoria). Soltarlo acá baja el pico: de la línea de abajo en
            // adelante ya no hace falta. Si la imagen entraba en ANCHO_FULL,
            // escalar() devolvió el mismo recurso y $full lo mantiene vivo.
            unset($original);

            // El thumb sale del full ya reducido, no del original: reescalar de
            // 1600 a 600 cuesta una fracción de partir de la imagen completa.
            $thumb = $this->escalar($full, self::ANCHO_THUMB);

            return [
                'full'  => $this->aWebp($full, self::CALIDAD),
                'thumb' => $this->aWebp($thumb, self::CALIDAD_THUMB),
            ];
        } finally {
            // Los GdImage se liberan solos al salir del scope (refcount);
            // imagedestroy() está deprecada desde PHP 8 y no hace falta.
            ini_set('memory_limit', $limiteOriginal);
        }
    }

    /**
     * Reduce a lo ancho manteniendo la proporción. Nunca agranda: si ya entra,
     * devuelve el mismo recurso.
     */
    private function escalar(GdImage $imagen, int $ancho): GdImage
    {
        if (imagesx($imagen) <= $ancho) {
            return $imagen;
        }

        // IMG_BICUBIC y no el filtro por defecto (IMG_BILINEAR_FIXED): en un
        // catálogo de producto la reducción es grande y el bilineal aliasa.
        $escalada = imagescale($imagen, $ancho, -1, IMG_BICUBIC);

        if ($escalada === false) {
            throw new \RuntimeException('No se pudo redimensionar la imagen a ' . $ancho . 'px.');
        }

        return $escalada;
    }

    /** Codifica a WebP en memoria, preservando la transparencia si la había. */
    private function aWebp(GdImage $imagen, int $calidad): string
    {
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);

        ob_start();
        $ok = imagewebp($imagen, null, $calidad);
        $binario = (string) ob_get_clean();

        if ($ok === false || $binario === '') {
            throw new \RuntimeException('No se pudo codificar la imagen a WebP.');
        }

        return $binario;
    }
}
