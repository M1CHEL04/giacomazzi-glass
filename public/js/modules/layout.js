(function () {
    'use strict';

    // ── Desktop dropdown hover ────────────────────────────────────────────────
    var dropdownEl     = document.querySelector('.dropdown');
    var dropdownToggle = document.querySelector('.dropdown-toggle');
    var dropdownMenu   = document.querySelector('.dropdown-menu');
    var hideTimeout;

    if (dropdownEl && window.innerWidth >= 992) {
        dropdownEl.addEventListener('mouseenter', function () {
            clearTimeout(hideTimeout);
            dropdownToggle.classList.add('show');
            dropdownMenu.classList.add('show');
        });
        dropdownEl.addEventListener('mouseleave', function () {
            hideTimeout = setTimeout(function () {
                dropdownToggle.classList.remove('show');
                dropdownMenu.classList.remove('show');
            }, 150);
        });
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth < 992) {
            clearTimeout(hideTimeout);
            if (dropdownToggle) dropdownToggle.classList.remove('show');
            if (dropdownMenu)   dropdownMenu.classList.remove('show');
        }
    });

    // ── Ramas del menú de Productos (desktop) ─────────────────────────────────
    // Mostrar y ocultar el panel lo hace el CSS con :hover y :focus-within, así
    // que el menú anda sin JS. Lo único que falta es contarle a un lector de
    // pantalla si el panel está desplegado, que es lo que el CSS no puede
    // expresar.
    document.querySelectorAll('[data-rama-wrap]').forEach(function (wrap) {
        var boton = wrap.querySelector('.nav-prod-rama');
        if (!boton) return;

        function sincronizar(abierto) {
            boton.setAttribute('aria-expanded', String(abierto));
        }

        wrap.addEventListener('mouseenter', function () { sincronizar(true); });
        wrap.addEventListener('mouseleave', function () {
            // Con el foco adentro el panel sigue abierto aunque salga el mouse.
            if (!wrap.contains(document.activeElement)) sincronizar(false);
        });
        wrap.addEventListener('focusin',  function () { sincronizar(true); });
        wrap.addEventListener('focusout', function (e) {
            if (!wrap.contains(e.relatedTarget)) sincronizar(false);
        });
    });

    // ── Sombra del navbar pegado ──────────────────────────────────────────────
    // El header acompaña el scroll; la sombra aparece recién cuando hay
    // contenido pasando por debajo, para que se lea como capa y no como
    // una barra flotando desde el principio.
    var header = document.querySelector('.external-header');

    if (header) {
        var ticking = false;

        function syncHeaderShadow() {
            header.classList.toggle('is-scrolled', window.scrollY > 4);
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(syncHeaderShadow);
        }, { passive: true });

        syncHeaderShadow();
    }

    // ── Mobile drawer ─────────────────────────────────────────────────────────
    var menuBtn  = document.getElementById('mobile-menu-btn');
    var drawer   = document.getElementById('mobile-nav-drawer');
    var closeBtn = document.getElementById('mobile-drawer-close');
    var backdrop = document.getElementById('mobile-drawer-backdrop');

    function openDrawer() {
        drawer.classList.add('open');
        menuBtn.classList.add('open');
        menuBtn.setAttribute('aria-expanded', 'true');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        menuBtn.classList.remove('open');
        menuBtn.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (menuBtn)  menuBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDrawer();
    });

    // ── Acordeones del drawer ─────────────────────────────────────────────────
    // Genérico y anidable: Productos contiene Especial y Estándar. La altura
    // se anima sobre scrollHeight y al terminar queda en 'auto', que es lo que
    // permite que abrir una rama empuje al padre en vez de quedar recortada.

    /**
     * Cierra de golpe las ramas que cuelgan de una sección. Se llama recién
     * cuando la sección padre terminó de plegarse, así que no hay nada a la
     * vista que animar: sin transición no se ve el hueco que deja una rama al
     * cerrarse dentro de otra que también se está cerrando.
     */
    function cerrarRamas(seccion) {
        seccion.querySelectorAll('[data-acordeon].open').forEach(function (rama) {
            var t = rama.querySelector(':scope > [data-acordeon-toggle]');
            var p = rama.querySelector(':scope > [data-acordeon-panel]');
            rama.classList.remove('open');
            if (p) p.style.height = '0px';
            if (t) t.setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('[data-acordeon]').forEach(function (seccion) {
        var toggle = seccion.querySelector(':scope > [data-acordeon-toggle]');
        var panel  = seccion.querySelector(':scope > [data-acordeon-panel]');
        if (!toggle || !panel) return;

        panel.style.height = '0px';

        function abrir() {
            seccion.classList.add('open');
            panel.style.height = panel.scrollHeight + 'px';
            panel.addEventListener('transitionend', function alTerminar(e) {
                if (e.propertyName !== 'height') return;
                panel.style.height = 'auto';
                panel.removeEventListener('transitionend', alTerminar);
            });
        }

        function cerrar() {
            // Del 'auto' no se puede animar: primero se fija el alto real.
            panel.style.height = panel.scrollHeight + 'px';
            requestAnimationFrame(function () {
                seccion.classList.remove('open');
                panel.style.height = '0px';
                panel.addEventListener('transitionend', function alCerrar(e) {
                    if (e.propertyName !== 'height') return;
                    panel.removeEventListener('transitionend', alCerrar);
                    // Si volvió a abrirse antes de que terminara de plegarse,
                    // esto ya no corresponde.
                    if (seccion.classList.contains('open')) return;
                    // Plegar Productos pliega sus ramas: la próxima vez que se
                    // abra, arranca desde el mismo estado que la primera.
                    cerrarRamas(seccion);
                });
            });
        }

        toggle.addEventListener('click', function () {
            var abierto = seccion.classList.contains('open');
            if (abierto) cerrar(); else abrir();
            toggle.setAttribute('aria-expanded', String(!abierto));

            // El padre está en 'auto' mientras está abierto, así que absorbe
            // el cambio solo; no hace falta recalcularlo.
        });
    });
})();
