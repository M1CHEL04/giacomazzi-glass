@extends('layouts.app-externo')
@section('title', 'Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/home.css') }}">
{{-- El hero es el LCP: lo pedimos antes de que el parser llegue al <img>. --}}
<link rel="preload" as="image"
    href="{{ imagen_src('images/hero_inicio.jpg', 1400) }}"
    imagesrcset="{{ imagen_srcset('images/hero_inicio.jpg') }}"
    imagesizes="100vw" fetchpriority="high">
<script>
    if (!matchMedia('(prefers-reduced-motion: reduce)').matches) document.documentElement.classList.add('reveal-on');
</script>
@endsection

@section('content')
@php
$waNumero = whatsapp_numero();
$waHref = whatsapp_href('¡Hola! tengo un proyecto y me gustaria trabajar con ustedes.');


$obras = ['Viviendas', 'Edificios', 'Locales comerciales', 'Hoteles'];

$nosotrosImagen = 'images/img-catalogo/logo-marca.png';


// El índice de adapta ya no se corta en seis: la caja tiene alto fijo y
// scrollea, así que van todas y la franja mide lo mismo con 4 que con 40.


$lineas = [
[
'num' => '01',
'nombre' => 'Línea estándar',
'resumen' => 'Precio al instante en la web',
'texto' => 'Modelos de medidas y tamaños fijos, con entrega casi inmediata. Los elegís del catálogo y obtenés el precio al instante.',
'accion' => 'Ver la línea estándar',
'href' => route('productos.todos', ['tipos' => ['estandar']]),
],
[
'num' => '02',
'nombre' => 'Línea adapta',
'resumen' => 'Personalizada, por encargo',
'texto' => 'Productos que no se fabrican en serie ni tienen medidas fijas. Los producimos por encargo y podemos ajustar dimensiones o detalles según lo que requiera tu espacio.',
'accion' => 'Ver la línea adapta',
'href' => route('productos.especiales'),
],
[
'num' => '03',
'nombre' => 'Proyectos a medida',
'resumen' => 'Obra integral, desde el plano',
'texto' => 'Cuando la obra es integral, la encaramos desde cero: casas, edificios, locales comerciales y hoteles, con cada abertura pensada junto al plano.',
'accion' => 'Ver qué obras realizadas',
'href' => '#proyectos',
],
];
@endphp

{{-- ── HERO ─────────────────────────────────────────────────────────── --}}
<section class="home-hero">
    <img src="{{ imagen_src('images/hero_inicio.jpg', 1400) }}"
        srcset="{{ imagen_srcset('images/hero_inicio.jpg') }}"
        sizes="100vw"
        alt="" class="home-hero-bg" aria-hidden="true"
        width="2400" height="1596"
        fetchpriority="high" decoding="async">
    <span class="home-hero-scrim"></span>
    <div class="container home-hero-inner">
        <h1 class="home-hero-title">Soluciones para cada espacio</h1>
        <!-- <p class="home-cota home-hero-cota"><span>Aluminio y PVC</span></p> -->
        <p class="home-hero-text">
            Aberturas, cerramientos, espejos, vidrios y sistemas para proyectos residenciales y comerciales.
        </p>
    </div>
    <div class="home-hero-cue-wrap">
        <div class="container">
            <a href="#nosotros" class="home-hero-cue">
                <span class="home-hero-cue-label">Descubrí más</span>
                <span class="home-hero-cue-track" aria-hidden="true">
                    <span class="home-hero-cue-beam"></span>
                </span>
            </a>
        </div>
    </div>
</section>


<section class="home-nosotros g-plano g-plano--light" id="nosotros">
    <div class="container home-nosotros-inner">
        <div class="home-nosotros-copy" data-reveal>
            <p class="home-eyebrow home-eyebrow--light">Sobre nosotros</p>
            <p class="home-nosotros-lead">
                En Aberturas Giacomazzi nos dedicamos a la fabricación y provisión de
                aberturas de PVC y aluminio, ofreciendo soluciones funcionales,
                duraderas y de calidad para todo tipo de proyectos.
            </p>
            <a href="{{ route('nosotros') }}" class="home-link home-link--light">
                Leer más <x-heroicon-o-arrow-right />
            </a>
        </div>

        <figure class="home-nosotros-figure" data-reveal data-reveal-delay="1">
            <div class="home-nosotros-plate g-plano">
                <img src="{{ imagen_src($nosotrosImagen, 800) }}"
                    srcset="{{ imagen_srcset($nosotrosImagen) }}"
                    sizes="(min-width: 1200px) 420px, 34vw"
                    alt="Aberturas Giacomazzi"
                    class="home-nosotros-logo"
                    loading="lazy" decoding="async">
            </div>
        </figure>
    </div>
</section>

