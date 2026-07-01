@extends('layouts.app-externo')
@section('title', 'Contacto - Giacomazzi Glass')

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

    .contact-section {
        padding: 5rem 0;
    }

    .sede-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: var(--external-white);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.85rem;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .sede-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 14px 32px rgba(27, 45, 33, 0.12);
    }

    /* Header con degradé de marca */
    .sede-card-header {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding: 1.35rem 1.75rem;
        background: linear-gradient(135deg, var(--external-primary) 0%, #1f5c3e 100%);
        color: var(--external-white);
    }

    .sede-card-header-icon {
        width: 46px;
        height: 46px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 12px;
    }

    .sede-card-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .sede-card-body {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        padding: 1.5rem 1.75rem 1.75rem;
    }

    .sede-info-row {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    .sede-info-row:first-child {
        padding-top: 0;
    }

    .sede-info-icon {
        flex-shrink: 0;
        margin-top: 0.15rem;
        width: 1.2rem;
        height: 1.2rem;
        color: var(--external-primary);
    }

    .sede-info-text {
        color: var(--external-secondary-soft);
        line-height: 1.55;
        font-size: 0.92rem;
    }

    .sede-info-text strong {
        display: block;
        margin-bottom: 0.15rem;
        color: var(--external-secondary);
        font-weight: 600;
        font-size: 0.88rem;
    }

    .sede-info-text a {
        color: var(--external-primary);
        font-weight: 600;
        text-decoration: none;
    }

    .sede-info-text a:hover {
        text-decoration: underline;
    }

    #map {
        height: 400px;
        border-radius: 0.5rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
    }

    .map-section {
        padding: 3rem 0;
        background-color: #f8f9fa;
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

        .contact-section {
            padding: 2.5rem 0;
        }

        .map-section {
            padding: 2rem 0;
        }

        #map {
            height: 300px;
        }
    }
</style>
@endsection
@section('content')


<!-- Hero Section -->
<section class="contact-hero">
    <img src="{{ asset('images/contactohero.jpg') }}" alt="Contacto" class="contact-hero-bg">
    <div class="container contact-hero-content">
        <div class="row">
            <div class="col-lg-8 col-xl-7">
                <p class="contact-hero-eyebrow">Giacomazzi Glass</p>
                <h1>Contacto</h1>
                <p>Estamos para ayudarte. Visitanos o comunicate con nosotros.</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Cards Section -->
<section class="contact-section">
    <div class="container">
        <div class="section-header text-center">
            <h2 class="section-title">Dónde encontrarnos</h2>
            <p class="section-subtitle">Dos puntos de atención para acompañarte en cada etapa de tu proyecto</p>
        </div>
        <div class="row g-4">
            <!-- Fábrica -->
            <div class="col-lg-6">
                <div class="sede-card">
                    <div class="sede-card-header">
                        <span class="sede-card-header-icon">
                            <x-heroicon-o-home-modern style="width: 26px; height: 26px;" />
                        </span>
                        <h3>Fábrica</h3>
                    </div>
                    <div class="sede-card-body">
                        <div class="sede-info-row">
                            <x-heroicon-o-map-pin class="sede-info-icon" />
                            <div class="sede-info-text">
                                <strong>Dirección</strong>
                                San Juan 1978 entre Av. La Plata y Madame Curie<br>
                                Quilmes Oeste, Buenos Aires
                            </div>
                        </div>
                        <div class="sede-info-row">
                            <x-heroicon-o-clock class="sede-info-icon" />
                            <div class="sede-info-text">
                                <strong>Horarios</strong>
                                Lunes a Viernes: 8:00 - 17:00
                            </div>
                        </div>
                        <div class="sede-info-row">
                            <x-heroicon-o-phone class="sede-info-icon" />
                            <div class="sede-info-text">
                                <strong>Teléfono</strong>
                                <a href="tel:01164457059">011 6445-7059</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Local al Público -->
            <div class="col-lg-6">
                <div class="sede-card">
                    <div class="sede-card-header">
                        <span class="sede-card-header-icon">
                            <x-heroicon-o-building-storefront style="width: 26px; height: 26px;" />
                        </span>
                        <h3>Local al público</h3>
                    </div>
                    <div class="sede-card-body">
                        <div class="sede-info-row">
                            <x-heroicon-o-map-pin class="sede-info-icon" />
                            <div class="sede-info-text">
                                <strong>Dirección</strong>
                                Au Dr. Ricardo Balbín Km 30 - Local 03B<br>
                                Guillermo Enrique Hudson, Buenos Aires
                            </div>
                        </div>
                        <div class="sede-info-row">
                            <x-heroicon-o-clock class="sede-info-icon" />
                            <div class="sede-info-text">
                                <strong>Horarios</strong>
                                Lunes a Viernes: 10:00 - 19:00
                            </div>
                        </div>
                        <div class="sede-info-row">
                            <x-heroicon-o-phone class="sede-info-icon" />
                            <div class="sede-info-text">
                                <strong>Teléfono</strong>
                                <a href="tel:01192683417">011 9268-3417</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">Nuestras ubicaciones</h2>
            <p class="section-subtitle">Encontranos en el mapa</p>
        </div>
        <div id="map"></div>
    </div>
</section>
@endsection

@section('script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Inicializar mapa centrado entre las dos ubicaciones
    // En mobile bajamos un punto de zoom para que entren los dos pines
    const initialZoom = window.matchMedia('(max-width: 767.98px)').matches ? 11 : 12;
    const map = L.map('map').setView([-34.752, -58.224], initialZoom);

    // Agregar tiles de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
        minZoom: 10
    }).addTo(map);

    // Icono personalizado verde
    const greenIcon = L.icon({
        iconUrl: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="25" height="41" viewBox="0 0 25 41"%3E%3Cpath fill="%23287452" d="M12.5 0C5.596 0 0 5.596 0 12.5c0 1.996.47 3.882 1.299 5.555L12.5 41l11.201-22.945C24.53 16.382 25 14.496 25 12.5 25 5.596 19.404 0 12.5 0z"/%3E%3Ccircle fill="%23ffffff" cx="12.5" cy="12.5" r="5"/%3E%3C/svg%3E',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34]
    });

    // Marcador Fábrica (San Juan 1978, Quilmes Oeste)
    const fabricaMarker = L.marker([-34.7277121, -58.2851433], {
        icon: greenIcon
    }).addTo(map);
    fabricaMarker.bindPopup(`
        <div style="font-family: 'Segoe UI', sans-serif;">
            <strong style="color: #287452; font-size: 1rem;">Fábrica</strong><br>
            San Juan 1978<br>
            Quilmes Oeste, Buenos Aires<br>
            <a href="tel:01164457059" style="color: #287452; text-decoration: none;"> 011 6445-7059</a>
        </div>
    `);

    // Marcador Local al Público (Polo Hudson - Au Balbín Km 30)
    const localMarker = L.marker([-34.7763988, -58.1634747], {
        icon: greenIcon
    }).addTo(map);
    localMarker.bindPopup(`
        <div style="font-family: 'Segoe UI', sans-serif;">
            <strong style="color: #287452; font-size: 1rem;">Local al público</strong><br>
            Au Dr. Ricardo Balbín Km 30<br>
            Local 03B - Polo Hudson<br>
            Guillermo Enrique Hudson, Buenos Aires<br>
            <a href="tel:01192683417" style="color: #287452; text-decoration: none;"> 011 9268-3417</a>
        </div>
    `);
</script>
@endsection