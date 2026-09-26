@extends('layouts.app-interno')
@section('title', 'Estadisticas - Aberturas Giacomazzi')
@section('page-title', 'Estadisticas')
@section('subhead', 'Resumen general del catálogo y las cotizaciones recibidas')
@section('content')

@php $maxProductos = $productosPorCategoria->max('productos_count') ?: 1; @endphp

<div class="d-flex flex-column gap-4">

    {{-- ── KPIs ───────────────────────────────────────────────────────────── --}}
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <span class="stat-card-icon"><x-heroicon-o-cube /></span>
                <div class="stat-card-body">
                    <span class="stat-card-value">{{ $totalProductos }}</span>
                    <span class="stat-card-label">Productos línea estandar</span>
                    <span class="stat-card-meta">{{ $productosActivos }} activos · {{ $productosInactivos }} inactivos</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <span class="stat-card-icon"><x-heroicon-o-sparkles /></span>
                <div class="stat-card-body">
                    <span class="stat-card-value">{{ $totalEspeciales }}</span>
                    <span class="stat-card-label">Productos línea adapta</span>
                    <span class="stat-card-meta">{{ $especialesActivos }} activos · {{ $totalEspeciales - $especialesActivos }} inactivos</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            @if($productosSinImagen > 0)
            <button type="button" class="stat-card stat-card--warn stat-card--action"
                data-bs-toggle="modal" data-bs-target="#modalSinImagen">
                <span class="stat-card-icon"><x-heroicon-o-photo /></span>
                <span class="stat-card-body">
                    <span class="stat-card-value">{{ $productosSinImagen }}</span>
                    <span class="stat-card-label">Productos sin imagen</span>
                    <span class="stat-card-meta">Ver lista</span>
                </span>
            </button>
            @else
            <div class="stat-card">
                <span class="stat-card-icon"><x-heroicon-o-photo /></span>
                <div class="stat-card-body">
                    <span class="stat-card-value">0</span>
                    <span class="stat-card-label">Productos sin imagen</span>
                    <span class="stat-card-meta">Todo el catálogo con imagen</span>
                </div>
            </div>
            @endif
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <span class="stat-card-icon"><x-heroicon-o-chat-bubble-left-right /></span>
                <div class="stat-card-body">
                    <span class="stat-card-value">{{ $totalConsultas }}</span>
                    <span class="stat-card-label">Cotizaciones solicitadas</span>
                    <span class="stat-card-meta">{{ $consultasMes }} este mes</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Paneles ────────────────────────────────────────────────────────── --}}
    <div class="row g-3">
        {{-- Cotizaciones por mes --}}
        <div class="col-12 col-lg-6">
            <div class="internal-content-card h-100">
                <div class="dash-panel-head">
                    <h2 class="dash-panel-title">Cotizaciones por mes</h2>
                </div>

                @if($cotizacionesMensuales->isEmpty())
                <div class="dash-empty">Todavía no hay cotizaciones registradas.</div>
                @else
                {{-- Serie única (magnitud en el tiempo): un solo hue verde, sin leyenda.
                     Eje Y a la izquierda (fijo); las barras scrollean en X cuando no entran. --}}
                <div class="dash-chart">
                    <div class="dash-chart-yaxis" aria-hidden="true">
                        <span class="dash-chart-ytick">{{ $maxCotizMes }}</span>
                        <span class="dash-chart-ytick">{{ intdiv($maxCotizMes, 2) }}</span>
                        <span class="dash-chart-ytick">0</span>
                    </div>
                    <div class="dash-chart-scroll">
                        <div class="dash-chart-cols">
                            @foreach($cotizacionesMensuales as $m)
                            @php $h = $m['total'] == 0 ? 0 : max(6, round($m['total'] / $maxCotizMes * 100)); @endphp
                            <div class="dash-chart-col"
                                data-total="{{ $m['total'] }}"
                                data-mes="{{ $m['mesLargo'] }}"
                                data-anio="{{ $m['anio'] }}"
                                aria-label="{{ $m['mesLargo'] }} {{ $m['anio'] }}: {{ $m['total'] }} {{ $m['total'] == 1 ? 'cotización' : 'cotizaciones' }}">
                                <div class="dash-chart-track">
                                    <div class="dash-chart-bar {{ $m['total'] == 0 ? 'dash-chart-bar--zero' : '' }}"
                                        style="height: {{ $h }}%;"></div>
                                </div>
                                <span class="dash-chart-xlabel">{{ $m['mes'] }}</span>
                                <span class="dash-chart-year">{{ ($loop->first || $m['esEnero']) ? $m['anio'] : '' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Productos por categoría --}}
        <div class="col-12 col-lg-6">
            <div class="internal-content-card h-100">
                <div class="dash-panel-head">
                    <h2 class="dash-panel-title">Productos por categoría</h2>
                </div>
                <div class="dash-bars">
                    @forelse($productosPorCategoria as $cat)
                    <div class="dash-bar-row">
                        <span class="dash-bar-label" title="{{ $cat->nombre }}">{{ $cat->nombre }}</span>
                        <span class="dash-bar-track">
                            <span class="dash-bar-fill"
                                style="width: {{ $cat->productos_count == 0 ? 0 : max(6, round($cat->productos_count / $maxProductos * 100)) }}%;"></span>
                        </span>
                        <span class="dash-bar-count">{{ $cat->productos_count }}</span>
                    </div>
                    @empty
                    <div class="dash-empty">No hay categorías cargadas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

@if($productosSinImagen > 0)
<div class="modal fade" id="modalSinImagen" tabindex="-1" aria-labelledby="modalSinImagenLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:540px;">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0 align-items-start pb-2">
                <div class="flex-grow-1">
                    <h2 class="internal-import-result-title m-0" id="modalSinImagenLabel">
                        {{ $productosSinImagen === 1 ? '1 producto sin imagen' : $productosSinImagen . ' productos sin imagen' }}
                    </h2>
                    <div class="internal-import-result-sub">Tocá un producto para ir a cargarle la foto.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-1 d-flex flex-column gap-4">
                @foreach($sinImagenPorLinea as $linea => $categorias)
                <section>
                    <h3 class="internal-import-section">
                        {{ $linea }} <span class="internal-import-count">{{ $categorias->sum->count() }}</span>
                    </h3>

                    @forelse($categorias as $categoria => $productos)
                    <h4 class="sin-imagen-cat">{{ $categoria }}</h4>
                    <ul class="internal-import-created list-unstyled m-0">
                        @foreach($productos as $p)
                        <li>
                            <a class="internal-import-created-item text-decoration-none"
                                href="{{ $p->es_especial ? route('uso-interno.especiales.edit', $p->id) : route('uso-interno.productos.edit', $p->id) }}">
                                @if($p->codigo)
                                <code class="internal-import-error-code">{{ $p->codigo }}</code>
                                @endif
                                <span class="internal-import-created-name" title="{{ $p->nombre }}">{{ $p->nombre }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                    @empty
                    <div class="sin-imagen-vacio">Todos los productos tienen imagen.</div>
                    @endforelse
                </section>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('script')
<script src="{{ versioned_asset('js/dashboard.js') }}"></script>
@endsection