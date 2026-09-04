@extends('layouts.app-externo')
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

            {{-- Sidebar de filtros: siempre presente, igual que en todos.blade.php.
                 Sin variantes (línea adapta por categoría) filtros-categoria.blade.php
                 ya se achica solo a la búsqueda por texto, así que no hace falta
                 ocultar nada acá. --}}
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

            <div class="col-lg-3">
                @include('UsoExterno.partials.filtros-categoria', [
                'filtrosLimpiarUrl' => $gridBaseUrl,
                ])
            </div>

            {{-- Grid de productos --}}
            <div class="col-lg-9" id="productos-container">
                @include('UsoExterno.partials.productos-grid', [
                'gridBaseUrl' => $gridBaseUrl,
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