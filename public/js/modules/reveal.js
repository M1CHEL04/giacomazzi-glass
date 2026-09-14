/**
 * Aparición al entrar en pantalla.
 *
 * Sin librería a propósito. AOS, GSAP o ScrollReveal traen entre 15 y 60 KB
 * y un CDN más para resolver exactamente esto: un IntersectionObserver y una
 * clase. Acá los módulos ya son vanilla (rail.js, layout.js) y este archivo
 * pesa menos que el <script> que lo iría a buscar.
 *
 * Regla de oro: el contenido NUNCA depende de este script para verse. Quien
 * esconde los elementos es la clase .reveal-on sobre <html>, y esa clase la
 * pone un inline de una línea en el <head> que ya chequea reduced-motion. Si
 * el JS falla, no carga o el navegador no tiene IntersectionObserver, la
 * clase se saca y todo queda visible.
 *
 * El hero no lleva [data-reveal]: es el LCP y esconderlo para animarlo sería
 * pagar la métrica más cara de la página por un efecto.
 *
 * La animación se repite: el observer no deja de mirar al elemento después
 * de la primera vez, así que si volvés a subir y bajás de nuevo, vuelve a
 * entrar. Antes se hacía unobserve() —cada bloque aparecía una sola vez por
 * carga— y al recorrer la página dos veces la mitad de la home ya estaba
 * quieta.
 *
 * Hooks: [data-reveal] en el elemento,
 *        [data-reveal-delay="1".."4"] para escalonar hermanos.
 */
(function () {
    'use strict';

    var root = document.documentElement;

    // Sin la clase no hay nada escondido: reduced-motion o head sin el inline.
    if (!root.classList.contains('reveal-on')) return;

    var items = document.querySelectorAll('[data-reveal]');

    if (!items.length || !('IntersectionObserver' in window)) {
        root.classList.remove('reveal-on');
        return;
    }

    // Sin threshold (default 0): el callback dispara en cada cruce de borde,
    // que es exactamente lo que hace falta para prender y apagar. Con un
    // threshold intermedio el apagado quedaba a medio camino y el bloque
    // parpadeaba al scrollear despacio sobre el límite.
    //
    // El -8% de abajo hace que el elemento entre cuando ya se metió un poco
    // en pantalla y no apenas asoma el primer píxel: si no, en el scroll
    // rápido la animación termina antes de que el bloque se lea. Arriba no
    // hay margen a propósito: se apaga recién cuando salió del todo, así que
    // nada se desvanece mientras todavía se está leyendo.
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            entry.target.classList.toggle('is-in', entry.isIntersecting);
        });
    }, { rootMargin: '0px 0px -8% 0px' });

    items.forEach(function (el) { io.observe(el); });
}());
