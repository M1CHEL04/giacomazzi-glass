@extends('layouts.app-externo')
@section('title', 'Nosotros - Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ versioned_asset('css/nosotros.css') }}">
@endsection

@section('content')
@php
$waNumero = preg_replace('/\D/', '', config('app.whatsapp_number', ''));
$waMensaje = '¡Hola! Quiero hacerles una consulta.';
$waHref = $waNumero ? 'https://wa.me/' . $waNumero . '?text=' . rawurlencode($waMensaje) : null;

// Sedes: alimentan tanto la lista como los marcadores del mapa
$sedes = [
[
'key' => 'fabrica',
'tipo' => 'Fábrica',
'tag' => 'Producción',
'direccion' => 'San Juan 1978 entre Av. La Plata y Madame Curie',
'localidad' => 'Quilmes Oeste, Buenos Aires',
'horarios' => 'Lun a Vie: 8:00 - 17:00',
'telefono_label' => '011 6445-7059',
'telefono_tel' => '01164457059',
'lat' => -34.7277121,
'lng' => -58.2851433,
],
[
'key' => 'local',
'tipo' => 'Local al público',
'tag' => 'Atención y showroom',
'direccion' => 'Au Dr. Ricardo Balbín Km 30 - Local 03B',
'localidad' => 'Guillermo Enrique Hudson, Buenos Aires',
'horarios' => 'Lun a Vie: 10:00 - 19:00',
'telefono_label' => '011 9268-3417',
'telefono_tel' => '01192683417',
'lat' => -34.7763988,
'lng' => -58.1634747,
],
];

// Versión reducida para el JS del mapa
$sedesMapa = array_map(fn($s) => [
'key' => $s['key'],
'tipo' => $s['tipo'],
'direccion' => $s['direccion'],
'localidad' => $s['localidad'],
'telLabel' => $s['telefono_label'],
'telTel' => $s['telefono_tel'],
'lat' => $s['lat'],
'lng' => $s['lng'],
], $sedes);

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
    <img src="{{ asset('images/heros/hero_nosotros.png') }}" alt="" class="about-hero-bg" aria-hidden="true">
    <span class="about-hero-scrim"></span>
    <div class="container about-hero-inner">
        <p class="about-hero-eyebrow">Aberturas Giacomazzi</p>
        <h1 class="about-hero-title">Nosotros</h1>
    </div>
</section>

{{-- ── SOBRE NOSOTROS ───────────────────────────────────────────────── --}}
<section class="about-intro">
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
                Además, somos representantes oficiales de puertas Oblaka y
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
<section class="about-obras">
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
                            <img src="{{ asset($obra['imagen']) }}" alt="{{ $obra['nombre'] }}"
                                class="about-obra-img" loading="lazy">
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
<section class="about-sedes" id="donde-estamos">
    <div class="container">
        <div class="about-head">
            <p class="g-eyebrow">Sedes</p>
            <h2 class="g-title">Dónde encontrarnos</h2>

        </div>
        <div class="about-sede-grid">
            @foreach($sedes as $i => $sede)
            <div class="about-sede {{ $i === 0 ? 'is-active' : '' }}" data-sede="{{ $sede['key'] }}">
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

                <div class="about-sede-actions">
                    {{-- La tarjeta entera responde al clic, pero el control
                         real es este botón: así el teclado también llega. --}}
                    <button type="button" class="about-sede-action"
                        data-sede-focus="{{ $sede['key'] }}"
                        aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">
                        <x-heroicon-o-map-pin />
                        Ver en el mapa
                    </button>
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $sede['lat'] }},{{ $sede['lng'] }}"
                        class="about-sede-action" target="_blank" rel="noopener">
                        <x-heroicon-o-arrow-right />
                        Cómo llegar
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div class="about-map" id="map" data-sedes="{{ json_encode($sedesMapa) }}"></div>

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
<section class="about-contacto">
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

        const sedes = JSON.parse(mapEl.dataset.sedes);

        // Centro aproximado entre las dos sedes
        const initialZoom = window.matchMedia('(max-width: 767.98px)').matches ? 11 : 12;
        const map = L.map(mapEl, {
            // La rueda no hace zoom para no entorpecer el scroll de la página.
            // Queda disponible por botones (+/-) y gesto de dos dedos.
            scrollWheelZoom: false,
            touchZoom: true,
        }).setView([-34.752, -58.224], initialZoom);

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

        const markers = {};
        sedes.forEach(function(s) {
            const marker = L.marker([s.lat, s.lng], {
                icon: greenIcon
            }).addTo(map);
            marker.bindPopup(
                '<div style="font-family: Asap, sans-serif; line-height: 1.45;">' +
                '<strong style="color: #287452; font-size: 1rem;">' + s.tipo + '</strong><br>' +
                s.direccion + '<br>' +
                s.localidad + '<br>' +
                '<a href="tel:' + s.telTel + '" style="color: #287452; text-decoration: none; font-weight: 600;">' + s.telLabel + '</a>' +
                '</div>', {
                    autoPan: false
                }
            );
            marker.on('click', function() {
                setActive(s.key, false);
            });
            markers[s.key] = marker;
        });

        const cards = document.querySelectorAll('.about-sede[data-sede]');
        const focusBtns = document.querySelectorAll('[data-sede-focus]');

        function setActive(key, moveMap) {
            if (moveMap === undefined) moveMap = true;

            cards.forEach(function(card) {
                card.classList.toggle('is-active', card.dataset.sede === key);
            });
            focusBtns.forEach(function(btn) {
                btn.setAttribute('aria-pressed', btn.dataset.sedeFocus === key ? 'true' : 'false');
            });

            const marker = markers[key];
            if (!marker) return;

            if (moveMap) {
                const zoom = 15;
                // Se sube el centro para que el popup no quede pegado al borde
                const punto = map.project(marker.getLatLng(), zoom).subtract([0, 60]);
                map.setView(map.unproject(punto, zoom), zoom, {
                    animate: true
                });
            }
            marker.openPopup();
        }

        cards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                // Los links y el botón propio actúan por su cuenta
                if (e.target.closest('a, button')) return;
                setActive(card.dataset.sede);
            });
        });

        focusBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                setActive(btn.dataset.sedeFocus);
                // El mapa quedó abajo de las tarjetas: sin esto, en teléfono
                // el botón parecía no hacer nada porque el mapa no se veía.
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                mapEl.scrollIntoView({
                    behavior: reduce ? 'auto' : 'smooth',
                    block: 'center'
                });
            });
        });

        // Reajuste por si el contenedor cambia de tamaño (mobile/desktop)
        window.addEventListener('load', function() {
            map.invalidateSize();
        });
    })();
</script>
@endsection