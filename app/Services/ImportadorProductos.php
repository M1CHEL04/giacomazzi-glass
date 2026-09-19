<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoEspecial;
use App\Models\UnidadMedida;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/**
 * Carga masiva de productos desde un Excel o CSV: la primera fila son los
 * nombres de los campos y cada fila siguiente un producto.
 *
 * Es una herramienta de uso esporádico, así que no hay vista previa: se crean
 * las filas válidas y las demás vuelven en el reporte (y al log) con el
 * motivo, para corregirlas y volver a subir sólo esas.
 */
class ImportadorProductos
{
    /** Encabezado normalizado → campo del producto. */
    private const COLUMNAS = [
        'nombre'              => 'nombre',
        'codigo'              => 'codigo',
        'codigo de producto'  => 'codigo',
        'categoria'           => 'categoria',
        'unidad'              => 'unidad',
        'descripcion'         => 'descripcion',
        'descripcion tecnica' => 'descripcion_tecnica',
    ];

    private const OBLIGATORIAS = ['nombre', 'codigo', 'categoria', 'descripcion'];

    /**
     * @return array{creados: list<array{fila: int, codigo: string, nombre: string}>, errores: list<array{fila: int|null, codigo: string, motivo: string}>}
     */
    public function importar(UploadedFile $archivo, bool $especial): array
    {
        $linea   = $especial ? 'adapta' : 'estándar';
        $creados = [];
        $errores = [];

        // Los especiales no se cotizan, así que no llevan unidad.
        $obligatorias = $especial ? self::OBLIGATORIAS : [...self::OBLIGATORIAS, 'unidad'];

        $lector = IOFactory::createReaderForFile($archivo->getRealPath());
        if ($lector instanceof Csv) {
            // Un CSV guardado desde Excel en Windows viene en Windows-1252,
            // no en UTF-8: sin esto las tildes llegan rotas.
            $lector->setInputEncoding(Csv::GUESS_ENCODING);
        }

        $filas = $lector->load($archivo->getRealPath())
            ->getActiveSheet()
            // Valores formateados: un código "00123" no se convierte en 123.
            ->toArray(null, true, true, false);

        $encabezados = array_map(fn ($h) => self::COLUMNAS[$this->normalizar((string) $h)] ?? null, array_shift($filas) ?? []);

        $faltantes = array_diff($obligatorias, $encabezados);
        if (! empty($faltantes)) {
            $motivo = 'Faltan columnas obligatorias: ' . implode(', ', $faltantes) . '.';
            Log::warning("Importación línea {$linea} ({$archivo->getClientOriginalName()}): {$motivo}");

            return ['creados' => [], 'errores' => [['fila' => null, 'codigo' => '', 'motivo' => $motivo]]];
        }

        $categorias = Categoria::all()->keyBy(fn ($c) => $this->normalizar($c->nombre));
        $unidades   = UnidadMedida::all()->keyBy(fn ($u) => $this->normalizarCodigo($u->codigo));
        $codigosVistos = [];

        foreach ($filas as $i => $celdas) {
            $numeroFila = $i + 2; // +1 por el encabezado, +1 porque Excel cuenta desde 1

            $datos = [];
            foreach ($encabezados as $col => $campo) {
                if ($campo !== null) {
                    $datos[$campo] = trim((string) ($celdas[$col] ?? ''));
                }
            }

            if (implode('', $datos) === '') {
                continue;
            }

            $codigo = $datos['codigo'];
            $motivo = $this->validar($datos, $obligatorias, $categorias, $unidades, $codigosVistos);

            if ($motivo === null) {
                try {
                    $atributos = [
                        'categoria_id'        => $categorias[$this->normalizar($datos['categoria'])]->id,
                        'nombre'              => $datos['nombre'],
                        'codigo'              => $codigo,
                        'descripcion'         => $datos['descripcion'],
                        'descripcion_tecnica' => ($datos['descripcion_tecnica'] ?? '') ?: null,
                        'activo'              => true,
                    ];

                    $especial
                        ? ProductoEspecial::create($atributos)
                        : Producto::create($atributos + [
                            'unidad_id' => $unidades[$this->normalizarCodigo($datos['unidad'])]->id,
                        ]);

                    $creados[] = ['fila' => $numeroFila, 'codigo' => $codigo, 'nombre' => $datos['nombre']];
                } catch (\Exception $e) {
                    $motivo = 'Error al guardar: ' . $e->getMessage();
                }
            }

            if ($codigo !== '') {
                $codigosVistos[$codigo] = true;
            }

            if ($motivo !== null) {
                $errores[] = ['fila' => $numeroFila, 'codigo' => $codigo, 'motivo' => $motivo];
                Log::warning("Importación línea {$linea}, fila {$numeroFila} (código \"{$codigo}\"): {$motivo}");
            }
        }

        // Los productos nuevos pueden hacer aparecer categorías en el menú.
        if (! empty($creados)) {
            MenuCategorias::olvidar();
        }

        Log::info("Importación línea {$linea} ({$archivo->getClientOriginalName()}): " . count($creados) . ' creados, ' . count($errores) . ' con error.');

        return ['creados' => $creados, 'errores' => $errores];
    }

    /** Minúsculas, sin tildes y con los espacios colapsados. */
    public function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return preg_replace('/\s+/u', ' ', $texto);
    }

    /**
     * Para códigos de unidad: además de normalizar, espacios y guiones valen
     * como guion bajo, así "Alto Ancho" o "alto-ancho" dan "alto_ancho".
     */
    public function normalizarCodigo(string $texto): string
    {
        return preg_replace('/[\s_\-]+/u', '_', $this->normalizar($texto));
    }

    /** Devuelve el motivo por el que la fila no se puede cargar, o null si está bien. */
    private function validar(array $datos, array $obligatorias, Collection $categorias, Collection $unidades, array $codigosVistos): ?string
    {
        foreach ($obligatorias as $campo) {
            if ($datos[$campo] === '') {
                return "Falta el campo \"{$campo}\".";
            }
        }

        if (mb_strlen($datos['nombre']) > 255) {
            return 'El nombre supera los 255 caracteres.';
        }
        if (mb_strlen($datos['codigo']) > 100) {
            return 'El código supera los 100 caracteres.';
        }
        if (mb_strlen($datos['descripcion']) > Producto::MAX_DESCRIPCION) {
            return 'La descripción supera los ' . Producto::MAX_DESCRIPCION . ' caracteres.';
        }
        if (mb_strlen($datos['descripcion_tecnica'] ?? '') > Producto::MAX_DESCRIPCION_TECNICA) {
            return 'La descripción técnica supera los ' . Producto::MAX_DESCRIPCION_TECNICA . ' caracteres.';
        }

        if (! isset($categorias[$this->normalizar($datos['categoria'])])) {
            return "No se encontró la categoría \"{$datos['categoria']}\".";
        }

        if (in_array('unidad', $obligatorias, true) && ! isset($unidades[$this->normalizarCodigo($datos['unidad'])])) {
            return "No se encontró la unidad \"{$datos['unidad']}\". Usá uno de estos códigos: "
                . $unidades->pluck('codigo')->implode(', ') . '.';
        }

        // El unique de código es sobre toda la tabla: estándar y especiales.
        if (isset($codigosVistos[$datos['codigo']])) {
            return 'Código repetido dentro del archivo.';
        }
        if (Producto::where('codigo', $datos['codigo'])->exists()) {
            return 'Ya existe un producto con ese código.';
        }

        return null;
    }
}
