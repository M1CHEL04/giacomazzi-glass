/**
 * Riel con scroll-snap (categorías de Inicio).
 *
 * A diferencia de carousel.js, acá el mecanismo es el scroll nativo del
 * navegador: en el teléfono el gesto natural es el swipe con el pulgar y
 * conviene no interceptarlo. Las flechas (solo desktop) y los puntos son
 * ayudas montadas encima de ese scroll, no el motor.
 *
 * Hooks: [data-rail], [data-rail-track], [data-rail-prev],
 *        [data-rail-next], [data-rail-dots] (opcional).
 */
(function () {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initRail(root) {
        const track = root.querySelector('[data-rail-track]');
        if (!track) return;

        const prevBtn = root.querySelector('[data-rail-prev]');
        const nextBtn = root.querySelector('[data-rail-next]');
        const dotsWrap = root.querySelector('[data-rail-dots]');
        const items = Array.from(track.children);
        if (!items.length) return;

        let dots = [];
        let ticking = false;

        function scrollable() {
            // 2px de tolerancia: evita flechas activas por redondeo subpíxel.
            return track.scrollWidth - track.clientWidth > 2;
        }

        function activeIndex() {
            const left = track.scrollLeft;
            let best = 0;
            let bestDist = Infinity;
            items.forEach(function (item, i) {
                const dist = Math.abs(item.offsetLeft - track.offsetLeft - left);
                if (dist < bestDist) {
                    bestDist = dist;
                    best = i;
                }
            });
            return best;
        }

        function scrollToItem(i) {
            const item = items[i];
            if (!item) return;
            track.scrollTo({
                left: item.offsetLeft - track.offsetLeft,
                behavior: reduceMotion ? 'auto' : 'smooth',
            });
        }

        function buildDots() {
            if (!dotsWrap) return;
            dots = items.map(function (_, i) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'rail-dot';
                b.setAttribute('aria-label', 'Ir a la categoría ' + (i + 1));
                b.addEventListener('click', function () { scrollToItem(i); });
                dotsWrap.appendChild(b);
                return b;
            });
        }

        function sync() {
            const canScroll = scrollable();

            if (prevBtn && nextBtn) {
                // Ocultas del todo si no hay desborde: un control muerto confunde.
                prevBtn.hidden = !canScroll;
                nextBtn.hidden = !canScroll;
                const max = track.scrollWidth - track.clientWidth;
                prevBtn.disabled = track.scrollLeft <= 2;
                nextBtn.disabled = track.scrollLeft >= max - 2;
            }

            if (dotsWrap) {
                dotsWrap.hidden = !canScroll;
                const active = activeIndex();
                dots.forEach(function (d, i) { d.classList.toggle('is-active', i === active); });
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

        function page(dir) {
            // Un "paso" es el ancho visible menos un asomo, así nunca se
            // saltea una tarjeta entera entre página y página.
            const step = Math.max(track.clientWidth * 0.85, 1);
            track.scrollBy({
                left: dir * step,
                behavior: reduceMotion ? 'auto' : 'smooth',
            });
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { page(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { page(1); });

        track.addEventListener('scroll', onScroll, { passive: true });

        let resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(sync, 150);
        });

        buildDots();
        sync();
    }

    document.querySelectorAll('[data-rail]').forEach(initRail);
})();
