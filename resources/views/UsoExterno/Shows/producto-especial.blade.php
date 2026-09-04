@extends('layouts.app-externo')

@section('title', $producto->nombre . ' - Línea adapta - Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/categoria-index.css') }}">
<link rel="stylesheet" href="{{ versioned_asset('css/producto-show.css') }}">
<link rel="stylesheet" href="{{ versioned_asset('css/producto-especial.css') }}">
@endsection

@section('content')

@php
$imagenes = $producto->imagenes;
$tecnicas = $producto->imagenesTecnicas;
$mensajeWa = '¡Hola! Quiero consultar por «' . $producto->nombre . '» ('
. $producto->categoria->nombre . ').';
@endphp

<section class="ps-section pe-section">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="categoria-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('welcome') }}">Inicio</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('productos.especiales') }}">Línea adapta</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $producto->nombre }}
                </li>
            </ol>
        </nav>

        <p class="g-eyebrow pe-linea-eyebrow">Línea adapta · fabricación a medida</p>

        <div class="row g-4 g-lg-5">

            {{-- Galería: mismo markup e ids que la ficha estándar, para que
                 producto-show.js funcione sin tocarlo. --}}
            <div class="col-lg-6">
                <div class="ps-galeria pe-galeria">

                    <div id="ps-carousel" class="carousel slide ps-carousel" data-bs-ride="false" data-bs-touch="true">
                        <div class="carousel-inner">
                            @if($imagenes->count() > 0)
                            @foreach($imagenes as $imagen)
                            <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                <img src="{{ $imagen->ruta }}"
                                    alt="{{ $producto->nombre }}"
                                    class="ps-carousel-img"
                                    width="1200" height="900"
                                    decoding="async"
                                    @if($loop->first) fetchpriority="high" @else loading="lazy" @endif
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
                            <img src="{{ $imagen->ruta_miniatura }}" alt="" width="58" height="58" loading="lazy" decoding="async">
                        </button>
                        @endforeach
                    </div>
                    @endif

                </div>
            </div>

            {{-- Panel de consulta --}}
            <div class="col-lg-6">
                <div class="ps-info pe-info">

                    <a href="{{ route('productos.categoria', $producto->categoria_id) }}" class="ps-categoria-eyebrow pe-categoria">
                        {{ $producto->categoria->nombre }}
                    </a>

                    <h1 class="pe-titulo">{{ $producto->nombre }}</h1>

                    @if($producto->descripcion)
                    <p class="pe-descripcion">{{ $producto->descripcion }}</p>
                    @endif
                    <div class="pe-personalizable">
                        <h2 class="pe-personalizable-titulo">Se adapta a lo que necesites</h2>
                        <ul class="pe-personalizable-lista">
                            <li class="pe-personalizable-item">
                                <span class="pe-personalizable-icono"><x-heroicon-o-arrows-pointing-out /></span>
                                <span><strong>Medidas exactas</strong> — se fabrica a la medida que necesites.</span>
                            </li>
                            <li class="pe-personalizable-item">
                                <span class="pe-personalizable-icono"><x-heroicon-o-swatch /></span>
                                <span><strong>Colores y terminaciones</strong> — según el material y el estilo que elijas.</span>
                            </li>
                            <li class="pe-personalizable-item">
                                <span class="pe-personalizable-icono"><x-heroicon-o-adjustments-horizontal /></span>
                                <span><strong>Modificaciones a pedido</strong> — si necesitás algo distinto, lo adaptamos para vos.</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Mismo botón que "Agregar al carrito" en la ficha
                         estándar (.btn-ps-primary de producto-show.css, ya
                         cargado en esta página): mismo verde de marca, mismo
                         tamaño y mismo hover, en vez de que el CTA de acá
                         tenga su propio verde de WhatsApp. --}}
                    <div class="pe-acciones">
                        <a href="{{ whatsapp_href($mensajeWa) }}"
                            class="btn-ps-primary"
                            @if(whatsapp_numero()) target="_blank" rel="noopener" @endif>
                            <i class="bi bi-whatsapp"></i>
                            Consultar por WhatsApp
                        </a>
                        <p class="pe-acciones-nota">
                            Te respondemos con opciones, tiempos y una propuesta.
                        </p>
                        <a href="{{ route('productos.categoria', $producto->categoria_id) }}" class="g-link pe-link-categoria">
                            Ver {{ Str::lower($producto->categoria->nombre) }} de catálogo
                            <x-heroicon-o-arrow-right />
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- Lightbox de galería --}}
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
        <div class="ps-lightbox-controles">
            <button type="button" class="ps-lightbox-nav ps-lightbox-nav--prev"
                id="ps-lightbox-prev" aria-label="Imagen anterior">
                <x-heroicon-o-chevron-left />
            </button>

            <div class="ps-lightbox-thumbs">
                @foreach($imagenes as $imagen)
                <button type="button" class="ps-lightbox-thumb {{ $loop->first ? 'active' : '' }}" data-index="{{ $loop->index }}" data-src="{{ $imagen->ruta }}">
                    <img src="{{ $imagen->ruta_miniatura }}" alt="" width="58" height="58" loading="lazy" decoding="async">
                </button>
                @endforeach
            </div>

            <button type="button" class="ps-lightbox-nav ps-lightbox-nav--next"
                id="ps-lightbox-next" aria-label="Imagen siguiente">
                <x-heroicon-o-chevron-right />
            </button>
        </div>
        @endif
    </div>
