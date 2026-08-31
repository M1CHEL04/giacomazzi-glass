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

    private const CALIDAD_THUMB = 80;

    /**
     * @return array{full: string, thumb: string} binarios WebP listos para subir
     */
    public function variantes(UploadedFile $archivo): array
    {
        // Se lee desde el path temporal y no con getContent(): así el archivo no
        // queda además entero en memoria al lado del bitmap descomprimido.
        // orient() aplica el EXIF de rotación — las fotos de celular vienen acostadas.
        $imagen = (new ImageManager(new Driver()))
            ->read($archivo->getRealPath())
            ->orient();

        try {
            $imagen->scaleDown(width: self::ANCHO_FULL);

            $full = $imagen->toWebp(self::CALIDAD)->toString();

            $thumb = (clone $imagen)
                ->scaleDown(width: self::ANCHO_THUMB)
                ->toWebp(self::CALIDAD_THUMB)
                ->toString();

            return ['full' => $full, 'thumb' => $thumb];
        } finally {

            unset($imagen);
        }
    }
}
