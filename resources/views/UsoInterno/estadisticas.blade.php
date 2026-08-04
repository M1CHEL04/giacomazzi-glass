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
                    <span class="stat-card-label">Productos</span>
                    <span class="stat-card-meta">{{ $productosActivos }} activos · {{ $productosInactivos }} inactivos</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <span class="stat-card-icon"><x-heroicon-o-squares-2x2 /></span>
                <div class="stat-card-body">
                    <span class="stat-card-value">{{ $totalCategorias }}</span>
                    <span class="stat-card-label">Categorías</span>
                    <span class="stat-card-meta">{{ $categoriasActivas }} activas</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card {{ $productosSinImagen > 0 ? 'stat-card--warn' : '' }}">
                <span class="stat-card-icon"><x-heroicon-o-photo /></span>
                <div class="stat-card-body">
                    <span class="stat-card-value">{{ $productosSinImagen }}</span>
                    <span class="stat-card-label">Productos sin imagen</span>
                    <span class="stat-card-meta">
                        {{ $productosSinImagen > 0 ? 'Requieren carga de foto' : 'Todo el catálogo con imagen' }}
                    </span>
                </div>
            </div>
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
@endsection

@section('script')
<script src="{{ versioned_asset('js/dashboard.js') }}"></script>
@endsection