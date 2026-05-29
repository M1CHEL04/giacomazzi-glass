<?php

namespace App\Jobs;

use App\Models\ImagenProducto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadImagen
{

    protected ImagenProducto $imagenProducto;
    protected string $pathAbsoluto;
    protected ?string $url = null;

    /**
     * Create a new job instance.
     */
    public function __construct(ImagenProducto $imagenProducto, string $pathAbsoluto)
    {
        $this->imagenProducto = $imagenProducto;
        $this->pathAbsoluto   = $pathAbsoluto;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->imagenProducto == null) {
                Log::error('La imagen de producto es nula.');
                throw new \Exception('La imagen de producto es nula.');
            }

            if (!file_exists($this->pathAbsoluto)) {
                Log::error('La ruta temporal de la imagen no existe: ' . $this->pathAbsoluto);
                throw new \Exception('La ruta temporal de la imagen no existe.');
            }

            $file_content = file_get_contents($this->pathAbsoluto);
            if ($file_content === false) {
                Log::error('No se pudo leer el contenido del archivo: ' . $this->pathAbsoluto);
                throw new \Exception('No se pudo leer el contenido del archivo.');
            }

            $nombreArchivo  = $this->imagenProducto->nombre_imagen;
            $carpetaDestino = 'imagenes_producto/' . $this->imagenProducto->producto_id;
            $rutaDestino    = $carpetaDestino . '/' . $nombreArchivo;

            $disk = config('filesystems.image_disk', 'sftp');

            try {
                $response = Storage::disk($disk)->put($rutaDestino, $file_content);

                if (!$response) {
                    Log::error('Error al subir la imagen al servidor de archivos. Ruta destino: ' . $rutaDestino);
                    throw new \Exception('Error al subir la imagen al servidor de archivos.');
                }

                if (!Storage::disk($disk)->exists($rutaDestino)) {
                    Log::error('La imagen no se encuentra en el servidor de archivos después de subirla. Ruta destino: ' . $rutaDestino);
                    throw new \Exception('La imagen no se encuentra en el servidor de archivos después de subirla.');
                }

                if ($disk === 'public') {
                    $this->url = Storage::disk('public')->url($rutaDestino);
                } else {
                    $baseUrl = rtrim(config('filesystems.disks.sftp.url', ''), '/');
                    $this->url = $baseUrl ? $baseUrl . '/' . $rutaDestino : $rutaDestino;
                }
            } catch (\Exception $e) {
                Log::error('Excepción al subir la imagen al servidor de archivos: ' . $e->getMessage());
                throw new \Exception('Excepción al subir la imagen al servidor de archivos: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Error al subir imagen de producto: ' . $e->getMessage());
            throw $e;
        } finally {
            if (is_string($this->pathAbsoluto) && file_exists($this->pathAbsoluto)) {
                @unlink($this->pathAbsoluto);
            }
        }
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
