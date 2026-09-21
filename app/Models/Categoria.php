<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    /** Encuadre de una foto sin retocar: centrada y sin ampliar. */
    public const ENCUADRE_DEFECTO = ['x' => 50.0, 'y' => 50.0, 'zoom' => 1.0];

    /**
     * Las cuatro bandas en que el hero cambia de forma, y el encuadre que el
     * admin ajusta para cada una.
     *
     * `ratio` es el mismo número que declara `aspect-ratio` en externo.css, no
     * un promedio representativo: esta constante y el CSS son una sola fuente
     * de verdad, y por eso el recuadro del panel es exactamente lo que se
     * publica. Si se toca un ratio acá, hay que tocarlo allá.
     *
     * Las bandas salen de pegarle a los altos que el hero tenía cuando dependía
     * del viewport (30svh / 42svh), para que el cambio no se note: diez de doce
     * dispositivos frecuentes quedan dentro de ±7%.
     *
     * `minimo` es el ancho en píxeles de foto que la banda necesita para no
     * verse ampliada, calculado sobre su ancho CSS máximo. La cota del editor
     * se pone en alerta cuando el recorte elegido baja de ahí.
     */
    public const RECUADROS_HERO = [
        'movil' => [
            'nombre'  => 'Teléfono',
            'ratio'   => 19 / 10,
            'leyenda' => 'hasta 767px · 1.9:1',
            'minimo'  => 1200,
        ],
        'tablet' => [
            'nombre'  => 'Tablet',
            'ratio'   => 21 / 10,
            'leyenda' => '768–1023px · 2.1:1',
            'minimo'  => 1500,
        ],
        'laptop' => [
            'nombre'  => 'Laptop',
            'ratio'   => 7 / 2,
            'leyenda' => '1024–1365px · 3.5:1',
            'minimo'  => 1800,
        ],
        'escritorio' => [
            'nombre'  => 'Escritorio',
            'ratio'   => 47 / 10,
            'leyenda' => 'desde 1366px · 4.7:1',
            'minimo'  => 2000,
        ],
    ];

    /**
     * Sufijo de las variables CSS de cada banda. El móvil usa las variables
     * base y las demás las sufijadas, en el mismo orden mobile-first que sigue
     * externo.css.
     */
    private const SUFIJOS_HERO = [
        'movil'      => '',
        'tablet'     => '-tab',
        'laptop'     => '-lap',
        'escritorio' => '-esc',
    ];

    protected $fillable = [
        'nombre',
        'activo',
        'imagen_hero',
        'hero_encuadre',
    ];

    protected $casts = [
        'hero_encuadre' => 'array',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    public function variantes()
    {
        return $this->belongsToMany(Variante::class, 'categorias_variantes', 'categoria_id', 'variante_id');
    }

    /**
     * El encuadre de una banda, con el defecto donde falte.
     *
     * Tolera un JSON ausente, incompleto o con bandas nuevas todavía sin
     * guardar: cada eje cae al centro por su cuenta. Eso es lo que hace que
     * agregar una banda no necesite migración ni backfill.
     */
    public function encuadreHero(string $recuadro): array
    {
        $guardado = ($this->hero_encuadre ?? [])[$recuadro] ?? [];

        return [
            'x'    => (float) ($guardado['x'] ?? self::ENCUADRE_DEFECTO['x']),
            'y'    => (float) ($guardado['y'] ?? self::ENCUADRE_DEFECTO['y']),
            'zoom' => (float) ($guardado['zoom'] ?? self::ENCUADRE_DEFECTO['zoom']),
        ];
    }

    /**
     * Variables CSS del encuadre, para el atributo `style` del <img> del hero.
     *
     * El sitio público no corre JS para esto: externo.css lee las variables en
     * object-position, transform-origin y scale (ver .g-hero-bg). Las que están
     * en su valor por defecto no se emiten — el fallback del CSS ya las cubre y
     * así una categoría sin retocar deja el markup igual que antes.
     */
    public function estiloEncuadreHero(): string
    {
        $vars = [];

        foreach (self::SUFIJOS_HERO as $recuadro => $sufijo) {
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
