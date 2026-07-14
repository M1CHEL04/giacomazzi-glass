@extends('layouts.app-externo')
@section('title', 'Nosotros - Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/nosotros.css') }}">
@endsection

@section('content')
@php
    $waNumero = preg_replace('/\D/', '', config('app.whatsapp_number', ''));
    $waMedida = '¡Hola! Quería cotizar un proyecto a medida.';
    $waHref   = $waNumero
        ? 'https://wa.me/' . $waNumero . '?text=' . rawurlencode($waMedida)
        : route('contacto');
@endphp

{{-- ── Header de página ─────────────────────────────────────────────── --}}
<section class="about-header">
    <img src="{{ asset('images/homehero.jpg') }}" alt="Aberturas Giacomazzi" class="about-header-bg">
    <div class="about-header-scrim"></div>
    <div class="container about-header-content">
        <h1 class="about-header-title">Nosotros</h1>
        <p class="about-header-sub">Quiénes somos y cómo trabajamos</p>
    </div>
</section>

{{-- ── Sobre nosotros ───────────────────────────────────────────────── --}}
<section class="about-intro">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <span class="about-eyebrow">Sobre nosotros</span>
            </div>
            <div class="col-lg-8">
                <p class="about-intro-lead">
                    En Aberturas Giacomazzi nos dedicamos a la fabricación y provisión de aberturas de
                    PVC y aluminio, ofreciendo soluciones funcionales, duraderas y de calidad para todo
                    tipo de proyectos.
                </p>
                <p class="about-intro-text">
                    Trabajamos en la fabricación de puertas, ventanas, cerramientos, espejos, mamparas,
                    barandas, y más, adaptándonos a las necesidades de cada obra con opciones
                    personalizadas y terminaciones cuidadas.
                </p>
                <p class="about-intro-text">
                    Además, somos representantes oficiales de puertas Oblak, puertas Gromant y
                    equipamiento para cocinas TST, lo que nos permite ampliar nuestra oferta con
                    productos reconocidos por su calidad y diseño.
                </p>
                <p class="about-intro-text">
                    También realizamos trabajos de herrería, brindando soluciones integrales para obras
                    particulares, comerciales y desarrollos a medida.
                </p>
                <p class="about-intro-text">
                    Nuestro compromiso es acompañar cada proyecto con asesoramiento personalizado,
                    materiales de calidad y la experiencia necesaria para garantizar resultados
                    confiables y duraderos.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ── Proyectos realizados ─────────────────────────────────────────── --}}
<section class="about-projects">
    <div class="container">
        @php
            // Las imágenes se definen más adelante: completar 'imagen' con la ruta
            // (ej. 'images/proyectos/edificio.jpg') y aparecerá en lugar del placeholder.
            $proyectos = [
                ['nombre' => 'Edificio residencial', 'imagen' => null],
                ['nombre' => 'Casa particular',      'imagen' => null],
                ['nombre' => 'Local comercial',      'imagen' => null],
                ['nombre' => 'Cerramiento de balcón','imagen' => null],
                ['nombre' => 'Fachada vidriada',     'imagen' => null],
                ['nombre' => 'Obra a medida',        'imagen' => null],
            ];
        @endphp

        <span class="about-eyebrow">Proyectos realizados</span>
        <h2 class="about-projects-title">Obras donde confiaron en nosotros</h2>

        <div class="about-carousel" data-carousel data-per-desktop="3" data-per-tablet="2" data-per-mobile="2">
            <div class="about-carousel-viewport" data-carousel-viewport>
                <div class="about-carousel-track" data-carousel-track>
                    @foreach($proyectos as $proyecto)
                    <div class="about-carousel-slide">
                        <div class="about-project">
                            <div class="about-project-thumb">
                                @if($proyecto['imagen'])
                                <img src="{{ asset($proyecto['imagen']) }}" alt="{{ $proyecto['nombre'] }}"
                                    class="about-project-img" loading="lazy">
                                @else
                                <x-heroicon-o-building-office-2 />
                                @endif
                            </div>
                            <div class="about-project-name">{{ $proyecto['nombre'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="about-carousel-controls">
                <button class="about-carousel-arrow" type="button"
                    data-carousel-prev aria-label="Proyectos anteriores">
                    <x-heroicon-o-chevron-left />
                </button>
                <div class="about-carousel-dots" data-carousel-dots></div>
                <button class="about-carousel-arrow" type="button"
                    data-carousel-next aria-label="Proyectos siguientes">
                    <x-heroicon-o-chevron-right />
                </button>
            </div>
        </div>
    </div>
</section>

{{-- ── CTA dual ─────────────────────────────────────────────────────── --}}
<section class="about-cta">
    <div class="container">
        <div class="row g-4 about-cta-row">
            <div class="col-6">
                <div class="about-cta-card about-cta-card--primary">
                    <h3 class="about-cta-card-title">Productos estándar</h3>
                    <p class="about-cta-card-text">
                        Explorá el catálogo de ventanas y puertas en medidas estándar, listas para cargar
                        en tu pedido dentro del sistema.
                    </p>
                    <a href="{{ route('productos.todos') }}" class="about-cta-link">
                        Ver productos <x-heroicon-o-arrow-right />
                    </a>
                </div>
            </div>
            <div class="col-6">
                <div class="about-cta-card about-cta-card--dark">
                    <h3 class="about-cta-card-title">Proyecto a medida</h3>
                    <p class="about-cta-card-text">
                        ¿Tu proyecto necesita medidas personalizadas? Contanos los detalles y te armamos
                        una cotización a medida, fuera del sistema.
                    </p>
                    <a href="{{ $waHref }}" class="about-cta-link" @if($waNumero) target="_blank" rel="noopener" @endif>
                        Cotizar por WhatsApp <x-heroicon-o-arrow-right />
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script src="{{ versioned_asset('js/modules/carousel.js') }}"></script>
@endsection