<section class="home-lineas g-plano">
    <div class="container home-lineas-inner">
        <div class="home-lineas-head" data-reveal>
            <p class="home-eyebrow">Cómo trabajamos</p>
            <h2 class="home-title home-lineas-title">Una solución para cada necesidad</h2>
            <p class="home-lineas-intro">
                TTres formas de resolver lo que necesitás, con la misma calidad de fabricación en cada una.
            </p>
        </div>

        <ul class="home-lineas-acordeon" data-reveal data-reveal-delay="1">
            @foreach($lineas as $i => $linea)
            <li class="home-lin{{ $i === 0 ? ' open' : '' }}" data-acordeon>
                <button class="home-lin-head" type="button"
                    data-acordeon-toggle
                    aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                    aria-controls="linea-panel-{{ $i }}">
                    <span class="home-lin-num">{{ $linea['num'] }}</span>
                    <span class="home-lin-titles">
                        <span class="home-lin-name">{{ $linea['nombre'] }}</span>
                        <span class="home-lin-resumen">{{ $linea['resumen'] }}</span>
                    </span>
                    <span class="home-lin-chevron" aria-hidden="true">
                        <x-heroicon-o-chevron-down />
                    </span>
                </button>

                <div class="home-lin-panel" id="linea-panel-{{ $i }}" data-acordeon-panel>
                    <div class="home-lin-panel-in">
                        <p class="home-lin-text">{{ $linea['texto'] }}</p>
                        <a href="{{ $linea['href'] }}" class="home-lin-go">
                            {{ $linea['accion'] }}
                            <x-heroicon-o-arrow-right />
                        </a>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>

        <ol class="home-lineas-list">
            @foreach($lineas as $i => $linea)
            <li data-reveal data-reveal-delay="{{ $i + 1 }}">
                <a href="{{ $linea['href'] }}" class="home-linea">
                    <span class="home-linea-num">{{ $linea['num'] }}</span>
                    <h3 class="home-linea-name">{{ $linea['nombre'] }}</h3>
                    <p class="home-linea-text">{{ $linea['texto'] }}</p>
                    <span class="home-linea-go">
                        {{ $linea['accion'] }}
                        <x-heroicon-o-arrow-right />
                    </span>
                </a>
            </li>
            @endforeach
        </ol>
    </div>
</section>

<section class="home-catalogo" id="catalogo">
    <div class="container">
        <div class="home-catalogo-head" data-reveal>
            <div>
                <p class="home-eyebrow">Línea estándar</p>
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
                            <img src="{{ imagen_src($categoria->imagen_hero, 800) }}"
                                srcset="{{ imagen_srcset($categoria->imagen_hero) }}"
                                sizes="(min-width: 768px) 380px, (min-width: 576px) 46vw, 72vw"
                                alt="" class="home-cat-img"
                                width="800" height="600"
                                loading="lazy" decoding="async">
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

@if($categoriasAdapta->isNotEmpty())
<section class="home-adapta g-plano g-plano--light">
    <div class="container home-adapta-inner">
        <div class="home-adapta-head" data-reveal>
            <div>
                <p class="home-eyebrow home-eyebrow--light">
                    Línea adapta
                </p>
                <h2 class="home-title home-title--light">
                    Fabricación a pedido, adaptada a tu espacio
                </h2>
            </div>
            <p class="home-adapta-text">
                Productos que no se fabrican en serie. Ajustamos medidas y detalles a pedido para que calce justo donde lo necesitás.
            </p>
        </div>

        <div class="home-indice-wrap" data-reveal data-reveal-delay="1">
            <ol class="home-indice" data-scroller tabindex="0" role="group"
                aria-label="Categorías de la línea adapta">
                @foreach($categoriasAdapta as $i => $categoria)
                <li>
                    <a href="{{ route('productos.especial.categoria', $categoria->id) }}"
                        class="home-indice-row">
                        <span class="home-indice-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="home-indice-name">{{ $categoria->nombre }}</span>
                        <span class="home-indice-go" aria-hidden="true">
                            <x-heroicon-o-arrow-right />
                        </span>
                    </a>
                </li>
                @endforeach
            </ol>

            <p class="home-indice-hint" aria-hidden="true">
                Deslizá para ver todas
                <x-heroicon-o-chevron-down />
            </p>
        </div>

        <div class="home-adapta-foot" data-reveal>
            <a href="{{ route('productos.especiales') }}" class="home-btn home-btn--solid">
                Ver toda la línea adapta
                <x-heroicon-o-arrow-right />
            </a>
        </div>
    </div>
</section>
@endif


<section class="home-medida" id="proyectos">
    <img src="{{ imagen_src('images/producto_medida.JPG', 1400) }}"
        srcset="{{ imagen_srcset('images/producto_medida.JPG') }}"
        sizes="100vw"
        alt="" class="home-medida-bg" aria-hidden="true"
        width="2400" height="1800"
        loading="lazy" decoding="async">
    <span class="home-medida-scrim"></span>

    <div class="container home-medida-inner">
        <div class="home-medida-copy" data-reveal>
            <p class="home-eyebrow home-eyebrow--light">Proyectos a medida</p>
            <h2 class="home-title home-title--light home-medida-title">
                Soluciones integrales para tu proyecto
            </h2>
            <p class="home-medida-text">
                Trabajamos a la par de arquitectos y constructoras desde el anteproyecto. Fabricamos e instalamos cerramientos, cristalería y estructuras que exige la obra.
            </p>

            <div class="home-medida-actions">
                <a href="{{ $waHref }}" class="g-btn g-btn--wa"
                    @if($waNumero) target="_blank" rel="noopener" @endif>
                    <i class="bi bi-whatsapp"></i> Contanos tu proyecto
                </a>
            </div>
        </div>

        <div class="home-medida-list-wrap" data-reveal data-reveal-delay="1">
            <ol class="home-medida-list">
                @foreach($obras as $i => $obra)
                <li class="home-medida-list-item">
                    <span class="home-medida-list-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="home-medida-list-name">{{ $obra }}</span>
                </li>
                @endforeach
            </ol>
            <p class="home-medida-nota">Y cualquier otra obra que lo requiera.</p>
        </div>
    </div>
</section>
@endsection

@section('script')
<script src="{{ versioned_asset('js/modules/rail.js') }}"></script>
<script src="{{ versioned_asset('js/modules/reveal.js') }}"></script>
<script src="{{ versioned_asset('js/modules/scroller.js') }}"></script>
@endsection