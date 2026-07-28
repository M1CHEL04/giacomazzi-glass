/**
 * Riel con scroll-snap (categorías de Inicio, obras de Nosotros).
 *
 * A diferencia de carousel.js, acá el mecanismo es el scroll nativo del
 * navegador: en el teléfono el gesto natural es el swipe con el pulgar y
 * conviene no interceptarlo. Las flechas (solo desktop) y los puntos son
 * ayudas montadas encima de ese scroll, no el motor.
 *
 * Los puntos cuentan PÁGINAS, no tarjetas. Antes había un punto por
 * tarjeta y en laptop quedaban 5 puntos para 2 posiciones reales de
 * scroll, porque entran cuatro tarjetas a la vez: los puntos mentían
 * sobre dónde estabas parado. Ahora se recalculan midiendo el riel, así
 * que el mismo código da 4 puntos en teléfono y 2 en laptop.
 *
 * Las flechas son circulares: en la última página, "siguiente" vuelve al
 * principio, y al revés. Nunca quedan muertas contra un extremo.
 *
 * Hooks: [data-rail], [data-rail-track], [data-rail-prev],
 *        [data-rail-next], [data-rail-dots] (opcional).
 */
(function () {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const BEHAVIOR = reduceMotion ? 'auto' : 'smooth';
    // Tolerancia para no pelear con el redondeo subpíxel del scroll.
    const EPS = 2;

    function initRail(root) {
        const track = root.querySelector('[data-rail-track]');
        if (!track) return;

        const prevBtn = root.querySelector('[data-rail-prev]');
        const nextBtn = root.querySelector('[data-rail-next]');
        const dotsWrap = root.querySelector('[data-rail-dots]');
        if (!track.children.length) return;

        let dots = [];
        let pages = 1;
        let ticking = false;

        function maxScroll() {
            return track.scrollWidth - track.clientWidth;
        }

        function countPages() {
            if (!track.clientWidth) return 1;
            return Math.max(1, Math.ceil((track.scrollWidth - EPS) / track.clientWidth));
        }

        // scrollLeft ∈ [0, max] mapeado sobre [0, pages-1]. Sirve igual
        // para la última página, que casi nunca mide un ancho completo.
        function activePage() {
            const max = maxScroll();
            if (max <= EPS || pages < 2) return 0;
            const ratio = track.scrollLeft / max;
            return Math.min(pages - 1, Math.max(0, Math.round(ratio * (pages - 1))));
        }

        function goToPage(i) {
            const max = maxScroll();
            if (max <= 0 || pages < 2) return;
            track.scrollTo({ left: (i / (pages - 1)) * max, behavior: BEHAVIOR });
        }

        function buildDots() {
            if (!dotsWrap) return;
            dotsWrap.textContent = '';
            dots = [];
            if (pages < 2) return;

            for (let i = 0; i < pages; i++) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'rail-dot';
                b.setAttribute('aria-label', 'Ir a la página ' + (i + 1) + ' de ' + pages);
                b.addEventListener('click', (function (page) {
                    return function () { goToPage(page); };
                })(i));
                dotsWrap.appendChild(b);
                dots.push(b);
            }
        }

        function sync() {
            const canScroll = maxScroll() > EPS;

            if (prevBtn && nextBtn) {
                // Ocultas del todo si no hay desborde. Cuando sí lo hay,
                // nunca se deshabilitan: dan la vuelta.
                prevBtn.hidden = !canScroll;
                nextBtn.hidden = !canScroll;
            }

            if (dotsWrap) {
                dotsWrap.hidden = !canScroll || pages < 2;
                const active = activePage();
                dots.forEach(function (d, i) {
                    d.classList.toggle('is-active', i === active);
                    d.setAttribute('aria-current', i === active ? 'true' : 'false');
                });
            }
        }

        function onScroll() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                ticking = false;
                sync();
            });
        }

        // Las flechas se mueven de a UNA página, la misma unidad que
        // cuentan los puntos. Antes avanzaban 0.85 del ancho visible y
        // no coincidían: un clic no equivalía a un punto.
        function step(dir) {
            if (pages < 2) return;
            let target = activePage() + dir;
            if (target >= pages) target = 0;          // del final, al principio
            else if (target < 0) target = pages - 1;  // del principio, al final
            goToPage(target);
        }

        function measure() {
            const next = countPages();
            if (next !== pages) {
                pages = next;
                buildDots();
            }
            sync();
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { step(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { step(1); });

        track.addEventListener('scroll', onScroll, { passive: true });

        let resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(measure, 150);
        });

        pages = countPages();
        buildDots();
        sync();

        // Las tarjetas traen imágenes lazy: al cargar cambian el alto y a
        // veces el ancho del riel, así que se vuelve a medir.
        window.addEventListener('load', measure);
    }

    document.querySelectorAll('[data-rail]').forEach(initRail);
})();
