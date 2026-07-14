/**
 * Carrusel reutilizable (Inicio y Nosotros).
 * - Desliza de a UNA tarjeta mostrando N por vista (configurable por breakpoint).
 * - Infinito/circular: clona los extremos y salta sin animación al cruzarlos,
 *   así nunca quedan páginas incompletas.
 * - Navegación por flechas y por puntitos (dots), ambos opcionales.
 * - Soporta varios carruseles en la misma página. Sin dependencias externas.
 *
 * Config por data-attributes en el contenedor [data-carousel]:
 *   data-per-desktop (>=992px, def. 4)
 *   data-per-tablet  (>=576px, def. 3)
 *   data-per-mobile  (<576px,  def. 2)
 *
 * Hooks: [data-carousel-viewport], [data-carousel-track], [data-carousel-prev],
 *        [data-carousel-next], [data-carousel-dots] (opcional).
 */
(function () {
    'use strict';

    function initCarousel(root) {
        const viewport = root.querySelector('[data-carousel-viewport]');
        const track = root.querySelector('[data-carousel-track]');
        const prevBtn = root.querySelector('[data-carousel-prev]');
        const nextBtn = root.querySelector('[data-carousel-next]');
        const dotsWrap = root.querySelector('[data-carousel-dots]');
        if (!viewport || !track || !prevBtn || !nextBtn) return;

        const perDesktop = parseInt(root.dataset.perDesktop, 10) || 4;
        const perTablet = parseInt(root.dataset.perTablet, 10) || 3;
        const perMobile = parseInt(root.dataset.perMobile, 10) || 2;

        const originals = Array.from(track.children);
        const count = originals.length;

        let perView = perDesktop;
        let index = 0;
        let slideWidth = 0;
        let looping = false;
        let animating = false;
        let dots = [];

        function getPerView() {
            const w = window.innerWidth;
            if (w >= 992) return perDesktop;
            if (w >= 576) return perTablet;
            return perMobile;
        }

        function clearClones() {
            track.querySelectorAll('[data-clone]').forEach(function (n) { n.remove(); });
        }

        function buildDots() {
            if (!dotsWrap || dots.length) return;
            dots = originals.map(function (_, i) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'carousel-dot';
                b.setAttribute('aria-label', 'Ir al elemento ' + (i + 1));
                b.addEventListener('click', function () { goToReal(i); });
                dotsWrap.appendChild(b);
                return b;
            });
        }

        // Índice "real" (0..count-1) del elemento más a la izquierda visible.
        function realIndex() {
            if (!looping) return 0;
            return ((index - perView) % count + count) % count;
        }

        function updateControls() {
            const show = looping;
            prevBtn.style.display = show ? '' : 'none';
            nextBtn.style.display = show ? '' : 'none';
            if (dotsWrap) dotsWrap.style.display = show ? '' : 'none';
            if (!dots.length) return;
            const active = realIndex();
            dots.forEach(function (d, i) { d.classList.toggle('is-active', i === active); });
        }

        function build() {
            perView = getPerView();
            clearClones();
            buildDots();

            looping = count > perView;

            if (looping) {
                for (let i = count - 1; i >= count - perView; i--) {
                    const c = originals[i].cloneNode(true);
                    c.setAttribute('data-clone', 'lead');
                    track.insertBefore(c, track.firstChild);
                }
                for (let i = 0; i < perView; i++) {
                    const c = originals[i].cloneNode(true);
                    c.setAttribute('data-clone', 'trail');
                    track.appendChild(c);
                }
                index = perView;
            } else {
                index = 0;
            }

            measure();
            setPosition(false);
            updateControls();
        }

        function measure() {
            slideWidth = viewport.clientWidth / perView;
            Array.from(track.children).forEach(function (slide) {
                slide.style.flex = '0 0 ' + slideWidth + 'px';
                slide.style.maxWidth = slideWidth + 'px';
            });
        }

        function setPosition(animate) {
            track.style.transition = animate ? 'transform 0.45s ease' : 'none';
            track.style.transform = 'translateX(' + (-index * slideWidth) + 'px)';
        }

        function move(dir) {
            if (!looping || animating) return;
            animating = true;
            index += dir;
            setPosition(true);
            updateControls();
        }

        // Va al elemento real i (usado por los puntitos).
        function goToReal(i) {
            if (!looping || animating) return;
            const target = perView + i;
            if (target === index) return;
            animating = true;
            index = target;
            setPosition(true);
            updateControls();
        }

        track.addEventListener('transitionend', function (e) {
            if (e.target !== track) return;
            animating = false;
            if (!looping) return;
            if (index < perView) {
                index += count;
                setPosition(false);
            } else if (index >= count + perView) {
                index -= count;
                setPosition(false);
            }
            updateControls();
        });

        prevBtn.addEventListener('click', function () { move(-1); });
        nextBtn.addEventListener('click', function () { move(1); });

        let resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(build, 150);
        });

        build();
    }

    document.querySelectorAll('[data-carousel]').forEach(initCarousel);
})();
