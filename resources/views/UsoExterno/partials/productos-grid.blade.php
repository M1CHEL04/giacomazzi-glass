@php
// Construir chips de filtros activos con URL para eliminar cada uno
$chipsActivos = [];

// Chips de categorías (sólo en "todos los productos", cuando $categoriasFiltro está definido)
foreach (($categoriasFiltro ?? []) as $catId) {
    $cat = ($todasCategorias ?? collect())->firstWhere('id', (int) $catId);
    if (!$cat) continue;
    $sinEsta = array_values(array_filter($categoriasFiltro, fn($c) => (int) $c !== (int) $catId));
    $params = [];
    if (!empty($filtros)) $params['variantes'] = $filtros;
    if (!empty($sinEsta)) $params['categorias'] = $sinEsta;
    $chipsActivos[] = [
        'label' => $cat->nombre,
        'url'   => $gridBaseUrl . (empty($params) ? '' : '?' . http_build_query($params)),
    ];
}

// Chips de variantes
foreach (($filtros ?? []) as $varianteId => $valores) {
    $variante = $variantes->firstWhere('id', (int) $varianteId);
    if (!$variante) continue;
    foreach ((array) $valores as $valorId) {
        $valor = $variante->valores->firstWhere('id', (int) $valorId);
        if (!$valor) continue;
        $sinEste = $filtros;
        $sinEste[$varianteId] = array_values(
            array_filter((array) $sinEste[$varianteId], fn ($v) => (int) $v !== (int) $valorId)
        );
        if (empty($sinEste[$varianteId])) unset($sinEste[$varianteId]);
        $params = [];
        if (!empty($sinEste)) $params['variantes'] = $sinEste;
        if (!empty($categoriasFiltro ?? [])) $params['categorias'] = $categoriasFiltro;
        $chipsActivos[] = [
            'label' => $variante->nombre . ': ' . $valor->valor,
            'url'   => $gridBaseUrl . (empty($params) ? '' : '?' . http_build_query($params)),
        ];
    }
}
@endphp

@if(!empty($chipsActivos))
<div class="filtros-activos">
    @foreach($chipsActivos as $chip)
    <a href="{{ $chip['url'] }}" class="filtro-chip">
        {{ $chip['label'] }} <i class="bi bi-x"></i>
    </a>
    @endforeach
    <a href="{{ $gridBaseUrl }}" class="filtro-chip-limpiar">Limpiar todo</a>
</div>
@endif

@if($productos->count() > 0)

<div class="productos-grid-header">
    <span class="productos-count">
        {{ $productos->total() }} {{ $productos->total() === 1 ? 'resultado' : 'resultados' }}
    </span>
    @if($productos->hasPages())
    <span class="productos-pagina-info">
        Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }} de {{ $productos->total() }}
    </span>
    @endif
</div>

<div class="row g-4">
    @foreach($productos as $producto)
    <div class="col-sm-6 col-xl-4">
        <article class="producto-card" style="--card-delay: {{ $loop->index * 0.055 }}s">
            <div class="producto-card-imagen">
                @php $imagenPrincipal = $producto->imagenes->first(); @endphp
                @if($imagenPrincipal && $imagenPrincipal->ruta)
                <img src="{{ $imagenPrincipal->ruta }}"
                    alt="{{ $producto->nombre }}"
                    class="producto-img"
                    loading="lazy">
                @else
                <div class="producto-img-placeholder">
                    <i class="bi bi-image"></i>
                </div>
                @endif
            </div>
            <div class="producto-card-body">
                {{-- Tag de categoría — sólo en "todos los productos" --}}
                @isset($todasCategorias)
                @if($producto->categoria)
                <span class="producto-categoria-tag">{{ $producto->categoria->nombre }}</span>
                @endif
                @endisset
                <h3 class="producto-nombre">{{ $producto->nombre }}</h3>
                <p class="producto-descripcion">{{ Str::limit($producto->descripcion, 100) }}</p>
                <span class="producto-cta">
                    Ver detalles <i class="bi bi-arrow-right"></i>
                </span>
            </div>
            <a href="{{ route('productos.show', $producto->id) }}" class="stretched-link" aria-label="{{ $producto->nombre }}"></a>
        </article>
    </div>
    @endforeach
</div>

@if($productos->hasPages())
<nav class="productos-pagination" aria-label="Paginación de productos">
    {{ $productos->links('pagination::bootstrap-5') }}
</nav>
@endif

{{-- Énfasis personalización --}}
<div class="grid-personalizar-banner">
    <span class="grid-personalizar-icono"><i class="bi bi-stars"></i></span>
    <p>
        <strong>Todos nuestros productos pueden ser fabricados a medida.</strong>
        ¿Necesitás otras medidas, colores o terminaciones?
        <a href="{{ route('contacto') }}">Consultanos</a> y lo producimos especialmente para vos.
    </p>
</div>

@else

<div class="productos-empty">
    <div class="productos-empty-icon">
        <i class="bi bi-search"></i>
    </div>
    <h3 class="productos-empty-title">No se encontraron productos</h3>
    <p class="productos-empty-text">
        @if(!empty(array_filter($filtros ?? [])) || !empty($categoriasFiltro ?? []))
        Probá ajustando los filtros para ver más resultados.
        @else
        Todavía no hay productos en esta sección.
        @endif
    </p>
    @if(!empty(array_filter($filtros ?? [])) || !empty($categoriasFiltro ?? []))
    <a href="{{ $gridBaseUrl }}" class="btn-limpiar-filtros">Limpiar filtros</a>
    @endif
</div>

@endif
