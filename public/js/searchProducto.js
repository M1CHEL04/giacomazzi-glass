document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchProducto');
    const categoriaSelect = document.getElementById('filterCategoria');
    const activoSelect = document.getElementById('filterActivo');
    let searchTimeout = null;

    function getFilters() {
        return {
            search: searchInput ? searchInput.value : '',
            categoria_id: categoriaSelect ? categoriaSelect.value : '',
            activo: activoSelect ? activoSelect.value : '',
        };
    }

    function fetchProductos(filters, page) {
        const url = new URL(window.location.origin + '/uso-interno/productos');
        if (filters.search) url.searchParams.set('search', filters.search);
        if (filters.categoria_id) url.searchParams.set('categoria_id', filters.categoria_id);
        if (filters.activo !== '') url.searchParams.set('activo', filters.activo);
        if (page && page > 1) url.searchParams.set('page', page);

        const loadingSpinner = document.getElementById('loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.classList.remove('d-none');
            loadingSpinner.classList.add('d-flex');
        }

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                renderProductos(data.productos);
                renderPagination(data.pagination);
                window.history.pushState({}, '', url.toString());
            })
            .catch((error) => {
                console.error('Error al buscar productos:', error);
            })
            .finally(() => {
                if (loadingSpinner) {
                    loadingSpinner.classList.add('d-none');
                    loadingSpinner.classList.remove('d-flex');
                }
            });
    }

    function renderProductos(productos) {
        const listContainer = document.getElementById('productos-list');

        if (!productos || productos.length === 0) {
            listContainer.innerHTML =
                '<div class="px-3 py-4 text-secondary text-center small">No se encontraron productos.</div>';
            return;
        }

        let html = '';
        productos.forEach((producto) => {
            const codigo = producto.codigo
                ? `<span class="badge bg-secondary-subtle text-secondary rounded-1" style="font-size: 11px; font-weight: 600;">${escapeHtml(producto.codigo)}</span>`
                : '<span class="text-secondary">—</span>';

            const descripcion = producto.descripcion
                ? `<div class="small text-secondary text-truncate" style="font-size: 12px; max-width: 400px;">${escapeHtml(producto.descripcion)}</div>`
                : '';

            html += `
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 px-3 py-2 border-bottom producto-row" data-producto-id="${producto.id}">
                    <div style="width: 110px;">${codigo}</div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold mb-1" style="font-size: 14px;">${escapeHtml(producto.nombre)}</div>
                        ${descripcion}
                    </div>
                    <div style="width: 180px;">
                        <span class="badge rounded-pill text-primary bg-primary-subtle" style="font-size: 10px; padding: 3px 10px;">
                            ${escapeHtml(producto.categoria)}
                        </span>
                    </div>
                    <div style="width: 100px;">
                        <span class="badge rounded-pill ${producto.activo ? 'text-success bg-success-subtle' : 'text-danger bg-danger-subtle'}" style="font-size: 10px; padding: 3px 10px;">
                            ${producto.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    <div class="text-end" style="width: 60px;">
                        <a
                            href="/uso-interno/show-producto/${producto.id}"
                            class="text-secondary text-decoration-none p-1 d-inline-flex rounded hover-bg-light"
                            aria-label="Ver producto"
                            title="Ver">
                            <svg style="width:16px;height:16px;" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 4C5.5 4 2 10 2 10s3.5 6 8 6 8-6 8-6-3.5-6-8-6zm0 10a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-6.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            `;
        });

        listContainer.innerHTML = html;
    }

    function renderPagination(pagination) {
        const paginationContainer = document.getElementById('productos-pagination');

        if (!pagination || pagination.last_page <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        paginationContainer.innerHTML = `
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 px-3 py-2 border-top">
                <div class="text-secondary" style="font-size: 12px;">
                    Mostrando ${pagination.from}-${pagination.to} de ${pagination.total} productos
                </div>
                <div>
                    ${pagination.links}
                </div>
            </div>
        `;

        // Interceptar clicks en los links de paginación de Laravel
        paginationContainer.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const url = new URL(this.href);
                const page = url.searchParams.get('page') || 1;
                fetchProductos(getFilters(), page);
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '—';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function onFilterChange() {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchProductos(getFilters(), 1), 300);
    }

    if (searchInput) searchInput.addEventListener('input', onFilterChange);
    if (categoriaSelect) categoriaSelect.addEventListener('change', onFilterChange);
    if (activoSelect) activoSelect.addEventListener('change', onFilterChange);

    // Soporte para navegación con historial
    window.addEventListener('popstate', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (searchInput) searchInput.value = urlParams.get('search') || '';
        if (categoriaSelect) categoriaSelect.value = urlParams.get('categoria_id') || '';
        if (activoSelect) activoSelect.value = urlParams.get('activo') || '';
        fetchProductos(getFilters(), urlParams.get('page') || 1);
    });
});
