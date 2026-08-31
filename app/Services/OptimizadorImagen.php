<?php

namespace App\Services;

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
            $imagen = (new ImageManager(new Driver()))
                ->read($archivo->getRealPath())
                ->orient();

            $imagen->scaleDown(width: self::ANCHO_FULL);

            $full = $imagen->toWebp(self::CALIDAD)->toString();

            $thumb = (clone $imagen)
                ->scaleDown(width: self::ANCHO_THUMB)
                ->toWebp(self::CALIDAD_THUMB)
                ->toString();

            return ['full' => $full, 'thumb' => $thumb];
        } finally {
            unset($imagen);
            ini_set('memory_limit', $limiteOriginal);
        }
    }
}
