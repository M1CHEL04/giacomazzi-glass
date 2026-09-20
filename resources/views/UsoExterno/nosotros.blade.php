@extends('layouts.app-externo')
@section('title', 'Nosotros - Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ versioned_asset('css/nosotros.css') }}">
{{-- El hero es el LCP: lo pedimos antes de que el parser llegue al <img>. --}}
<link rel="preload" as="image"
    href="{{ imagen_src('images/heros/hero_nosotros.png', 1400) }}"
    imagesrcset="{{ imagen_srcset('images/heros/hero_nosotros.png') }}"
    imagesizes="100vw" fetchpriority="high">
@endsection

@section('content')
@php
$waNumero = preg_replace('/\D/', '', config('app.whatsapp_number', ''));
$waMensaje = '¡Hola! Quiero hacerles una consulta.';
$waHref = $waNumero ? 'https://wa.me/' . $waNumero . '?text=' . rawurlencode($waMensaje) : null;

// Sede: alimenta tanto el panel como el marcador del mapa
$sede = [
'tipo' => 'Fábrica',
'tag' => 'Producción',
'direccion' => 'San Juan 1978 entre Av. La Plata y Madame Curie',
'localidad' => 'Quilmes Oeste, Buenos Aires',
'horarios' => 'Lun a Vie: 8:00 - 17:00',
'telefono_label' => '011 6445-7059',
'telefono_tel' => '01164457059',
'lat' => -34.7277121,
'lng' => -58.2851433,
];

// Obras: cargá 'imagen' con la ruta (ej. 'images/obras/edificio.jpg') y
// la obra aparece sola. Sin fotos cargadas la sección no se muestra:
// seis recuadros vacíos comunican menos que no tener la sección.
$obras = [
['nombre' => 'Edificio residencial', 'imagen' => null],
['nombre' => 'Casa particular', 'imagen' => null],
['nombre' => 'Local comercial', 'imagen' => null],
['nombre' => 'Cerramiento de balcón', 'imagen' => null],
['nombre' => 'Fachada vidriada', 'imagen' => null],
['nombre' => 'Obra a medida', 'imagen' => null],
];
$obras = array_values(array_filter($obras, fn($o) => !empty($o['imagen'])));
@endphp

<section class="about-hero">
    <img src="{{ imagen_src('images/heros/hero_nosotros.png', 1400) }}"
        srcset="{{ imagen_srcset('images/heros/hero_nosotros.png') }}"
        sizes="100vw"
        alt="" class="about-hero-bg" aria-hidden="true"
        width="2400" height="745"
        fetchpriority="high" decoding="async">
    <span class="about-hero-scrim"></span>
    <div class="container about-hero-inner">
        <p class="about-hero-eyebrow">Aberturas Giacomazzi</p>
        <h1 class="about-hero-title">Nosotros</h1>
    </div>
</section>

{{-- ── SOBRE NOSOTROS ───────────────────────────────────────────────── --}}
<section class="about-intro g-plano">
    <div class="container">
        <div class="about-head">
            <p class="g-eyebrow">Sobre nosotros</p>
            <h2 class="g-title">Quiénes somos</h2>

        </div>

        <div class="about-intro-body">
            <p class="about-intro-text">
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
                Además, somos representantes oficiales de puertas Oblak y
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
</section>

{{-- ── OBRAS ────────────────────────────────────────────────────────── --}}
@if(count($obras))
<section class="about-obras g-plano">
    <div class="container">
        <div class="about-head">
            <div>
                <p class="g-eyebrow">Obras realizadas</p>
                <h2 class="g-title">Dónde confiaron en nosotros</h2>
            </div>
        </div>

        <div class="about-rail-wrap" data-rail>
            <div class="about-rail" data-rail-track tabindex="0" role="group"
                aria-label="Obras realizadas">
                @foreach($obras as $obra)
                <div class="about-rail-item">
                    <figure class="about-obra">
                        <span class="about-obra-thumb">
                            <img src="{{ imagen_src($obra['imagen'], 800) }}"
                                srcset="{{ imagen_srcset($obra['imagen']) }}"
                                sizes="(min-width: 768px) 380px, 72vw"
                                alt="{{ $obra['nombre'] }}"
                                class="about-obra-img"
                                width="800" height="600"
                                loading="lazy" decoding="async">
                        </span>
                        <figcaption class="about-obra-name">{{ $obra['nombre'] }}</figcaption>
                    </figure>
                </div>
                @endforeach
            </div>

            <div class="about-rail-controls">
                <button class="about-rail-arrow" type="button"
                    data-rail-prev aria-label="Obras anteriores">
                    <x-heroicon-o-chevron-left />
                </button>
                <div class="about-rail-dots" data-rail-dots></div>
                <button class="about-rail-arrow" type="button"
                    data-rail-next aria-label="Obras siguientes">
                    <x-heroicon-o-chevron-right />
                </button>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ── DÓNDE ESTAMOS ────────────────────────────────────────────────── --}}
{{-- Ancla #donde-estamos: es adonde llega quien hace clic en "Contacto". --}}
<section class="about-sedes g-plano" id="donde-estamos">
    <div class="container">
        <div class="about-head">
            <p class="g-eyebrow">Ubicación</p>
            <h2 class="g-title">Dónde encontrarnos</h2>

        </div>
        {{-- Un único panel: info a la izquierda, mapa a la derecha. Con
             una sola sede, separar tarjeta y mapa en dos bloques dejaba
             un plano gigante sin nada que justificara su tamaño. --}}
        <div class="about-sede-panel">
            <div class="about-sede-info">
                <span class="about-sede-tag">{{ $sede['tag'] }}</span>
                <h3 class="about-sede-title">{{ $sede['tipo'] }}</h3>

                <div class="about-sede-meta">
                    <p class="about-sede-row">
                        <x-heroicon-o-map-pin />
                        <span>{{ $sede['direccion'] }}<br>{{ $sede['localidad'] }}</span>
                    </p>
                    <p class="about-sede-row">
                        <x-heroicon-o-clock />
                        <span>{{ $sede['horarios'] }}</span>
                    </p>
                    <p class="about-sede-row">
                        <x-heroicon-o-phone />
                        <a href="tel:{{ $sede['telefono_tel'] }}">{{ $sede['telefono_label'] }}</a>
                    </p>
                </div>

                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $sede['lat'] }},{{ $sede['lng'] }}"
                    class="g-btn g-btn--primary about-sede-action" target="_blank" rel="noopener">
                    <x-heroicon-o-arrow-right />
                    Cómo llegar
                </a>
            </div>

            <div class="about-map" id="map"
                data-lat="{{ $sede['lat'] }}" data-lng="{{ $sede['lng'] }}"
                data-tipo="{{ $sede['tipo'] }}"
                data-direccion="{{ $sede['direccion'] }}"
                data-localidad="{{ $sede['localidad'] }}"></div>
        </div>

        {{-- Instagram cierra la sección: es la única dirección que no
             está en el mapa. La cota va abajo del dibujo, como en el
             plano, y acota lo que el mapa no alcanza a mostrar. --}}
        <div class="about-online">
            <p class="g-cota about-online-cota"><span>También en línea</span></p>
            <a href="https://www.instagram.com/giacomazzi_srl/"
                class="about-online-link" target="_blank" rel="noopener">
                <i class="bi bi-instagram" aria-hidden="true"></i>
                <span>Seguinos en <strong>@@giacomazzi_srl</strong></span>
            </a>
        </div>
    </div>