</dialog>
@endif

@if($producto->descripcion_tecnica || $tecnicas->isNotEmpty())
<section class="ps-tecnica-section">
    <div class="container">
        <div class="ps-tecnica-layout">

            <div class="ps-tecnica-texto-col">
                <p class="ps-seccion-eyebrow">Ficha técnica</p>
                <h2 class="ps-seccion-titulo">Descripción técnica</h2>

                @if($producto->descripcion_tecnica)
                <div id="ps-tecnica-texto" class="ps-tecnica-texto-wrap is-clamped" data-tecnica-texto-wrap>
                    <p class="ps-tecnica-texto">{{ $producto->descripcion_tecnica }}</p>
                </div>
                <button type="button" class="ps-tecnica-vermas"
                    data-tecnica-toggle aria-expanded="false" aria-controls="ps-tecnica-texto">
                    <span data-tecnica-toggle-label>Ver más</span>
                    <x-heroicon-o-chevron-down />
                </button>
                @endif
            </div>

            @if($tecnicas->isNotEmpty())
            <div class="ps-tecnica-galeria{{ $tecnicas->count() === 1 ? ' ps-tecnica-galeria--unica' : '' }}"
                data-tecnica-galeria>
                @foreach($tecnicas as $tecnica)
                <button type="button"
                    class="ps-tecnica-figura"
                    data-tecnica-index="{{ $loop->index }}"
                    data-src="{{ $tecnica->ruta }}"
                    aria-label="Ampliar imagen técnica {{ $loop->iteration }} de {{ $tecnicas->count() }}">
                    <img src="{{ $tecnica->ruta }}"
                        alt="Detalle técnico de {{ $producto->nombre }}"
                        class="ps-tecnica-img"
                        width="600" height="450"
                        loading="lazy" decoding="async">
                    <span class="ps-tecnica-zoom" aria-hidden="true">
                        <x-heroicon-o-arrows-pointing-out />
                    </span>
                </button>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>

@if($tecnicas->isNotEmpty())
<dialog id="ps-tecnica-lightbox" class="ps-lightbox">
    <div class="ps-lightbox-inner">
        <button class="ps-lightbox-close" id="ps-tecnica-lightbox-close" aria-label="Cerrar">
            <x-heroicon-o-x-mark />
        </button>
        <div class="ps-lightbox-img-wrap">
            <img id="ps-tecnica-lightbox-img" src="" alt="">
        </div>
        @if($tecnicas->count() > 1)
        <div class="ps-lightbox-controles">
            <button type="button" class="ps-lightbox-nav ps-lightbox-nav--prev"
                id="ps-tecnica-lightbox-prev" aria-label="Imagen técnica anterior">
                <x-heroicon-o-chevron-left />
            </button>

            <div class="ps-lightbox-thumbs">
                @foreach($tecnicas as $tecnica)
                <button type="button" class="ps-lightbox-thumb ps-tecnica-lightbox-thumb {{ $loop->first ? 'active' : '' }}"
                    data-index="{{ $loop->index }}" data-src="{{ $tecnica->ruta }}">
                    <img src="{{ $tecnica->ruta_miniatura }}" alt="" width="58" height="58" loading="lazy" decoding="async">
                </button>
                @endforeach
            </div>

            <button type="button" class="ps-lightbox-nav ps-lightbox-nav--next"
                id="ps-tecnica-lightbox-next" aria-label="Imagen técnica siguiente">
                <x-heroicon-o-chevron-right />
            </button>
        </div>
        @endif
    </div>
