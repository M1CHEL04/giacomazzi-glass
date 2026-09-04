@extends('layouts.app-externo')

@php
$esLineaSingular = $esLineaSingular ?? false;
// Sólo lo pasa indexTodos() cuando se llama directo (no vía la entrada de
// línea singular, que siempre lo manda): fallback por las dudas.
$gridBaseUrl = $gridBaseUrl ?? route('productos.todos');
@endphp

@section('title', $esLineaSingular ? 'Línea adapta - Aberturas Giacomazzi' : 'Todos los Productos - Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/categoria-index.css') }}">
<link rel="preload" as="image"
    href="{{ imagen_src('images/heros/hero_todos_productos_2.jpg', 1400) }}"
    imagesrcset="{{ imagen_srcset('images/heros/hero_todos_productos_2.jpg') }}"
    imagesizes="100vw" fetchpriority="high">
@endsection

@section('content')

<section class="g-hero">
    <img src="{{ imagen_src('images/heros/hero_todos_productos_2.jpg', 1400) }}"
        srcset="{{ imagen_srcset('images/heros/hero_todos_productos_2.jpg') }}"
        sizes="100vw"
        alt="" class="g-hero-bg" aria-hidden="true"
        width="1584" height="672"
        fetchpriority="high" decoding="async">
    <span class="g-hero-scrim"></span>
    <div class="container g-hero-inner">
        <p class="g-hero-eyebrow">Aberturas Giacomazzi</p>
        <h1 class="g-hero-title">{{ $esLineaSingular ? 'Línea adapta' : 'Línea estándar' }}</h1>
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
                    {{ $esLineaSingular ? 'Línea adapta' : 'Línea estándar' }}
                </li>
            </ol>
        </nav>

        {{-- Qué es la línea singular, en un renglón: la única diferencia de
             contenido entre las dos líneas en toda esta vista. --}}
        @if($esLineaSingular)
        <div class="singular-linea-alert">
            <span class="singular-linea-alert-icono"><i class="bi bi-stars"></i></span>
            <p>
                <strong>Línea adapta.</strong>
                Productos que no se fabrican en serie ni tienen medidas fijas.
                Los producimos por encargo y podemos ajustar dimensiones o
                detalles según lo que requiera tu espacio.
            </p>
        </div>
        @endif

        <div class="row g-4">

            {{-- Botón filtros sólo en móvil --}}
            <div class="col-12 d-lg-none">
                <button class="btn-filtros-mobile" id="filtros-mobile-btn" type="button">
                    <i class="bi bi-funnel"></i>
                    Filtrar
                    @php $totalFiltros = collect($filtros)->flatten()->filter()->count() + count($categoriasFiltro) + count($tipos); @endphp
                    @if($totalFiltros > 0)
                    <span class="filtros-badge-mobile">{{ $totalFiltros }}</span>
                    @endif
                </button>
            </div>

            {{-- Sidebar de filtros: exactamente los mismos que en la línea
                 estándar, Tipo incluido (acá puede venir pre-tildado en
                 "Línea singular", pero el usuario lo puede cambiar como en
                 cualquier otro filtro). --}}
            <div class="col-lg-3">
                @include('UsoExterno.partials.filtros-categoria', [
                'filtrosLimpiarUrl' => $gridBaseUrl,
                'todasCategorias' => $todasCategorias,
                'categoriasFiltro' => $categoriasFiltro,
                'tipos' => $tipos,
                ])
            </div>

            {{-- Grid de productos --}}
            <div class="col-lg-9" id="productos-container">
                @include('UsoExterno.partials.productos-grid', [
                'gridBaseUrl' => $gridBaseUrl,
                'todasCategorias' => $todasCategorias,
                'categoriasFiltro' => $categoriasFiltro,
                'tipos' => $tipos,
                'esLineaSingular' => $esLineaSingular,
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