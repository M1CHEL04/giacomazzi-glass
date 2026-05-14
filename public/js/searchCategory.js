document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchCategory');
    let searchTimeout = null;
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value;
            
            // Cancelar la búsqueda anterior si existe
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Esperar 300ms después de que el usuario deje de escribir
            searchTimeout = setTimeout(function() {
                searchCategories(searchTerm);
            }, 300);
        });
    }
    
    function searchCategories(search) {
        const url = new URL(window.location.origin + '/categorias');
        if (search) {
            url.searchParams.append('search', search);
        }
        
        // Mostrar spinner de carga
        const loadingSpinner = document.getElementById('loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.classList.remove('d-none');
            loadingSpinner.classList.add('d-flex');
        }
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Renderizar las categorías
            renderCategories(data.categorias);
            
            // Renderizar la paginación
            renderPagination(data.pagination);
            
            // Actualizar la URL sin recargar la página
            const newUrl = search ? url.toString() : window.location.pathname;
            window.history.pushState({}, '', newUrl);
        })
        .catch(error => {
            console.error('Error al buscar categorías:', error);
        })
        .finally(() => {
            // Ocultar spinner de carga
            if (loadingSpinner) {
                loadingSpinner.classList.add('d-none');
                loadingSpinner.classList.remove('d-flex');
            }
        });
    }
    
    function renderCategories(categorias) {
        const listContainer = document.getElementById('categorias-list');
        
        if (categorias.length === 0) {
            listContainer.innerHTML = '<div class="px-3 py-4 text-secondary text-center small">No se encontraron categorías.</div>';
            return;
        }
        
        let html = '';
        categorias.forEach(categoria => {
            const estadoRaw = categoria.activo;
            const isActive = estadoRaw === true || estadoRaw === 1 || estadoRaw === '1';
            const badgeClass = isActive ? 'text-success bg-success-subtle' : 'text-danger bg-danger-subtle';
            const estadoText = isActive ? 'Activa' : 'Inactiva';
            
            html += `
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 px-3 py-2 border-bottom category-row" data-category-id="${categoria.id}">
                    <div class="flex-grow-1">
                        <div class="fw-semibold mb-1 category-name" style="font-size: 14px;">${categoria.nombre}</div>
                        <div class="small text-secondary" style="font-size: 12px;">${categoria.productos_count} productos asignados</div>
                    </div>
                    <div class="d-flex align-items-center" style="width: 140px;">
                        <span class="badge rounded-pill ${badgeClass}" style="font-size: 10px; padding: 3px 10px;">
                            ${estadoText}
                        </span>
                    </div>
                    <div class="text-end" style="width: 60px;">
                        <a
                            href="/categorias/${categoria.id}/edit"
                            class="text-secondary text-decoration-none p-1 d-inline-flex rounded hover-bg-light"
                            aria-label="Editar categoria"
                            title="Editar">
                            <svg class="fluentui-icon" style="width:16px;height:16px;" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.44 3.44a1.5 1.5 0 0 1 2.12 0l1 1a1.5 1.5 0 0 1 0 2.12l-8.5 8.5a1.5 1.5 0 0 1-.53.35l-3 1a.5.5 0 0 1-.64-.64l1-3c.08-.24.2-.45.35-.53l8.2-8.8zm1.41.71a.5.5 0 0 0-.7 0L5.65 12.65a.5.5 0 0 0-.12.18l-.75 2.25 2.25-.75a.5.5 0 0 0 .18-.12l8.5-8.5a.5.5 0 0 0 0-.71l-1-1z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            `;
        });
        
        listContainer.innerHTML = html;
    }
    
    function renderPagination(pagination) {
        const paginationContainer = document.getElementById('categorias-pagination');
        
        if (pagination.last_page <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        paginationContainer.innerHTML = `
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 px-3 py-2 border-top">
                <div class="text-secondary" style="font-size: 12px;">
                    Mostrando ${pagination.from}-${pagination.to} de ${pagination.total} categorías
                </div>
                <div>
                    ${pagination.links}
                </div>
            </div>
        `;
    }
    
    // Manejar la navegación con el historial del navegador
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search') || '';
        searchInput.value = search;
        searchCategories(search);
    });
});
