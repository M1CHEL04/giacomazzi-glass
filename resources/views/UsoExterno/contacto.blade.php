@extends('layouts.app-externo')
@section('title', 'Contacto - Aberturas Giacomazzi')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .contact-hero {
        position: relative;
        min-height: 42vh;
        display: flex;
        align-items: center;
        overflow: hidden;
        background: #111;
        margin-top: calc(-1.5rem - 1px);
    }

    .contact-hero-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center 92%;
        z-index: 0;
        opacity: 0.85;
    }

    /* Dark scrim — mismo tratamiento que el hero de categorías */
    .contact-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.06) 0%, rgba(0, 0, 0, 0.38) 100%);
        z-index: 1;
    }

    /* Dot grid texture */
    .contact-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: radial-gradient(circle, rgba(255, 255, 255, 0.07) 1px, transparent 1px);
        background-size: 22px 22px;
        z-index: 2;
        pointer-events: none;
    }

    .contact-hero-content {
        position: relative;
        z-index: 3;
        padding-top: 4rem;
        padding-bottom: 4rem;
    }

    .contact-hero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--external-primary-soft);
        margin-bottom: 0.75rem;
    }

    .contact-hero-eyebrow::before {
        content: '';
        display: block;
        width: 22px;
        height: 2px;
        background: var(--external-primary-soft);
        border-radius: 2px;
    }

    .contact-hero h1 {
        font-size: 3rem;
        font-weight: 800;
        color: #ffffff;
        margin-bottom: 0.75rem;
        line-height: 1.1;
        letter-spacing: -0.025em;
    }

    .contact-hero p {
        color: rgba(255, 255, 255, 0.82);
        font-size: 1.1rem;
        margin: 0;
    }

    /* Ubicaciones (mapa + selector de sedes) */
    .loc-section {
        padding: 5rem 0;
        background: var(--external-light);
    }

    .loc-panel {
        background: var(--external-white);
        border: 1px solid rgba(0, 0, 0, 0.07);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 10px 34px rgba(0, 0, 0, 0.07);
    }

    /* Columna izquierda: lista compacta de sedes seleccionables */
    .loc-list {
        display: flex;
        flex-direction: column;
    }

    .loc-item {
        display: block;
        width: 100%;
        text-align: left;
        background: transparent;
        border: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.07);
        border-left: 3px solid transparent;
        padding: 1.4rem 1.6rem;
        cursor: pointer;
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .loc-item:last-child {
        border-bottom: none;
    }

    .loc-item:hover {
        background: rgba(40, 116, 82, 0.04);
    }

    .loc-item.is-active {
        background: rgba(40, 116, 82, 0.06);
        border-left-color: var(--external-primary);
    }

    .loc-item:focus-visible {
        outline: 2px solid var(--external-primary);
        outline-offset: -2px;
    }

    .loc-item-head {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        margin-bottom: 0.85rem;
    }

    .loc-item-icon {
        width: 38px;
        height: 38px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(40, 116, 82, 0.1);
        color: var(--external-primary);
    }

    .loc-item-icon svg {
        width: 20px;
        height: 20px;
    }

    .loc-item-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--external-secondary);
        line-height: 1.2;
    }

    .loc-item-tag {
        display: block;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--external-primary);
        margin-top: 0.1rem;
    }

    .loc-meta {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .loc-meta-row {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        font-size: 0.86rem;
        line-height: 1.45;
        color: var(--external-secondary);
    }

    .loc-meta-row svg {
        flex-shrink: 0;
        width: 1rem;
        height: 1rem;
        margin-top: 0.12rem;
        color: var(--external-primary);
    }

    .loc-meta-row a {
        color: var(--external-primary);
        font-weight: 600;
        text-decoration: none;
    }

    .loc-meta-row a:hover {
        text-decoration: underline;
    }

    .loc-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
        margin-top: 1rem;
    }

    .loc-action {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--external-primary);
        text-decoration: none;
        transition: gap 0.2s ease, color 0.2s ease;
    }

    .loc-action svg {
        width: 1rem;
        height: 1rem;
    }

    .loc-action:hover {
        color: var(--external-primary-dark);
        gap: 0.55rem;
    }

    /* Columna derecha: mapa */
    .loc-map-col {
        position: relative;
        min-height: 100%;
        border-left: 1px solid rgba(0, 0, 0, 0.07);
    }

    #map {
        height: 100%;
        min-height: 460px;
        background: #e9ecef;
    }

    /* Contacto directo (barra compacta) */
    .contact-cta {
        padding: 4rem 0;
        background: var(--external-white);
    }

    .contact-cta-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.5rem;
        background: linear-gradient(135deg, var(--external-primary) 0%, #1f5c3e 100%);
        border-radius: 1rem;
        padding: 1.75rem 2.25rem;
        color: var(--external-white);
        box-shadow: 0 12px 30px rgba(27, 45, 33, 0.16);
    }

    .contact-cta-copy {
        flex: 1 1 320px;
    }

    .contact-cta-copy h2 {
        font-size: 1.4rem;
        font-weight: 800;
        margin: 0 0 0.25rem 0;
        letter-spacing: -0.02em;
    }

    .contact-cta-copy p {
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.82);
        margin: 0;
    }

    .contact-cta-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .contact-cta-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 1.3rem;
        border-radius: 0.6rem;
        font-size: 0.95rem;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
    }

    .contact-cta-btn i {
        font-size: 1.15rem;
        line-height: 1;
    }

    .contact-cta-btn-solid {
        background: var(--external-white);
        color: var(--external-primary);
    }

    .contact-cta-btn-solid:hover {
        transform: translateY(-2px);
        color: var(--external-primary-dark);
    }

    .contact-cta-btn-ghost {
        background: rgba(255, 255, 255, 0.12);
        border: 1.5px solid rgba(255, 255, 255, 0.4);
        color: var(--external-white);
    }

    .contact-cta-btn-ghost:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
        color: var(--external-white);
    }

    /* Responsive */
    @media (max-width: 991.98px) {

        .loc-section,
        .contact-cta {
            padding: 3.5rem 0;
        }

        /* En mobile/tablet el mapa pasa arriba (ambos pines visibles) y las
           sedes quedan debajo, apiladas */
        .loc-map-col {
            order: -1;
            border-left: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.07);
        }

        #map {
            min-height: 300px;
        }
    }

    @media (max-width: 767.98px) {
        .contact-hero h1 {
            font-size: 2rem;
            letter-spacing: -0.02em;
        }

        .contact-hero p {
            font-size: 1rem;
        }

        .contact-hero-content {
            padding-top: 3rem;
            padding-bottom: 3rem;
        }

        /* Mapa más bajo para no ocupar toda la pantalla */
        #map {
            min-height: 240px;
        }

        /* Sedes compactas: mismos componentes, todo más chico */
        .loc-item {
            padding: 1.05rem 1.15rem;
        }

        .loc-item-head {
            gap: 0.6rem;
            margin-bottom: 0.6rem;
        }

        .loc-item-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
        }

        .loc-item-icon svg {
            width: 17px;
            height: 17px;
        }

        .loc-item-title {
            font-size: 0.98rem;
        }

        .loc-meta {
            gap: 0.35rem;
        }

        .loc-meta-row {
            font-size: 0.82rem;
        }

        .loc-meta-row svg {
            width: 0.95rem;
            height: 0.95rem;
        }

        .loc-actions {
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .loc-action {
            font-size: 0.8rem;
        }

        .contact-cta-bar {
            padding: 1.5rem 1.4rem;
        }

        .contact-cta-btn {
            flex: 1 1 auto;
            justify-content: center;
        }
    }
