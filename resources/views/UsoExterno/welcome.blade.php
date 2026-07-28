@extends('layouts.app-externo')
@section('title', 'Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/home.css') }}">
@endsection

@section('content')
@php
$waNumero = preg_replace('/\D/', '', config('app.whatsapp_number', ''));
$waMedida = '¡Hola! tengo un proyecto y quiero cotizarlo con ustedes.';
$waHref = $waNumero
? 'https://wa.me/' . $waNumero . '?text=' . rawurlencode($waMedida)
: route('contacto');
@endphp

{{-- ── HERO ─────────────────────────────────────────────────────────── --}}
<section class="home-hero">
    <picture>
        <!-- <source media="(max-width: 480px)"
            srcset="{{ asset('images/posible_hero_mobile.jpg') }}"> -->
        <img src="{{ asset('images/hero_inicio.jpg') }}" alt=""
            class="home-hero-bg" aria-hidden="true">
    </picture>
    <span class="home-hero-scrim"></span>
    <div class="container home-hero-inner">
        <h1 class="home-hero-title">Diseño y solidez en cada abertura</h1>
        <p class="home-cota home-hero-cota"><span>Aluminio y PVC</span></p>
        <p class="home-hero-text">
            Fabricamos con la misma calidad tanto productos de catálogo como diseños a medida para tu obra o proyecto arquitectónico.
        </p>
        <div class="home-hero-actions">
            <a href="#catalogo" class="home-btn home-btn--primary">
                Ver el catálogo
            </a>
            <a href="{{ $waHref }}" class="home-btn home-btn--ghost"
                @if($waNumero) target="_blank" rel="noopener" @endif>
                <i class="bi bi-whatsapp"></i> Cotizar a medida
            </a>
        </div>
    </div>
</section>

{{-- ── CATÁLOGO ESTÁNDAR ────────────────────────────────────────────── --}}
{{-- Primer paso del recorrido: lo que ya está resuelto y se cotiza solo. --}}
<section class="home-catalogo" id="catalogo">
    <div class="container">
        <div class="home-catalogo-head">
            <div>
                <p class="home-eyebrow">Catálogo</p>
                <h2 class="home-title">Medidas estándar, listas para entrega</h2>
            </div>
            <p class="home-catalogo-intro">
                Los modelos más elegidos, siempre en stock. Sumalos a tu cotización en la web y obtené el precio al instante.
            </p>
        </div>

        @if($categorias->isNotEmpty())
        <div class="home-rail-wrap" data-rail>
            <div class="home-rail" data-rail-track tabindex="0" role="group"
                aria-label="Categorías de productos">
                @foreach($categorias as $categoria)
                <div class="home-rail-item">
                    <a href="{{ route('productos.categoria', $categoria->id) }}" class="home-cat">
                        <span class="home-cat-thumb">
                            @if($categoria->imagen_hero)
                            <img src="{{ asset($categoria->imagen_hero) }}" alt=""
                                class="home-cat-img" loading="lazy">
                            @else
                            <span class="home-cat-icon"><x-heroicon-o-squares-2x2 /></span>
                            @endif
                            <span class="home-cat-scrim"></span>
                            <span class="home-cat-label">{{ $categoria->nombre }}</span>
                        </span>
                    </a>
                </div>
                @endforeach
            </div>

            <div class="home-rail-controls">
                <button class="home-rail-arrow" type="button"
                    data-rail-prev aria-label="Categorías anteriores">
                    <x-heroicon-o-chevron-left />
                </button>
                <div class="home-rail-dots" data-rail-dots></div>
                <button class="home-rail-arrow" type="button"
                    data-rail-next aria-label="Categorías siguientes">
                    <x-heroicon-o-chevron-right />
                </button>
            </div>
        </div>

        <div class="home-catalogo-foot">
            <a href="{{ route('productos.todos') }}" class="home-btn home-btn--primary">
                Ver todos los productos y cotizar
                <x-heroicon-o-arrow-right />
            </a>
        </div>
        @else
        <p class="home-catalogo-intro">
            Estamos actualizando el catálogo. Escribinos y te pasamos los modelos disponibles.
        </p>
        @endif
    </div>
</section>

{{-- ── A MEDIDA ─────────────────────────────────────────────────────── --}}
{{-- Llega después del catálogo, cuando ya se sabe si algo estándar sirve,
     pero se lleva una franja entera: es el otro negocio de la fábrica. --}}
<section class="home-medida">
    <div class="container home-medida-inner">
        <div class="home-medida-copy">
            <p class="home-eyebrow home-eyebrow--light">Fabricación a medida</p>
            <h2 class="home-title home-title--light home-medida-title">
                Cuando el catálogo no alcanza, diseñamos la solución
            </h2>
            <p class="home-medida-text">
                Cada proyecto tiene sus propios requerimientos. Los resolvemos con fabricación a medida, sin resignar calidad ni tiempos de entrega. Contanos tu proyecto y avanzamos con tu presupuesto.
            </p>
            <div class="home-medida-actions">
                {{-- Una sola acción: los teléfonos ya están en el footer y
                     Contacto vive en el menú. Un segundo botón acá sólo
                     le sacaba fuerza a este. --}}
                <a href="{{ $waHref }}" class="home-btn home-btn--solid"
                    @if($waNumero) target="_blank" rel="noopener" @endif>
                    <i class="bi bi-whatsapp"></i> Cotizar por WhatsApp
                </a>
            </div>
        </div>

        <figure class="home-medida-figure">
            <div class="home-medida-figure-body">
                <img src="{{ asset('images/producto_medida.JPG') }}"
                    alt="Mampara fabricada a medida por Aberturas Giacomazzi"
                    class="home-medida-img" loading="lazy">
            </div>
        </figure>
    </div>
</section>

{{-- ── NOSOTROS (mini) ──────────────────────────────────────────────── --}}
{{-- Cierre de confianza: sostiene los dos caminos, no uno solo. --}}
<section class="home-nosotros">
    <div class="container home-nosotros-inner">
        <p class="home-eyebrow">Sobre nosotros</p>
        <p class="home-nosotros-lead">
            Fabricamos y proveemos aberturas de PVC y aluminio, con soluciones
            funcionales y duraderas para todo tipo de proyectos.
        </p>
        <a href="{{ route('nosotros') }}" class="home-link">
            Leer más <x-heroicon-o-arrow-right />
        </a>
    </div>
</section>
@endsection

@section('script')
<script src="{{ versioned_asset('js/modules/rail.js') }}"></script>
@endsection