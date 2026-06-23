@extends('layouts.app-externo')

@section('title', $producto->nombre . ' - Giacomazzi Glass')

@section('css')
<link rel="stylesheet" href="{{ asset('css/categoria-index.css') }}">
<link rel="stylesheet" href="{{ asset('css/producto-show.css') }}">
@endsection

@section('content')

<section class="ps-section">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="categoria-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('welcome') }}">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('productos.categoria', $producto->categoria_id) }}">{{ $producto->categoria->nombre }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $producto->nombre }}
                </li>
            </ol>
        </nav>

        <div class="row g-4 g-lg-5">

            {{-- Galería --}}
            <div class="col-lg-6">
                @php $imagenes = $producto->imagenes; @endphp
                <div class="ps-galeria">

                    <div id="ps-carousel" class="carousel slide ps-carousel" data-bs-ride="false" data-bs-touch="true">
                        <div class="carousel-inner">
                            @if($imagenes->count() > 0)
                            @foreach($imagenes as $imagen)
                            <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                <img src="{{ route('imagen.show', $imagen) }}"
                                    alt="{{ $producto->nombre }}"
                                    class="ps-carousel-img"
                                    title="Clic para ampliar">
                            </div>
                            @endforeach
                            @else
                            <div class="carousel-item active">
                                <div class="ps-galeria-placeholder">
                                    <x-heroicon-o-photo />
                                </div>
                            </div>
                            @endif
                        </div>

                        @if($imagenes->count() > 1)
                        <button class="carousel-control-prev ps-carousel-prev" type="button" data-bs-target="#ps-carousel" data-bs-slide="prev" aria-label="Anterior">
                            <x-heroicon-o-chevron-left />
                        </button>
                        <button class="carousel-control-next ps-carousel-next" type="button" data-bs-target="#ps-carousel" data-bs-slide="next" aria-label="Siguiente">
                            <x-heroicon-o-chevron-right />
                        </button>
                        @endif

                        @if($imagenes->count() > 0)
                        <button class="ps-ampliar-btn" id="ps-ampliar-btn" aria-label="Ampliar imagen">
                            <x-heroicon-o-arrows-pointing-out />
                        </button>
                        @endif
                    </div>

                    @if($imagenes->count() > 1)
                    <div class="ps-galeria-thumbs" role="tablist" aria-label="Imágenes del producto">
                        @foreach($imagenes as $imagen)
                        <button type="button"
                            class="ps-thumb {{ $loop->first ? 'active' : '' }}"
                            data-bs-target="#ps-carousel"
                            data-bs-slide-to="{{ $loop->index }}"
                            aria-label="Ver imagen {{ $loop->iteration }}">
                            <img src="{{ route('imagen.show', $imagen) }}" alt="" loading="lazy">
                        </button>
                        @endforeach
                    </div>
                    @endif

                </div>
            </div>

            {{-- Información --}}
            <div class="col-lg-6">
                <div class="ps-info">

                    {{-- Categoría como eyebrow (sin badge/chip) --}}
                    <a href="{{ route('productos.categoria', $producto->categoria_id) }}" class="ps-categoria-eyebrow">
                        {{ $producto->categoria->nombre }}
                    </a>

                    <h1 class="ps-titulo">{{ $producto->nombre }}</h1>

                    @if($producto->descripcion)
                    <p class="ps-descripcion">{{ $producto->descripcion }}</p>
                    @endif

                    {{-- Selector de variantes --}}
                    @if($selectorVariantes->isNotEmpty())
                    <div class="ps-variantes" id="ps-variantes">
                        @foreach($selectorVariantes as $selector)
                        <div class="ps-variante" data-variante="{{ $selector['nombre'] }}">
                            <span class="ps-variante-label">
                                {{ $selector['nombre'] }}
                                @if(count($selector['opciones']) > 1)
                                <span class="ps-variante-seleccion" id="ps-sel-{{ $loop->index }}">— {{ $selector['opciones'][0]['valor'] }}</span>
                                @endif
                            </span>
                            <div class="ps-variante-opciones">
                                @foreach($selector['opciones'] as $opcion)
                                <button type="button"
                                    class="ps-opcion {{ $loop->first ? 'active' : '' }}"
                                    data-valor-id="{{ $opcion['id'] }}"
                                    data-label-id="ps-sel-{{ $loop->parent->index }}">
                                    {{ $opcion['valor'] }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Callout personalización --}}
                    <div class="ps-personalizar">
                        <span class="ps-personalizar-icono">
                            <x-heroicon-o-sparkles />
                        </span>
                        <div class="ps-personalizar-texto">
                            <strong>¿Necesitás otras medidas, colores o terminaciones?</strong>
                            Todos nuestros productos se fabrican completamente a medida y pueden adaptarse a tus requerimientos específicos.
                            <a href="{{ route('contacto') }}" class="ps-personalizar-link">Consultanos sin compromiso</a>
                        </div>
                    </div>
                    @endif

                    <div class="ps-acciones">
                        <button type="button"
                            id="btn-agregar-carrito"
                            class="btn-ps-primary"
                            data-producto-id="{{ $producto->id }}">
                            <span id="btn-carrito-icon"><x-heroicon-o-shopping-cart /></span>
                            <span class="spinner-border spinner-border-sm d-none"
                                id="btn-carrito-spinner" role="status" aria-hidden="true"></span>
                            <span id="btn-carrito-text">Agregar al carrito</span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