</section>

{{-- ── ESCRIBINOS ───────────────────────────────────────────────────── --}}
<section class="about-contacto g-banda-verde g-plano g-plano--light">
    <div class="container about-contacto-inner">
        <p class="g-eyebrow g-eyebrow--light">Escribinos</p>
        <h2 class="g-title g-title--light about-contacto-title">
            ¿Necesitás asesoramiento antes de cotizar?
        </h2>
        <p class="about-contacto-text">
            Contanos tu proyecto y te acompañamos en todo el proceso.
        </p>
        <div class="about-contacto-actions">
            @if($waHref)
            <a href="{{ $waHref }}" class="g-btn g-btn--solid" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Escribir por WhatsApp
            </a>
            @endif
        </div>
    </div>
</section>
@endsection

@section('script')
@if(count($obras))
<script src="{{ versioned_asset('js/modules/rail.js') }}"></script>
@endif
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    (function() {
        const mapEl = document.getElementById('map');
        if (!mapEl) return;

        const lat = parseFloat(mapEl.dataset.lat);
        const lng = parseFloat(mapEl.dataset.lng);

        const map = L.map(mapEl, {
            // La rueda no hace zoom para no entorpecer el scroll de la página.
            // Queda disponible por botones (+/-) y gesto de dos dedos.
            scrollWheelZoom: false,
            touchZoom: true,
        }).setView([lat, lng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
            minZoom: 10
        }).addTo(map);

        const greenIcon = L.icon({
            iconUrl: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="25" height="41" viewBox="0 0 25 41"%3E%3Cpath fill="%23287452" d="M12.5 0C5.596 0 0 5.596 0 12.5c0 1.996.47 3.882 1.299 5.555L12.5 41l11.201-22.945C24.53 16.382 25 14.496 25 12.5 25 5.596 19.404 0 12.5 0z"/%3E%3Ccircle fill="%23ffffff" cx="12.5" cy="12.5" r="5"/%3E%3C/svg%3E',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        });

        L.marker([lat, lng], {
            icon: greenIcon
        }).addTo(map).bindPopup(
            '<div style="font-family: Asap, sans-serif; line-height: 1.45;">' +
            '<strong style="color: #287452; font-size: 1rem;">' + mapEl.dataset.tipo + '</strong><br>' +
            mapEl.dataset.direccion + '<br>' +
            mapEl.dataset.localidad +
            '</div>'
        );

        // Reajuste por si el contenedor cambia de tamaño (mobile/desktop)
        window.addEventListener('load', function() {
            map.invalidateSize();
        });
    })();
</script>
@endsection