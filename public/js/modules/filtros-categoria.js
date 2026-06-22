(function () {
    'use strict';

    var updateVarianteVisibility = null;

    document.addEventListener('DOMContentLoaded', function () {
        initFiltroGrupoToggle();
        initVariantesCondicionales();
        initBuscar();
        initAutoSubmit();
        initMobileFiltros();
        initStickyFilterBtn();
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
     * Colapsa/expande cada grupo usando la altura real del contenido (scrollHeight)
     * para que la transición CSS sea perfectamente proporcional al contenido.
     */
    function initFiltroGrupoToggle() {
        document.querySelectorAll('.filtro-grupo-toggle').forEach(function (toggle) {
            var targetId = toggle.getAttribute('data-target');
            var content  = document.getElementById(targetId);
            if (!content) return;

            // Anclar la altura inicial para que la primera transición tenga punto de partida
            if (content.classList.contains('collapsed')) {
                content.style.height = '0';
            }
            // Si está abierto, dejamos height sin fijar (auto) —
            // al colapsar por primera vez lo pineamos a scrollHeight antes de animar.

            toggle.addEventListener('click', function () {
                var chevron     = this.querySelector('.filtro-chevron');
                var isCollapsed = content.classList.contains('collapsed');

                if (isCollapsed) {
                    // Expandir: 0 → scrollHeight → auto
                    content.classList.remove('collapsed');
                    requestAnimationFrame(function () {
                        content.style.height = content.scrollHeight + 'px';
                        content.addEventListener('transitionend', function onEnd() {
                            content.style.height = 'auto';
                            content.removeEventListener('transitionend', onEnd);
                        });
                    });
                } else {
                    // Colapsar: auto → scrollHeight (pinear) → 0
                    content.style.height = content.scrollHeight + 'px';
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            content.classList.add('collapsed');
                            content.style.height = '0';
                        });
                    });
                }

                if (chevron) chevron.classList.toggle('rotated', !isCollapsed);
                toggle.setAttribute('aria-expanded', String(isCollapsed));
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
                syncFormFromUrl(url);
                updateSidebarBadge();
                syncStickyCount();
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
     * Sincroniza checkboxes Y el input de búsqueda con los params de la URL cargada.
     */
    function syncFormFromUrl(urlStr) {
        var f = getForm();
        if (!f) return;
        var url;
        try { url = new URL(urlStr, window.location.origin); } catch (e) { return; }

        // Limpiar checkboxes y re-marcar los que estén en la URL
        f.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
        url.searchParams.forEach(function (value, key) {
            var cb = f.querySelector('input[type="checkbox"][name="' + key + '"][value="' + value + '"]');
            if (cb) cb.checked = true;
        });

        // Sincronizar input de búsqueda
        var searchInput = document.getElementById('filtros-buscar-input');
        if (searchInput) {
            searchInput.value = url.searchParams.get('buscar') || '';
            updateClearBtn(searchInput);
        }

        if (updateVarianteVisibility) updateVarianteVisibility();
    }

    /**
     * Muestra u oculta el botón de limpiar búsqueda y la clase has-value.
     */
    function updateClearBtn(input) {
        var wrapper = document.getElementById('filtros-buscar-wrapper');
        if (wrapper && input) {
            wrapper.classList.toggle('has-value', input.value.trim().length > 0);
        }
    }

    /**
     * Actualiza el badge de filtros activos (checkboxes + búsqueda activa).
     */
    function updateSidebarBadge() {
        var f = getForm();
        if (!f) return;
        var checkCount  = f.querySelectorAll('input[type="checkbox"]:checked').length;
        var searchInput = document.getElementById('filtros-buscar-input');
        var hasSearch   = searchInput && searchInput.value.trim().length > 0;
        var count       = checkCount + (hasSearch ? 1 : 0);

        var limpiarEl = document.querySelector('.filtros-limpiar');
        var badgeEl   = document.querySelector('.filtros-limpiar .filtros-badge');
        if (limpiarEl) {
            if (count > 0) {
                limpiarEl.style.display = '';
                if (badgeEl) badgeEl.textContent = count;
            } else {
                limpiarEl.style.display = 'none';
            }
        }

        var stickyBadge = document.getElementById('filtros-sticky-badge');
        if (stickyBadge) {
            if (count > 0) {
                stickyBadge.textContent = count;
                stickyBadge.classList.add('show');
            } else {
                stickyBadge.classList.remove('show');
            }
        }
    }

    /**
     * En la vista "todos los productos", oculta los grupos de variantes hasta que
     * haya al menos una categoría seleccionada o texto en el buscador.
     */
    function initVariantesCondicionales() {
        if (!document.getElementById('filtro-grupo-categorias')) return;

        var varianteGroups = Array.from(document.querySelectorAll('.filtro-grupo')).filter(function (group) {
            var toggle = group.querySelector('.filtro-grupo-toggle');
            return toggle && toggle.getAttribute('data-target') !== 'filtro-grupo-categorias';
        });

        if (!varianteGroups.length) return;

        function hasActiveBaseFilter() {
            var hasCat = !!document.querySelector('#filtro-grupo-categorias input[type="checkbox"]:checked');
            var searchInput = document.getElementById('filtros-buscar-input');
            return hasCat || (searchInput && searchInput.value.trim().length > 0);
        }

        updateVarianteVisibility = function () {
            var show = hasActiveBaseFilter();
            if (!show) {
                varianteGroups.forEach(function (group) {
                    group.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
                });
            }
            varianteGroups.forEach(function (group) {
                group.style.display = show ? '' : 'none';
            });
            updateSidebarBadge();
        };

        // Attach to category checkboxes BEFORE initAutoSubmit so visibility updates first
        document.querySelectorAll('#filtro-grupo-categorias input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', updateVarianteVisibility);
        });

        updateVarianteVisibility();
    }

    /**
     * Input de búsqueda con debounce de 400ms.
     */
    function initBuscar() {
        var input    = document.getElementById('filtros-buscar-input');
        var clearBtn = document.getElementById('filtros-buscar-clear');
        if (!input) return;

        var debounceTimer;

        input.addEventListener('input', function () {
            updateClearBtn(input);
            if (updateVarianteVisibility) updateVarianteVisibility();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                fetchProductos(buildUrlFromForm());
            }, 400);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                updateClearBtn(input);
                if (updateVarianteVisibility) updateVarianteVisibility();
                fetchProductos(buildUrlFromForm());
                input.focus();
            });
        }

        // Estado inicial del botón limpiar
        updateClearBtn(input);
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

    function syncStickyCount() {
        var countEl     = document.querySelector('#productos-container .productos-count');
        var stickyCount = document.getElementById('filtros-sticky-count');
        if (stickyCount) {
            stickyCount.textContent = countEl ? countEl.textContent.trim() : '';
        }
    }

    /**
     * Barra sticky en la parte superior (mobile): aparece cuando el botón
     * original de filtros sale del viewport. Muestra el conteo de resultados
     * y un botón de filtros con badge de filtros activos.
     */
    function initStickyFilterBtn() {
        var bar       = document.getElementById('filtros-sticky-bar');
        var stickyBtn = document.getElementById('filtros-sticky-btn');
        var origBtn   = document.getElementById('filtros-mobile-btn');

        if (!bar || !stickyBtn) return;

        syncStickyCount();

        stickyBtn.addEventListener('click', function () {
            var sidebar = document.querySelector('.filtros-sidebar');
            var overlay = document.getElementById('filtros-overlay');
            if (sidebar) sidebar.classList.add('filtros-open');
            if (overlay) overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });

        if (!origBtn) return;

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                bar.classList.toggle('show', !entries[0].isIntersecting);
            }, { threshold: 0 });
            observer.observe(origBtn);
        } else {
            window.addEventListener('scroll', function () {
                bar.classList.toggle('show', origBtn.getBoundingClientRect().bottom < 0);
            }, { passive: true });
        }
    }

    /**
     * Intercepta clics en chips y paginación dentro del grid de productos.
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
