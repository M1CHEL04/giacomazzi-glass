@extends('layouts.app-externo')

@section('title', 'Todos los Productos - Giacomazzi Glass')

@section('css')
<link rel="stylesheet" href="{{ asset('css/categoria-index.css') }}">
@endsection

@section('content')

{{-- Hero del catálogo completo --}}
<section class="todos-hero">
    <div class="container">
        <div class="todos-hero-content">
            <p class="todos-hero-eyebrow">
                <span class="todos-hero-eyebrow-line"></span>
                Giacomazzi Glass
            </p>
            <h1 class="todos-hero-title">Catálogo <em class="todos-hero-accent">Completo</em></h1>
            <p class="todos-hero-subtitle">
                Explorá toda nuestra línea de productos en vidrio y carpintería de aluminio.
            </p>
            <div class="todos-hero-stats">
                <div class="todos-hero-stat">
                    <span class="todos-hero-stat-value">{{ $productos->total() }}</span>
                    <span class="todos-hero-stat-label">{{ $productos->total() === 1 ? 'producto' : 'productos' }}</span>
                </div>
                <span class="todos-hero-stat-sep" aria-hidden="true">·</span>
                <div class="todos-hero-stat">
                    <span class="todos-hero-stat-value">{{ $todasCategorias->count() }}</span>
                    <span class="todos-hero-stat-label">{{ $todasCategorias->count() === 1 ? 'categoría' : 'categorías' }}</span>
                </div>
                @if(!empty($categoriasFiltro) || !empty(array_filter($filtros)))
                <span class="todos-hero-stat-sep" aria-hidden="true">·</span>
                <div class="todos-hero-stat todos-hero-stat--filtered">
                    <i class="bi bi-funnel-fill"></i>
                    Filtros activos
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="categoria-section">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="categoria-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('welcome') }}">Inicio</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Todos los productos
                </li>
            </ol>
        </nav>

        <div class="row g-4">

            {{-- Botón filtros sólo en móvil --}}
            <div class="col-12 d-lg-none">
                <button class="btn-filtros-mobile" id="filtros-mobile-btn" type="button">
                    <i class="bi bi-funnel"></i>
                    Filtrar
                    @php $totalFiltros = collect($filtros)->flatten()->filter()->count() + count($categoriasFiltro); @endphp
                    @if($totalFiltros > 0)
                    <span class="filtros-badge-mobile">{{ $totalFiltros }}</span>
                    @endif
                </button>
            </div>

            {{-- Sidebar de filtros --}}
            <div class="col-lg-3">
                @include('UsoExterno.partials.filtros-categoria', [
                    'filtrosLimpiarUrl' => route('productos.todos'),
                    'todasCategorias'   => $todasCategorias,
                    'categoriasFiltro'  => $categoriasFiltro,
                ])
            </div>

            {{-- Grid de productos --}}
            <div class="col-lg-9" id="productos-container">
                @include('UsoExterno.partials.productos-grid', [
                    'gridBaseUrl'      => route('productos.todos'),
                    'todasCategorias'  => $todasCategorias,
                    'categoriasFiltro' => $categoriasFiltro,
                ])
            </div>

        </div>
    </div>
</section>

<div class="filtros-sticky-bar" id="filtros-sticky-bar">
    <div class="container">
        <div class="filtros-sticky-bar-inner">
            <span class="filtros-sticky-count" id="filtros-sticky-count"></span>
            <button type="button" class="filtros-sticky-bar-btn" id="filtros-sticky-btn" aria-label="Abrir filtros">
                <i class="bi bi-sliders2"></i>
                <span class="filtros-sticky-badge" id="filtros-sticky-badge"></span>
            </button>
        </div>
    </div>
</div>

<div class="filtros-overlay" id="filtros-overlay" aria-hidden="true"></div>

@endsection

@section('script')
<script src="{{ asset('js/modules/filtros-categoria.js') }}"></script>
@endsection
