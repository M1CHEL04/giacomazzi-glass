@php
// Construir chips de filtros activos con URL para eliminar cada uno
$chipsActivos = [];
foreach (($filtros ?? []) as $varianteId => $valores) {
$variante = $variantes->firstWhere('id', (int) $varianteId);
if (! $variante) continue;
foreach ((array) $valores as $valorId) {
$valor = $variante->valores->firstWhere('id', (int) $valorId);
if (! $valor) continue;
$sinEste = $filtros;
$sinEste[$varianteId] = array_values(
array_filter((array) $sinEste[$varianteId], fn ($v) => (int) $v !== (int) $valorId)
);
if (empty($sinEste[$varianteId])) unset($sinEste[$varianteId]);
$chipsActivos[] = [
'label' => $variante->nombre . ': ' . $valor->valor,
'url' => route('productos.categoria', $categoria->id) . (empty($sinEste) ? '' : '?' . http_build_query(['variantes' => $sinEste])),
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
    <a href="{{ route('productos.categoria', $categoria->id) }}" class="filtro-chip-limpiar">Limpiar todo</a>
</div>
@endif

@if($productos->count() > 0)

<div class="productos-grid-header">
    <span class="productos-count">
        {{ $productos->total() }} {{ $productos->total() === 1 ? 'resultado' : 'resultados' }}
    </span>
</div>

<div class="row g-4">
    @foreach($productos as $producto)
    <div class="col-sm-6 col-xl-4">
        {{-- style var permite el stagger de animación en CSS --}}
        <article class="producto-card" style="--card-delay: {{ $loop->index * 0.055 }}s">
            <div class="producto-card-imagen">
                @php $imagenPrincipal = $producto->imagenes->first(); @endphp
                @if($imagenPrincipal && $imagenPrincipal->ruta)
                {{-- ruta almacena URL completa --}}
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
                <span class="producto-codigo">{{ $producto->codigo }}</span>
                <h3 class="producto-nombre">{{ $producto->nombre }}</h3>
                <p class="producto-descripcion">{{ Str::limit($producto->descripcion, 100) }}</p>
                <a href="#" class="producto-cta">
                    Ver detalles <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </article>
    </div>
    @endforeach
</div>

@if($productos->hasPages())
<div class="mt-5 d-flex justify-content-center">
    {{ $productos->links('pagination::bootstrap-5') }}
</div>
@endif

@else

<div class="productos-empty">
    <div class="productos-empty-icon">
        <i class="bi bi-search"></i>
    </div>
    <h3 class="productos-empty-title">No se encontraron productos</h3>
    <p class="productos-empty-text">
        @if(!empty(array_filter($filtros)))
        Probá ajustando los filtros para ver más resultados.
        @else
        Todavía no hay productos en esta categoría.
        @endif
    </p>
    @if(!empty(array_filter($filtros)))
    <a href="{{ route('productos.categoria', $categoria->id) }}" class="btn-limpiar-filtros">Limpiar filtros</a>
    @endif
</div>

@endif