</style>
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
'icon' => 'home-modern',
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
'icon' => 'storefront',
'direccion' => 'Au Dr. Ricardo Balbín Km 30 - Local 03B',
'localidad' => 'Guillermo Enrique Hudson, Buenos Aires',
'horarios' => 'Lun a Vie: 10:00 - 19:00',
'telefono_label' => '011 9268-3417',
'telefono_tel' => '01192683417',
'lat' => -34.7763988,
'lng' => -58.1634747,
],
];

// Version reducida para el JS del mapa
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
@endphp

<section class="contact-hero">
    <img src="{{ asset('images/contactohero.jpg') }}" alt="Contacto" class="contact-hero-bg">
    <div class="container contact-hero-content">
        <div class="row">
            <div class="col-lg-8 col-xl-7">
                <h1>Contacto</h1>
                <p>Estamos para ayudarte. Visitanos o comunicate con nosotros.</p>
            </div>
        </div>
    </div>
</section>

<section class="loc-section">
    <div class="container">
        <div class="section-header text-center">
            <h2 class="section-title">Dónde encontrarnos</h2>
        </div>

        <div class="loc-panel">
            <div class="row g-0">
                <div class="col-lg-5 loc-list">
                    @foreach($sedes as $i => $sede)
                    <button type="button" class="loc-item {{ $i === 0 ? 'is-active' : '' }}"
                        data-sede="{{ $sede['key'] }}" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">
                        <div class="loc-item-head">
                            <span class="loc-item-icon">
                                @if($sede['icon'] === 'home-modern')
                                <x-heroicon-o-home-modern />
                                @else
                                <x-heroicon-o-building-storefront />
                                @endif
                            </span>
                            <span>
                                <span class="loc-item-title">{{ $sede['tipo'] }}</span>
                            </span>
                        </div>
                        <div class="loc-meta">
                            <div class="loc-meta-row">
                                <x-heroicon-o-map-pin />
                                <span>{{ $sede['direccion'] }} — {{ $sede['localidad'] }}</span>
                            </div>
                            <div class="loc-meta-row">
                                <x-heroicon-o-clock />
                                <span>{{ $sede['horarios'] }}</span>
                            </div>
                            <div class="loc-meta-row">
                                <x-heroicon-o-phone />
                                <a href="tel:{{ $sede['telefono_tel'] }}">{{ $sede['telefono_label'] }}</a>
                            </div>
                        </div>
                        <div class="loc-actions">
                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $sede['lat'] }},{{ $sede['lng'] }}"
                                class="loc-action" target="_blank" rel="noopener">
                                <x-heroicon-o-map-pin />
                                Cómo llegar
                            </a>
                            <a href="tel:{{ $sede['telefono_tel'] }}" class="loc-action">
                                <x-heroicon-o-phone />
                                Llamar
                            </a>
                        </div>
                    </button>
                    @endforeach
                </div>
                <div class="col-lg-7 loc-map-col">
                    <div id="map" data-sedes="{{ json_encode($sedesMapa) }}"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="contact-cta">
    <div class="container">
        <div class="contact-cta-bar">
            <div class="contact-cta-copy">
                <h2>¿Preferís escribirnos?</h2>
                <p>Contanos sobre tu proyecto y te ayudamos a encontrar la mejor solución.</p>
            </div>
            <div class="contact-cta-actions">
                @if($waHref)
                <a href="{{ $waHref }}" class="contact-cta-btn contact-cta-btn-solid" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp"></i>
                    WhatsApp
                </a>
                @endif
                <a href="https://www.instagram.com/giacomazzi_srl/" class="contact-cta-btn contact-cta-btn-ghost"
                    target="_blank" rel="noopener">
                    <i class="bi bi-instagram"></i>
                    Instagram
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const mapEl = document.getElementById('map');
    const sedes = JSON.parse(mapEl.dataset.sedes);

    // Centro aproximado entre las dos sedes
    const initialZoom = window.matchMedia('(max-width: 767.98px)').matches ? 11 : 12;
    const map = L.map(mapEl, {
        // La rueda no hace zoom para no entorpecer el scroll de la pagina.
        // El zoom queda disponible por botones (+/-) y gesto de dos dedos.
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
            '<div style="font-family: \'Segoe UI\', sans-serif; line-height: 1.4;">' +
            '<strong style="color: #287452; font-size: 1rem;">' + s.tipo + '</strong><br>' +
            s.direccion + '<br>' +
            s.localidad + '<br>' +
            '<a href="tel:' + s.telTel + '" style="color: #287452; text-decoration: none; font-weight: 600;">' + s.telLabel + '</a>' +
            '</div>', {
                autoPan: false
            }
        );
        // Al clickear el pin, marcar como activa su tarjeta en la lista
        marker.on('click', function() {
            setActive(s.key, false);
        });
        markers[s.key] = marker;
    });

    const items = document.querySelectorAll('.loc-item[data-sede]');

    function setActive(key, moveMap) {
        if (moveMap === undefined) moveMap = true;
        items.forEach(function(item) {
            const isActive = item.dataset.sede === key;
            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        const marker = markers[key];
        if (!marker) return;

        if (moveMap) {
            const zoom = 15;
            const punto = map.project(marker.getLatLng(), zoom).subtract([0, 60]);
            map.setView(map.unproject(punto, zoom), zoom, {
                animate: true
            });
        }
        marker.openPopup();
    }

    items.forEach(function(item) {
        item.addEventListener('click', function(e) {
            // Los enlaces internos (tel / como llegar) actuan por su cuenta
            if (e.target.closest('a')) return;
            setActive(item.dataset.sede);
        });
    });

    // Reajuste por si el contenedor cambia de tamano (mobile/desktop)
    window.addEventListener('load', function() {
        map.invalidateSize();
    });
</script>
@endsection