</dialog>
@endif
@endif
@php
$pasosProceso = [
['titulo' => 'Contanos tu idea', 'texto' => 'Compartinos tu proyecto o lo que necesitás resolver. Te asesoramos sobre las mejores opciones y armamos un presupuesto a tu medida.'],
['titulo' => 'Relevamiento y medidas', 'texto' => 'Verificamos las medidas exactas en el lugar para asegurar que cada trabajo calce a la perfección en tu espacio.'],
['titulo' => 'Fabricación a medida', 'texto' => 'Producimos en nuestro taller con materiales de primera calidad, cuidando cada detalle.'],
['titulo' => 'Entrega o instalación', 'texto' => 'Coordinamos según lo que prefieras: colocación en el lugar, envío directo o retiro por nuestro taller.'],
];
@endphp

<section class="pe-proceso">
    <div class="container">
        <p class="ps-seccion-eyebrow">Así trabajamos</p>
        <h2 class="ps-seccion-titulo pe-proceso-titulo">Tu espacio renovado en 4 pasos</h2>

        {{-- Mobile: acordeón. Mismo mecanismo genérico que el menú del
             drawer (layout.js ya inicializa cualquier [data-acordeon] de la
             página), así que acá no hace falta cargar ni escribir JS nuevo. --}}
        <div class="pe-proceso-lista d-lg-none">
            @foreach($pasosProceso as $i => $paso)
            <div class="pe-proceso-item" data-acordeon>
                <button type="button" class="pe-proceso-item-toggle" data-acordeon-toggle
                    aria-expanded="false" aria-controls="pe-proceso-panel-{{ $i }}">
                    <span class="pe-proceso-item-numero">{{ $i + 1 }}</span>
                    <span class="pe-proceso-item-titulo">{{ $paso['titulo'] }}</span>
                    <i class="bi bi-chevron-down pe-proceso-item-chevron"></i>
                </button>
                <div class="pe-proceso-item-panel" id="pe-proceso-panel-{{ $i }}" data-acordeon-panel>
                    <p class="pe-proceso-item-texto">{{ $paso['texto'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop: los 4 entran cómodos en una fila, sin necesidad de
             carrusel ni scroll. --}}
        <ol class="pe-proceso-pasos d-none d-lg-flex">
            @foreach($pasosProceso as $i => $paso)
            <li class="pe-proceso-paso">
                <span class="pe-proceso-numero">{{ $i + 1 }}</span>
                <h3 class="pe-proceso-paso-titulo">{{ $paso['titulo'] }}</h3>
                <p class="pe-proceso-paso-texto">{{ $paso['texto'] }}</p>
            </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- Relacionados --}}
@if($relacionados->count() > 0)
<section class="ps-relacionados-section">
    <div class="container">

        <div class="ps-relacionados-header">
            <div>
                <p class="ps-seccion-eyebrow">Seguí explorando</p>
                <h2 class="ps-seccion-titulo">
                    {{ $relacionadosSonEspeciales ? 'Otros productos de la línea adapta' : 'Más en ' . $producto->categoria->nombre }}
                </h2>
            </div>
            <a href="{{ $relacionadosSonEspeciales ? route('productos.especiales') : route('productos.categoria', $producto->categoria_id) }}"
                class="ps-relacionados-link">
                {{ $relacionadosSonEspeciales ? 'Ver todos' : 'Ver categoría' }}
                <x-heroicon-o-arrow-right />
            </a>
        </div>

        <div class="row g-3">
            @foreach($relacionados as $relacionado)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ $relacionadosSonEspeciales ? route('productos.especial.show', $relacionado->id) : route('productos.show', $relacionado->id) }}"
                    class="ps-mini-card">
                    <div class="ps-mini-imagen">
                        @php $imagenPrincipal = $relacionado->imagenes->first(); @endphp
                        @if($imagenPrincipal && $imagenPrincipal->ruta)
                        <img src="{{ $imagenPrincipal->ruta_miniatura }}" alt="{{ $relacionado->nombre }}" width="60" height="60" loading="lazy" decoding="async">
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

{{-- Sin banner de cierre ni barra fija de mobile: los dos eran el mismo
     botón de WhatsApp repetido, y ya está arriba del todo en el panel de
     consulta. --}}

@endsection

@section('script')
<script src="{{ versioned_asset('js/modules/producto-show.js') }}"></script>
@endsection