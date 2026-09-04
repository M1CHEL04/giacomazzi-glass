<?php

namespace App\Services;

use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Rules\ImagenProductoValida;
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
     * Delegadas en ImagenProductoValida en lugar de la tira de reglas nativas
     * "image|mimes:...|max:...|dimensions:..." para que el mensaje de error
     * incluya el nombre del archivo: con varias imágenes en el mismo request,
     * "cada imagen no puede superar los 5 MB" no dice cuál hay que corregir.
     *
     * El tope de tamaño (5 MB) y de dimensiones (8000x8000 px) que aplica esa
     * regla es el mismo de siempre: 8000x8000 es la guarda de memoria de GD al
     * decodificar (ver comentario en OptimizadorImagen) y deja entrar a los
     * celulares de 48/50 MP sin pasar el techo de 512M que ese servicio se
     * pone durante la conversión. Si se sube ese número hay que volver a medir
     * y ajustar allá.
     */
    public static function reglasImagen(): array
    {
        return [new ImagenProductoValida()];
    }

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