{{-- Lightbox --}}
@if($imagenes->count() > 0)
<dialog id="ps-lightbox" class="ps-lightbox">
    <div class="ps-lightbox-inner">
        <button class="ps-lightbox-close" id="ps-lightbox-close" aria-label="Cerrar">
            <x-heroicon-o-x-mark />
        </button>
        <div class="ps-lightbox-img-wrap">
            <img id="ps-lightbox-img" src="" alt="">
        </div>
        @if($imagenes->count() > 1)
        <div class="ps-lightbox-thumbs">
            @foreach($imagenes as $imagen)
            <button type="button" class="ps-lightbox-thumb {{ $loop->first ? 'active' : '' }}" data-index="{{ $loop->index }}" data-src="{{ route('imagen.show', $imagen) }}">
                <img src="{{ route('imagen.show', $imagen) }}" alt="" loading="lazy">
            </button>
            @endforeach
        </div>
        @endif
    </div>
</dialog>
@endif

{{-- Descripción técnica --}}
@if($producto->descripcion_tecnica)
<section class="ps-tecnica-section">
    <div class="container">
        <p class="ps-seccion-eyebrow">Ficha técnica</p>
        <h2 class="ps-seccion-titulo">Descripción técnica</h2>
        <p class="ps-tecnica-texto">{{ $producto->descripcion_tecnica }}</p>
    </div>
</section>
@endif

{{-- Productos relacionados --}}
@if($relacionados->count() > 0)
<section class="ps-relacionados-section">
    <div class="container">

        <div class="ps-relacionados-header">
            <div>
                <p class="ps-seccion-eyebrow">Seguí explorando</p>
                <h2 class="ps-seccion-titulo">Más en {{ $producto->categoria->nombre }}</h2>
            </div>
            <a href="{{ route('productos.categoria', $producto->categoria_id) }}" class="ps-relacionados-link">
                Ver categoría
                <x-heroicon-o-arrow-right />
            </a>
        </div>

        <div class="row g-3">
            @foreach($relacionados as $relacionado)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('productos.show', $relacionado->id) }}" class="ps-mini-card">
                    <div class="ps-mini-imagen">
                        @php $imagenPrincipal = $relacionado->imagenes->first(); @endphp
                        @if($imagenPrincipal && $imagenPrincipal->ruta)
                        <img src="{{ route('imagen.show', $imagenPrincipal) }}" alt="{{ $relacionado->nombre }}" loading="lazy">
                        @else
                        <span class="ps-mini-placeholder">
                            <x-heroicon-o-photo />
                        </span>
                        @endif
                    </div>
                    <div class="ps-mini-body">
                        <span class="ps-mini-nombre">{{ $relacionado->nombre }}</span>
                    </div>
                    <span class="ps-mini-arrow">
                        <x-heroicon-o-arrow-right />
                    </span>
                </a>
            </div>
            @endforeach
        </div>

    </div>
</section>
@endif

{{-- Banner personalización --}}
<section class="ps-banner-personalizar">
    <div class="container">
        <div class="ps-banner-personalizar-inner">
            <div class="ps-banner-personalizar-icon">
                <x-heroicon-o-squares-2x2 />
            </div>
            <div class="ps-banner-personalizar-content">
                <h3 class="ps-banner-personalizar-titulo">Fabricación completamente a medida</h3>
                <p class="ps-banner-personalizar-desc">
                    Además de las variantes disponibles, podemos fabricar este producto con cualquier medida, color, material o terminación que necesités. Cada pieza se produce en nuestro taller con total flexibilidad.
                </p>
            </div>
            <a href="{{ route('contacto') }}" class="ps-banner-personalizar-cta">
                Consultar personalización
                <x-heroicon-o-arrow-right />
            </a>
        </div>
    </div>
</section>

@endsection

@section('script')
<script src="{{ asset('js/modules/producto-show.js') }}"></script>
@endsection