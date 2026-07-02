@extends('layouts.app-interno')
@section('title', 'Panel Interno - Giacomazzi Glass')
@section('page-title', 'Panel Interno')
@section('subhead', 'Bienvenido al panel de administración de Giacomazzi Glass')
@section('content')

@php $maxProductos = $productosPorCategoria->max('productos_count') ?: 1; @endphp

<div class="d-flex flex-column gap-4">

    {{-- ── KPIs ───────────────────────────────────────────────────────────── --}}
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <span class="stat-card-icon"><x-heroicon-o-cube /></span>
                <div class="stat-card-body">
                    <span class="stat-card-value">{{ $totalProductos }}</span>
                    <span class="stat-card-label">Productos</span>
                    <span class="stat-card-meta">{{ $productosActivos }} activos · {{ $productosInactivos }} inactivos</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="stat-card">
                <span class="stat-card-icon"><x-heroicon-o-squares-2x2 /></span>
                <div class="stat-card-body">
                    <span class="stat-card-value">{{ $totalCategorias }}</span>
                    <span class="stat-card-label">Categorías</span>
                    <span class="stat-card-meta">{{ $categoriasActivas }} activas</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
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
    </div>

    {{-- ── Paneles ────────────────────────────────────────────────────────── --}}
    <div class="row g-3">
        {{-- Últimos productos --}}
        <div class="col-12 col-lg-6">
            <div class="internal-content-card h-100">
                <div class="dash-panel-head">
                    <h2 class="dash-panel-title">Últimos productos</h2>
                    <a href="{{ route('uso-interno.productos.index') }}" class="dash-panel-link">Ver todos</a>
                </div>
                <div class="dash-list">
                    @forelse($ultimosProductos as $producto)
                    <a href="{{ route('uso-interno.productos.edit', $producto->id) }}" class="dash-list-item">
                        <span class="dash-status-dot {{ $producto->activo ? 'is-active' : 'is-inactive' }}"
                            title="{{ $producto->activo ? 'Activo' : 'Inactivo' }}"></span>
                        <span class="dash-list-title">{{ $producto->nombre }}</span>
                        <span class="dash-list-date">{{ $producto->created_at->diffForHumans() }}</span>
                    </a>
                    @empty
                    <div class="dash-empty">Todavía no hay productos cargados.</div>
                    @endforelse
                </div>
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