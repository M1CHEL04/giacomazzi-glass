(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initFiltroGrupoToggle();
        initAutoSubmit();
        initMobileFiltros();
        initDelegatedLinks();
        initLimpiarLink();
    });

    function getForm() {
        return document.getElementById('filtros-form');
    }

    function getContainer() {
        return document.getElementById('productos-container');
    }

    /**
     * Colapsa/expande cada grupo de filtros al hacer clic en el encabezado.
     */
    function initFiltroGrupoToggle() {
        document.querySelectorAll('.filtro-grupo-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                var targetId = this.getAttribute('data-target');
                var content = document.getElementById(targetId);
                var chevron = this.querySelector('.filtro-chevron');
                if (!content) return;

                var isCollapsed = content.classList.contains('collapsed');
                content.classList.toggle('collapsed', !isCollapsed);
                if (chevron) {
                    chevron.classList.toggle('rotated', !isCollapsed);
                }
            });
        });
    }

    function buildUrlFromForm() {
        var f = getForm();
        if (!f) return window.location.pathname;
        var params = new URLSearchParams(new FormData(f));
        var str = params.toString();
        return window.location.pathname + (str ? '?' + str : '');
    }

    function fetchProductos(url, scrollGrid) {
        var container      = getContainer();
        var loadingOverlay = document.getElementById('filtros-loading');

        if (loadingOverlay) {
            loadingOverlay.classList.add('show');
            loadingOverlay.removeAttribute('aria-hidden');
        }
        if (container) container.classList.add('loading');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (container) container.innerHTML = data.html;
                history.pushState(null, '', url);
                syncCheckboxesFromUrl(url);
                updateSidebarBadge();
                if (scrollGrid && container) {
                    var top = container.getBoundingClientRect().top + window.scrollY - 20;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
            })
            .catch(function () {
                window.location.href = url;
            })
            .finally(function () {
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('show');
                    loadingOverlay.setAttribute('aria-hidden', 'true');
                }
                if (container) container.classList.remove('loading');
            });
    }

    /**
     * Sincroniza los checkboxes del sidebar con los params de la URL cargada.
     */
    function syncCheckboxesFromUrl(urlStr) {
        var f = getForm();
        if (!f) return;
        var url;
        try { url = new URL(urlStr, window.location.origin); } catch (e) { return; }

        f.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });

        url.searchParams.forEach(function (value, key) {
            var cb = f.querySelector('input[type="checkbox"][name="' + key + '"][value="' + value + '"]');
            if (cb) cb.checked = true;
        });
    }

    /**
     * Actualiza el badge de filtros activos en el encabezado del sidebar.
     */
    function updateSidebarBadge() {
        var f = getForm();
        if (!f) return;
        var count     = f.querySelectorAll('input[type="checkbox"]:checked').length;
        var limpiarEl = document.querySelector('.filtros-limpiar');
        var badgeEl   = document.querySelector('.filtros-limpiar .filtros-badge');
        if (!limpiarEl) return;
        if (count > 0) {
            limpiarEl.style.display = '';
            if (badgeEl) badgeEl.textContent = count;
        } else {
            limpiarEl.style.display = 'none';
        }
    }

    /**
     * Envía el formulario automáticamente al cambiar cualquier checkbox.
     */
    function initAutoSubmit() {
        var f = getForm();
        if (!f) return;

        f.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                fetchProductos(buildUrlFromForm());
            });
        });
    }

    /**
     * Intercepta clics en chips y paginación dentro del grid de productos.
     * Solo intercepta navegación a la misma ruta (con distintos query params);
     * links a páginas de producto u otras rutas los deja pasar normalmente.
     */
    function initDelegatedLinks() {
        var container = getContainer();
        if (!container) return;

        container.addEventListener('click', function (e) {
            var link = e.target.closest('a[href]');
            if (!link) return;
            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#') return;
            var url;
            try {
                url = new URL(href, window.location.origin);
                if (url.origin !== window.location.origin) return;
            } catch (err) { return; }
            // Solo interceptar navegación dentro de la misma ruta (filtros, chips, paginación)
            if (url.pathname !== window.location.pathname) return;
            e.preventDefault();
            var isPagination = !!link.closest('.productos-pagination');
            fetchProductos(href, isPagination);
        });
    }

    /**
     * Intercepta el enlace "Limpiar" del encabezado del sidebar.
     */
    function initLimpiarLink() {
        var limpiarEl = document.querySelector('.filtros-limpiar');
        if (!limpiarEl) return;
        limpiarEl.addEventListener('click', function (e) {
            e.preventDefault();
            fetchProductos(this.getAttribute('href'));
        });
    }

    /**
     * Panel deslizante de filtros en móvil.
     */
    function initMobileFiltros() {
        var mobileBtn = document.getElementById('filtros-mobile-btn');
        var cerrarBtn = document.getElementById('filtros-cerrar-btn');
        var sidebar   = document.querySelector('.filtros-sidebar');
        var overlay   = document.getElementById('filtros-overlay');

        if (!sidebar || !overlay) return;

        function openFiltros() {
            sidebar.classList.add('filtros-open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeFiltros() {
            sidebar.classList.remove('filtros-open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        if (mobileBtn) mobileBtn.addEventListener('click', openFiltros);
        if (cerrarBtn) cerrarBtn.addEventListener('click', closeFiltros);
        overlay.addEventListener('click', closeFiltros);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeFiltros();
        });
    }
})();
