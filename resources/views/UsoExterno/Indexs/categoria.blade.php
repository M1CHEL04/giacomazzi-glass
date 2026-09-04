@extends('layouts.app-externo')

{{-- Misma vista para el catálogo estándar (indexCategoria) y para una
     categoría de la línea singular (indexEspecialesCategoria): ver el
     comentario equivalente en todos.blade.php. Acá sí hay dos queries
     distintas del lado del controlador (Producto::estandar() vs
     ProductoEspecial), porque el filtro Tipo no existe en esta vista —la
     categoría ya viene fija por la URL— así que no hay forma de elegir el
     tipo *dentro* de la página como en todos.blade.php. --}}
@php
$esLineaSingular = $esLineaSingular ?? false;
$gridBaseUrl = $gridBaseUrl ?? route('productos.categoria', $categoria->id);
@endphp

@section('title', $categoria->nombre . ' - Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/categoria-index.css') }}">
@endsection

@section('content')

@include('UsoExterno.partials.hero-categoria')

<section class="categoria-section">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="categoria-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('welcome') }}">Inicio</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $categoria->nombre }}
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
            @if($variantes->count() > 0)
            <div class="col-12 d-lg-none">
                <button class="btn-filtros-mobile" id="filtros-mobile-btn" type="button">
                    <i class="bi bi-funnel"></i>
                    Filtrar
                    @php $totalFiltros = collect($filtros)->flatten()->filter()->count(); @endphp
                    @if($totalFiltros > 0)
                    <span class="filtros-badge-mobile">{{ $totalFiltros }}</span>
                    @endif
                </button>
            </div>
            @endif

            {{-- Sidebar de filtros --}}
            @if($variantes->count() > 0)
            <div class="col-lg-3">
                @include('UsoExterno.partials.filtros-categoria', [
                'filtrosLimpiarUrl' => $gridBaseUrl,
                ])
            </div>
            @endif

            {{-- Grid de productos --}}
            <div class="{{ $variantes->count() > 0 ? 'col-lg-9' : 'col-12' }}" id="productos-container">
                @include('UsoExterno.partials.productos-grid', [
                'gridBaseUrl' => $gridBaseUrl,
                'esLineaSingular' => $esLineaSingular,
                ])
            </div>

        </div>
    </div>
</section>

{{-- Franja de "también fabricamos X a medida": sólo tiene sentido en la
     categoría estándar (invita a cruzar a la línea singular). Si ya estamos
     viendo la categoría DESDE la línea singular, $especiales no llega —
     mostrar acá los mismos productos que ya están arriba sería redundante. --}}
@if(($especiales ?? collect())->isNotEmpty())
@include('UsoExterno.partials.especiales-seccion')
@endif

@if($variantes->count() > 0)
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
@endif

<div class="filtros-overlay" id="filtros-overlay" aria-hidden="true"></div>

@endsection

@section('script')
<script src="{{ versioned_asset('js/modules/filtros-categoria.js') }}"></script>
@endsection