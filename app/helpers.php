<?php

if (! function_exists('versioned_asset')) {
    /**
     * Genera la URL de un asset local con un parámetro de versión (?v=<mtime>)
     * para romper la caché "immutable" de nginx cuando el archivo cambia.
     *
     * Los assets de CDN no deben pasar por acá — ya versionan en su URL.
     */
    function versioned_asset(string $path): string
    {
        $url  = asset($path);
        $full = public_path($path);

        return is_file($full) ? "{$url}?v=" . filemtime($full) : $url;
    }
}

if (! function_exists('whatsapp_numero')) {
    /** El número de WhatsApp como lo quiere wa.me: sólo dígitos, o '' si no hay. */
    function whatsapp_numero(): string
    {
        return preg_replace('/\D/', '', (string) config('app.whatsapp_number', ''));
    }
}

if (! function_exists('whatsapp_href')) {
    /**
     * Link de consulta por WhatsApp con el mensaje ya escrito.
     *
     * Todas las invitaciones a consultar del sitio público abren el chat
     * —es el canal por el que la fábrica atiende—, así que el link se arma
     * en un solo lugar y cada vista sólo pone el texto que corresponde a su
     * contexto.
     *
     * Sin WHATSAPP_NUMBER configurado cae a /contacto, que redirige a
     * Nosotros: el link queda peor pero nunca muerto.
     */
    function whatsapp_href(string $mensaje = ''): string
    {
        $numero = whatsapp_numero();

        if ($numero === '') {
            return route('contacto');
        }

        return 'https://wa.me/' . $numero
            . ($mensaje === '' ? '' : '?text=' . rawurlencode($mensaje));
    }
}

if (! function_exists('imagen_anchos')) {
    /** Anchos de las variantes que genera `php artisan images:optimizar`. */
    function imagen_anchos(): array
    {
        return [800, 1400, 2400];
    }
}

if (! function_exists('imagen_variante')) {
    /**
     * Ruta pública de la variante WebP de un ancho dado, o null si no fue generada.
     *
     * Convención: `images/heros/foo.jpg` → `images/heros/foo-800.webp`.
     * Deriva el nombre en vez de guardarlo en la base, así una imagen recién
     * subida por el admin sigue funcionando (sin optimizar) hasta que se corra
     * el comando.
     */
    function imagen_variante(string $ruta, int $ancho): ?string
    {
        static $cache = [];

        $base = preg_replace('/\.[^.\/]+$/', '', ltrim($ruta, '/'));
        $rel  = "{$base}-{$ancho}.webp";

        return $cache[$rel] ??= is_file(public_path($rel)) ? $rel : null;
    }
}

if (! function_exists('imagen_src')) {
    /**
     * URL para el atributo `src`. Devuelve la variante pedida si existe;
     * si no, cae al archivo original para no romper la vista.
     */
    function imagen_src(string $ruta, int $ancho = 1400): string
    {
        return versioned_asset(imagen_variante($ruta, $ancho) ?? ltrim($ruta, '/'));
    }
}

if (! function_exists('imagen_srcset')) {
    /**
     * Cadena para el atributo `srcset` con todas las variantes existentes.
     * Devuelve '' si no hay ninguna (el navegador ignora un srcset vacío y
     * usa el `src`).
     */
    function imagen_srcset(string $ruta, ?array $anchos = null): string
    {
        $fuentes = [];

        foreach ($anchos ?? imagen_anchos() as $ancho) {
            if ($variante = imagen_variante($ruta, $ancho)) {
                $fuentes[] = versioned_asset($variante) . " {$ancho}w";
            }
        }

        return implode(', ', $fuentes);
    }
}
