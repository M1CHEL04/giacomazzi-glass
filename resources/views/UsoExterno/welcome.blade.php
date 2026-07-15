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
    <img src="{{ asset('images/homehero.jpg') }}" alt="Aberturas Giacomazzi" class="home-hero-bg">
    <div class="home-hero-scrim"></div>
    <div class="container home-hero-content">
        <h1 class="home-hero-title">Diseño y solidez<br>en cada abertura</h1>
        <h2 class="home-hero-subtitle">Aluminio y PVC de precisión</h2>
        <p class="home-hero-text">
            Más de 30 años fabricando aberturas de aluminio y PVC para hogares y obras,
            con la calidad y el respaldo que tu proyecto merece.
        </p>
    </div>
</section>

{{-- ── NOSOTROS (mini) ──────────────────────────────────────────────── --}}
<section class="home-about">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="home-about-text">
                    <span class="home-eyebrow">Sobre nosotros</span>
                    <p class="home-about-lead">
                        En Aberturas Giacomazzi nos dedicamos a la fabricación y provisión de aberturas
                        de PVC y aluminio, ofreciendo soluciones funcionales, duraderas y de calidad para
                        todo tipo de proyectos.
                    </p>
                    <a href="{{ route('nosotros') }}" class="home-about-link">
                        Leer más <x-heroicon-o-arrow-right />
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <img src="{{ asset('images/heros/mampara.png') }}" alt="Trabajos de Aberturas Giacomazzi"
                    class="home-about-media">
            </div>
        </div>
    </div>
</section>

{{-- ── PRODUCTOS ────────────────────────────────────────────────────── --}}
<section class="home-products" id="productos">
    <div class="container">
        <div class="home-products-head">
            <div>
                <span class="home-eyebrow">Nuestros productos</span>
                <h2 class="home-products-title">Modelos estándar, listos para tu proyecto</h2>
            </div>
            <p class="home-products-intro">
                Todos los productos disponibles en el sistema se ofrecen en medidas estándar para garantizar una producción eficiente y plazos de entrega confiables.
            </p>
        </div>

        @if($categorias->isNotEmpty())
        <div class="home-carousel" data-carousel data-per-desktop="4" data-per-tablet="3" data-per-mobile="2">
            <div class="home-carousel-viewport" data-carousel-viewport>
                <div class="home-carousel-track" data-carousel-track>
                    @foreach($categorias as $categoria)
                    <div class="home-carousel-slide">
                        <a href="{{ route('productos.categoria', $categoria->id) }}" class="home-product-card">
                            <div class="home-product-thumb">
                                @if($categoria->imagen_hero)
                                <img src="{{ asset($categoria->imagen_hero) }}" alt="{{ $categoria->nombre }}"
                                    class="home-product-img" loading="lazy">
                                @else
                                <span class="home-product-icon"><x-heroicon-o-squares-2x2 /></span>
                                @endif
                                <span class="home-product-scrim"></span>
                                <span class="home-product-label">{{ $categoria->nombre }}</span>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="home-carousel-controls">
                <button class="home-carousel-arrow" type="button"
                    data-carousel-prev aria-label="Categorías anteriores">
                    <x-heroicon-o-chevron-left />
                </button>
                <div class="home-carousel-dots" data-carousel-dots></div>
                <button class="home-carousel-arrow" type="button"
                    data-carousel-next aria-label="Categorías siguientes">
                    <x-heroicon-o-chevron-right />
                </button>
            </div>
        </div>
        @else
        <p class="text-center text-muted py-4 mb-0">No hay categorías disponibles en este momento.</p>
        @endif

        {{-- CTA medida a medida (fuera del sistema, por WhatsApp) --}}
        <div class="home-medida">
            <div class="home-medida-icon">
                <x-heroicon-o-wrench-screwdriver />
            </div>
            <div class="home-medida-body">
                <h3 class="home-medida-title">¿Necesitás una medida personalizada?</h3>
                <p class="home-medida-text">
                    Los productos del sistema son de medida estándar. Si tu proyecto requiere medidas
                    personalizadas, cotizalo directamente con nuestro equipo.
                </p>
            </div>
            <a href="{{ $waHref }}" class="home-medida-btn" @if($waNumero) target="_blank" rel="noopener" @endif>
                <x-heroicon-o-chat-bubble-left-right />
                Cotizar medida personalizada
            </a>
        </div>
    </div>
</section>
@endsection

@section('script')
<script src="{{ versioned_asset('js/modules/carousel.js') }}"></script>
@endsection