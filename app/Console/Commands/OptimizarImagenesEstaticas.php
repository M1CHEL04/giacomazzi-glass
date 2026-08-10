<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Genera variantes WebP redimensionadas de las imágenes estáticas de public/images.
 *
 * Las vistas no referencian estos archivos directamente: usan los helpers
 * imagen_src()/imagen_srcset() (app/helpers.php), que derivan el nombre de la
 * variante por convención. Por eso el comando es re-ejecutable y no toca la base.
 */
class OptimizarImagenesEstaticas extends Command
{
    protected $signature = 'images:optimizar
        {--anchos=800,1400,2400 : Anchos a generar, separados por coma}
        {--calidad=78 : Calidad WebP (0-100)}
        {--force : Regenerar variantes que ya existen}
        {--eliminar-originales : Borrar el archivo original tras generar las variantes}';

    protected $description = 'Redimensiona y convierte a WebP las imágenes estáticas de public/images';

    private const EXTENSIONES = ['jpg', 'jpeg', 'png'];

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('GD no tiene soporte WebP en este PHP. Recompilá GD con --with-webp.');

            return self::FAILURE;
        }

        $anchos = array_values(array_filter(array_map(
            'intval',
            explode(',', (string) $this->option('anchos'))
        )));

        if (empty($anchos)) {
            $this->error('El parámetro --anchos no tiene valores válidos.');

            return self::FAILURE;
        }

        sort($anchos);

        $calidad  = max(1, min(100, (int) $this->option('calidad')));
        $manager  = new ImageManager(new Driver());
        $origenes = $this->buscarOriginales();

        if (empty($origenes)) {
            $this->info('No se encontraron imágenes para optimizar en public/images.');

            return self::SUCCESS;
        }

        $filas       = [];
        $pesoAntes   = 0;
        $pesoDespues = 0;
        $fallidas    = 0;

        foreach ($origenes as $origen) {
            $original = filesize($origen);
            $generado = 0;
            $creadas  = 0;

            try {
                $imagen = $manager->read($origen)->orient();
            } catch (\Throwable $e) {
                $this->warn('No se pudo leer ' . $this->relativa($origen) . ': ' . $e->getMessage());
                $fallidas++;
                continue;
            }

            $anchoOriginal = $imagen->width();

            // Nunca se agranda: sólo anchos que el original puede cubrir de verdad.
            // Si el descriptor `800w` del srcset no coincide con el ancho real del
            // archivo, el navegador elige mal en pantallas retina.
            $objetivos = array_values(array_filter($anchos, fn ($a) => $a <= $anchoOriginal));

            if (empty($objetivos)) {
                $objetivos = [$anchoOriginal];
            }

            foreach ($objetivos as $ancho) {
                $destino = $this->rutaVariante($origen, $ancho);

                if (is_file($destino) && ! $this->option('force')) {
                    $generado += filesize($destino);
                    continue;
                }

                try {
                    (clone $imagen)->scaleDown(width: $ancho)->toWebp($calidad)->save($destino);
                } catch (\Throwable $e) {
                    $this->warn('Falló ' . $this->relativa($destino) . ': ' . $e->getMessage());
                    $fallidas++;
                    continue;
                }

                $generado += filesize($destino);
                $creadas++;
            }

            $pesoAntes   += $original;
            $pesoDespues += $generado;

            $filas[] = [
                $this->relativa($origen),
                $anchoOriginal . 'px',
                $this->kb($original),
                implode('/', $objetivos),
                $this->kb($generado),
            ];

            if ($this->option('eliminar-originales') && $creadas > 0) {
                @unlink($origen);
            }
        }

        $this->newLine();
        $this->table(
            ['Archivo', 'Ancho orig.', 'Antes', 'Variantes', 'Después (todas)'],
            $filas
        );

        $this->info(sprintf(
            'Total: %s → %s (%d%% menos) en %d imágenes.',
            $this->kb($pesoAntes),
            $this->kb($pesoDespues),
            $pesoAntes > 0 ? round((1 - $pesoDespues / $pesoAntes) * 100) : 0,
            count($filas)
        ));

        if ($fallidas > 0) {
            $this->warn("{$fallidas} operación(es) fallaron — ver los avisos de arriba.");
        }

        return self::SUCCESS;
    }

    /** Todas las imágenes de public/images, excluyendo las variantes ya generadas. */
    private function buscarOriginales(): array
    {
        $raiz = public_path('images');

        if (! is_dir($raiz)) {
            return [];
        }

        $encontradas = [];

        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterador as $archivo) {
            if (! $archivo->isFile()) {
                continue;
            }

            $ext = strtolower($archivo->getExtension());

            if (! in_array($ext, self::EXTENSIONES, true)) {
                continue;
            }

            $encontradas[] = $archivo->getPathname();
        }

        sort($encontradas);

        return $encontradas;
    }

    /** foo/bar.jpg + 800 → foo/bar-800.webp (misma convención que imagen_variante()). */
    private function rutaVariante(string $origen, int $ancho): string
    {
        return preg_replace('/\.[^.\/\\\\]+$/', '', $origen) . "-{$ancho}.webp";
    }

    private function relativa(string $ruta): string
    {
        return str_replace('\\', '/', substr($ruta, strlen(public_path()) + 1));
    }

    private function kb(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 2) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}
