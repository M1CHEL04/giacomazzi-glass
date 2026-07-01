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
