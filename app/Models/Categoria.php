<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    /** Encuadre de una foto sin retocar: centrada y sin ampliar. */
    public const ENCUADRE_DEFECTO = ['x' => 50.0, 'y' => 50.0, 'zoom' => 1.0];

    /**
     * Los dos recuadros que el admin encuadra por separado, con la relación de
     * aspecto representativa de cada uno. El editor del panel dibuja estos
     * mismos valores, así que el marco que se ve ahí es el que se publica.
     *
     * Salen de calcular el alto real de .g-hero —min-height de 30svh hasta
     * 767px y 42svh desde ahí— sobre las pantallas más frecuentes. El
     * min-height gana siempre: ni un título de dos renglones llega a estirarlo,
     * así que el alto no depende del contenido y estas formas son estables.
     *
     *   teléfonos      1.76:1 (Pixel 7) … 2.26:1 (iPhone SE)   promedio 1.91
     *   laptops/monitores  4.40:1 (1440) … 5.02:1 (1366)       promedio 4.7
     *
     * La excepción es la tablet en vertical: mide ≈1.95:1, forma de teléfono,
     * pero cae del lado de escritorio porque el corte del CSS es un único
     * 768px. El punto focal la sostiene igual —lo que el admin eligió sigue en
     * cuadro—, sólo que se ve más foto arriba y abajo de lo que muestra el
     * marco. Arreglarlo de verdad pide un tercer encuadre para tablet.
     *
     * `minimo` es el ancho en píxeles de foto que el recuadro necesita para no
     * verse ampliado: un teléfono de 430px a 2.5× de densidad pide ~1075, y el
     * hero de escritorio se sirve hasta 2400px de ancho. La cota del editor se
     * pone en alerta cuando el recorte elegido baja de ahí.
     */
    public const RECUADROS_HERO = [
        'movil'      => ['nombre' => 'Teléfono', 'ratio' => 1.9, 'leyenda' => 'hasta 767px · 1.9:1', 'minimo' => 1100],
        'escritorio' => ['nombre' => 'Escritorio', 'ratio' => 4.8, 'leyenda' => 'desde 768px · 4.8:1', 'minimo' => 1800],
    ];

    protected $fillable = [
        'nombre',
        'activo',
        'imagen_hero',
        'hero_movil_x',
        'hero_movil_y',
        'hero_movil_zoom',
        'hero_escritorio_x',
        'hero_escritorio_y',
        'hero_escritorio_zoom',
    ];

    protected $casts = [
        'hero_movil_x'         => 'float',
        'hero_movil_y'         => 'float',
        'hero_movil_zoom'      => 'float',
        'hero_escritorio_x'    => 'float',
        'hero_escritorio_y'    => 'float',
        'hero_escritorio_zoom' => 'float',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    public function variantes()
    {
        return $this->belongsToMany(Variante::class, 'categorias_variantes', 'categoria_id', 'variante_id');
    }

    /** El encuadre de un recuadro ('movil' | 'escritorio'), con defecto si falta. */
    public function encuadreHero(string $recuadro): array
    {
        return [
            'x'    => $this->{"hero_{$recuadro}_x"} ?? self::ENCUADRE_DEFECTO['x'],
            'y'    => $this->{"hero_{$recuadro}_y"} ?? self::ENCUADRE_DEFECTO['y'],
            'zoom' => $this->{"hero_{$recuadro}_zoom"} ?? self::ENCUADRE_DEFECTO['zoom'],
        ];
    }

    /**
     * Variables CSS del encuadre, para el atributo `style` del <img> del hero.
     *
     * El sitio público no corre JS para esto: externo.css lee las variables en
     * object-position, transform-origin y scale (ver .g-hero-bg). Las que están
     * en su valor por defecto no se emiten — el fallback del CSS ya las cubre y
     * así el markup de una categoría sin retocar queda igual que antes.
     */
    public function estiloEncuadreHero(): string
    {
        $vars = [];

        // El móvil usa las variables base y escritorio las sufijadas, en el
        // mismo orden mobile-first que sigue externo.css.
        foreach (['movil' => '', 'escritorio' => '-esc'] as $recuadro => $sufijo) {
            $encuadre = $this->encuadreHero($recuadro);

            foreach (['x', 'y'] as $eje) {
                if (abs($encuadre[$eje] - self::ENCUADRE_DEFECTO[$eje]) > 0.001) {
                    $vars[] = "--hero-{$eje}{$sufijo}:" . $this->numero($encuadre[$eje]) . '%';
                }
            }

            if (abs($encuadre['zoom'] - self::ENCUADRE_DEFECTO['zoom']) > 0.001) {
                $vars[] = "--hero-zoom{$sufijo}:" . $this->numero($encuadre['zoom']);
            }
        }

        return implode(';', $vars);
    }

    /** 62.50 → "62.5", 1.00 → "1": sin ceros de relleno en el HTML. */
    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
}
