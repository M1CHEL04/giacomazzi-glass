<aside class="filtros-sidebar">

    <div class="filtros-loading" id="filtros-loading" aria-hidden="true" aria-label="Cargando filtros">
        <div class="filtros-loading-spinner"></div>
        <span class="filtros-loading-text">Filtrando…</span>
    </div>

    {{-- Encabezado sólo visible en móvil --}}
    <button class="filtros-mobile-close" id="filtros-cerrar-btn" type="button" aria-label="Cerrar filtros">
        <span>Filtros</span>
        <i class="bi bi-x-lg"></i>
    </button>

    {{-- Encabezado con badge y limpiar --}}
    <div class="filtros-header">
        <h2 class="filtros-title">Filtros</h2>
        @php $totalFiltros = collect($filtros)->flatten()->filter()->count() + count($categoriasFiltro ?? []) + (request('buscar') ? 1 : 0); @endphp
        <a href="{{ $filtrosLimpiarUrl }}"
            class="filtros-limpiar"
            style="{{ $totalFiltros > 0 ? '' : 'display:none' }}">
            Limpiar <span class="filtros-badge">{{ $totalFiltros ?: '' }}</span>
        </a>
    </div>

    <form id="filtros-form" method="GET" action="{{ $filtrosLimpiarUrl }}">

        {{-- Búsqueda por texto --}}
        @php $buscarActual = request('buscar', ''); @endphp
        <div class="filtros-buscar {{ $buscarActual ? 'has-value' : '' }}" id="filtros-buscar-wrapper">
            <i class="bi bi-search filtros-buscar-icon"></i>
            <input type="text"
                   id="filtros-buscar-input"
                   name="buscar"
                   class="filtros-buscar-input"
                   placeholder="Buscar producto…"
                   value="{{ $buscarActual }}"
                   autocomplete="off"
                   spellcheck="false">
            <button type="button" class="filtros-buscar-clear" id="filtros-buscar-clear" aria-label="Limpiar búsqueda">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Sección categoría — sólo en "todos los productos" --}}
        @isset($todasCategorias)
        <div class="filtro-grupo">
            <button type="button"
                class="filtro-grupo-toggle"
                data-target="filtro-grupo-categorias"
                aria-expanded="true">
                Categoría
                <i class="bi bi-chevron-down filtro-chevron"></i>
            </button>
            <div class="filtro-grupo-contenido" id="filtro-grupo-categorias">
                @foreach($todasCategorias as $cat)
                <label class="filtro-opcion">
                    <input type="checkbox"
                        name="categorias[]"
                        value="{{ $cat->id }}"
                        {{ in_array($cat->id, $categoriasFiltro ?? []) ? 'checked' : '' }}>
                    <span class="filtro-label">{{ $cat->nombre }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endisset

        {{-- Grupos de variantes --}}
        @foreach($variantes as $variante)
        <div class="filtro-grupo">
            <button type="button"
                class="filtro-grupo-toggle"
                data-target="filtro-grupo-{{ $variante->id }}"
                aria-expanded="true">
                {{ $variante->nombre }}
                <i class="bi bi-chevron-down filtro-chevron"></i>
            </button>

            <div class="filtro-grupo-contenido" id="filtro-grupo-{{ $variante->id }}">
                @foreach($variante->valores as $valor)
                <label class="filtro-opcion">
                    <input type="checkbox"
                        name="variantes[{{ $variante->id }}][]"
                        value="{{ $valor->id }}"
                        {{ isset($filtros[$variante->id]) && in_array($valor->id, (array) $filtros[$variante->id]) ? 'checked' : '' }}>
                    <span class="filtro-label">{{ $valor->valor }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endforeach

    </form>

</aside>
