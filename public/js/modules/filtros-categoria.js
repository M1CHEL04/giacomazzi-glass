(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initFiltroGrupoToggle();
        initAutoSubmit();
        initMobileFiltros();
    });

    /**
     * Colapsa/expande cada grupo de filtros al hacer clic en el encabezado.
     */
    function initFiltroGrupoToggle() {
        var toggles = document.querySelectorAll('.filtro-grupo-toggle');

        toggles.forEach(function (toggle) {
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

    /**
     * Envía el formulario automáticamente al cambiar cualquier checkbox.
     */
    function initAutoSubmit() {
        var form = document.getElementById('filtros-form');
        if (!form) return;

        form.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                form.submit();
            });
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

        if (mobileBtn) {
            mobileBtn.addEventListener('click', openFiltros);
        }

        if (cerrarBtn) {
            cerrarBtn.addEventListener('click', closeFiltros);
        }

        overlay.addEventListener('click', closeFiltros);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeFiltros();
            }
        });
    }
})();
