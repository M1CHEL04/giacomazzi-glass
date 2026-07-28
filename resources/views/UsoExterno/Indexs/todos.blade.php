@extends('layouts.app-externo')

@section('title', 'Todos los Productos - Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/categoria-index.css') }}">
@endsection

@section('content')

<section class="g-hero">
    <img src="{{ asset('images/heros/hero_todos_productos_2.jpg') }}" alt=""
        class="g-hero-bg" aria-hidden="true">
    <span class="g-hero-scrim"></span>
    <div class="container g-hero-inner">
        <p class="g-hero-eyebrow">Aberturas Giacomazzi</p>
        <h1 class="g-hero-title">Catálogo</h1>
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
                'todasCategorias' => $todasCategorias,
                'categoriasFiltro' => $categoriasFiltro,
                ])
            </div>

            {{-- Grid de productos --}}
            <div class="col-lg-9" id="productos-container">
                @include('UsoExterno.partials.productos-grid', [
                'gridBaseUrl' => route('productos.todos'),
                'todasCategorias' => $todasCategorias,
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
<script src="{{ versioned_asset('js/modules/filtros-categoria.js') }}"></script>
@endsection