@extends('layouts.app-externo')

@section('title', $categoria->nombre . ' - Giacomazzi Glass')

@section('css')
<link rel="stylesheet" href="{{ asset('css/categoria-index.css') }}">
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
                    'filtrosLimpiarUrl' => route('productos.categoria', $categoria->id),
                ])
            </div>
            @endif

            {{-- Grid de productos --}}
            <div class="{{ $variantes->count() > 0 ? 'col-lg-9' : 'col-12' }}" id="productos-container">
                @include('UsoExterno.partials.productos-grid', [
                    'gridBaseUrl' => route('productos.categoria', $categoria->id),
                ])
            </div>

        </div>
    </div>
</section>

{{-- Overlay para el panel de filtros en móvil --}}
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
<script src="{{ asset('js/modules/filtros-categoria.js') }}"></script>
@endsection