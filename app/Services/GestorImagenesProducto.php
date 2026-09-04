<?php

namespace App\Services;

use App\Models\ImagenProducto;
use App\Models\Producto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Subida de las imágenes de un producto al file server.
 *
 * Está tipado a Producto, así que también recibe ProductoEspecial: los dos
 * comparten la tabla imagenes_producto y por lo tanto el mismo circuito de
 * optimización, thumbs y baja lógica.
 */
class GestorImagenesProducto
{
    /**
     * Reglas de cada archivo de imagen de producto.
     *
     * `mimes` acota lo que acepta la regla `image` a secas (svg, gif y bmp
     * incluidos): GD no puede leer un SVG y la conversión a WebP explotaría.
     *
     * `dimensions` es la guarda de memoria, y va en megapíxeles porque es lo que
     * cuesta: GD descomprime a 4 bytes por píxel, así que el peso del archivo no
     * predice nada (una foto de 50 MP pesa 3,5 MB y pica en 216 MB). El tope de
     * 8000x8000 deja entrar a los celulares de 48/50 MP y pica en 280 MB contra
     * el techo de 512M que OptimizadorImagen se pone durante la conversión.
     * Si se sube este número hay que volver a medir y ajustar allá.
     */
    public const REGLAS_IMAGEN = 'image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000';

    public function __construct(private OptimizadorImagen $optimizador) {}

    public function guardar(
        Producto $producto,
        UploadedFile $imagen,
        bool $esPrincipal = false,
        bool $esTecnica = false
    ): ImagenProducto {
        if (!$imagen->isValid()) {
            Log::error('Archivo de imagen invalido en el request', [
                'producto_id'   => $producto->id,
                'error_message' => $imagen->getErrorMessage(),
            ]);
            throw new \Exception('Imagen no válida: ' . $imagen->getClientOriginalName());
        }

        $imagenProducto = ImagenProducto::create([
            'producto_id'  => $producto->id,
            'es_principal' => $esPrincipal,
            'es_tecnica'   => $esTecnica,
        ]);

        // Se descarta la extensión original: lo que se sube siempre es WebP.
        $base = $imagenProducto->id . '_' . preg_replace(
            '/[^a-zA-Z0-9._-]/',
            '_',
            pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME)
        );

        $imagenProducto->update(['nombre_imagen' => $base . '.webp']);

        $disk       = config('filesystems.image_disk', 'sftp');
        $carpeta    = 'imagenes_producto/' . $producto->id . '/';
        $rutaDisco  = $carpeta . $base . '.webp';
        $rutaThumb  = $carpeta . $base . '-thumb.webp';

        try {
            $variantes = $this->optimizador->variantes($imagen);

            Storage::disk($disk)->put($rutaDisco, $variantes['full']);

            $url = $this->urlDelDisco($disk, $rutaDisco);
        } catch (\Exception $e) {
            $imagenProducto->delete();
            Log::error('Error al subir imagen: ' . $e->getMessage());
            throw new \Exception('Error al subir la imagen al servidor de archivos: ' . $e->getMessage());
        }

        // El thumb es una optimización, no contenido: si falla, se registra y se
        // sigue. La vista cae a la imagen grande (ImagenProducto::rutaMiniatura).
        $urlThumb = null;
        try {
            Storage::disk($disk)->put($rutaThumb, $variantes['thumb']);
            $urlThumb = $this->urlDelDisco($disk, $rutaThumb);
        } catch (\Exception $e) {
            Log::warning('No se pudo subir la miniatura de la imagen ' . $imagenProducto->id . ': ' . $e->getMessage());
        }

        $imagenProducto->update([
            'ruta'       => $url,
            'ruta_thumb' => $urlThumb,
        ]);

        return $imagenProducto;
    }

    /** URL pública de un archivo del file server, o del disco local si no hay una configurada. */
    private function urlDelDisco(string $disk, string $ruta): string
    {
        $baseUrl = rtrim(config('filesystems.disks.' . $disk . '.url', ''), '/');

        return $baseUrl ? $baseUrl . '/' . $ruta : asset('storage/' . $ruta);
    }
}
