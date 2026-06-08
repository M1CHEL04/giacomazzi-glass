@extends('layouts.app-externo')
@section('title', 'Contacto - Giacomazzi Glass')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .contact-hero {
        padding: 4rem 0 3rem;
        margin-top: calc(-1.5rem - 1px);
        /* Compensa el padding del main y el borde del navbar */
        position: relative;
        overflow: hidden;
    }

    .contact-hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center 92%;
        z-index: 0;
    }

    .contact-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(45, 90, 63, 0.85) 0%, rgba(26, 46, 31, 0.9) 100%);
        z-index: 0;
    }

    .contact-hero-content {
        position: relative;
        z-index: 1;
    }

    .contact-hero h1 {
        color: var(--external-white);
        font-size: 2.75rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }

    .contact-hero p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1rem;
        margin: 0;
    }

    .contact-section {
        padding: 3.5rem 0;
    }

    .contact-card {
        background: var(--external-white);
        border-radius: 0.5rem;
        padding: 2rem;
        height: 100%;
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .contact-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .contact-card-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--external-primary) 0%, var(--external-primary-soft) 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--external-white);
        font-size: 1.75rem;
        margin-bottom: 1.25rem;
    }

    .contact-card h3 {
        font-size: 1.35rem;
        font-weight: 600;
        color: var(--external-secondary);
        margin-bottom: 1rem;
    }

    .contact-info {
        color: var(--external-secondary-soft);
        line-height: 1.7;
        margin-bottom: 1rem;
    }

    .contact-info strong {
        color: var(--external-secondary);
        display: block;
        margin-bottom: 0.25rem;
    }

    .contact-info a {
        color: var(--external-primary);
        text-decoration: none;
        font-weight: 500;
    }

    .contact-info a:hover {
        text-decoration: underline;
    }

    .contact-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .btn-contact {
        padding: 0.65rem 1.5rem;
        border-radius: 0.375rem;
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-whatsapp {
        background-color: #25D366;
        color: var(--external-white);
    }

    .btn-whatsapp:hover {
        background-color: #20BA5A;
        color: var(--external-white);
        transform: translateY(-2px);
    }

    .btn-email {
        background-color: var(--external-primary);
        color: var(--external-white);
    }

    .btn-email:hover {
        background-color: #1f5c3f;
        color: var(--external-white);
        transform: translateY(-2px);
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
        }

        .contact-hero p {
            font-size: 1rem;
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

        .contact-actions {
            flex-direction: column;
        }

        .btn-contact {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endsection
@section('content')


<!-- Hero Section -->
<section class="contact-hero">
    <img src="{{ asset('images/contactohero.jpg') }}" alt="Contacto" class="contact-hero-bg">
    <div class="contact-hero-content">
        <div class="container text-center">
            <h1>Contacto</h1>
            <p>Estamos para ayudarte. Visitanos o comunicate con nosotros.</p>
        </div>
    </div>
</section>

<!-- Contact Cards Section -->
<section class="contact-section">
    <div class="container">
        <div class="row g-4">
            <!-- Fábrica -->
            <div class="col-lg-6">
                <div class="contact-card">
                    <div class="contact-card-icon">
                        <x-heroicon-o-home-modern style="width: 35px; height: 35px;" />
                    </div>
                    <h3>Fábrica</h3>
                    <div class="contact-info">
                        <strong>Dirección</strong>
                        San Juan 1978 entre Av. La Plata y Madame Curie<br>
                        Quilmes Oeste, Buenos Aires
                    </div>
                    <div class="contact-info">
                        <strong>Horarios</strong>
                        Lunes a Viernes: 8:00 - 17:00<br>
                    </div>
                    <div class="contact-actions">
                        <a href="https://wa.me/542395425498" target="_blank" class="btn-contact btn-whatsapp">
                            <x-fluentui-chat-24-o /> WhatsApp
                        </a>
                        <a href="mailto:contacto@giacomazzi.com" class="btn-contact btn-email">
                            <x-fluentui-mail-24-o /> Email
                        </a>
                    </div>
                </div>
            </div>

            <!-- Local al Público -->
            <div class="col-lg-6">
                <div class="contact-card">
                    <div class="contact-card-icon">
                        <x-heroicon-o-building-storefront style="width: 35px; height: 35px;" />

                    </div>
                    <h3>Local al público</h3>
                    <div class="contact-info">
                        <strong>Dirección</strong>
                        Au Dr. Ricardo Balbín Km 30 - Local 03B<br>
                        Guillermo Enrique Hudson, Buenos Aires
                    </div>
                    <div class="contact-info">
                        <strong>Horarios</strong>
                        Lunes a Viernes: 10:00 - 19:00<br>
                    </div>
                    <div class="contact-actions">
                        <a href="https://wa.me/541164457059" target="_blank" class="btn-contact btn-whatsapp">
                            WhatsApp
                        </a>
                        <a href="mailto:contacto@giacomazzi.com" class="btn-contact btn-email">
                            Email
                        </a>
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
    const map = L.map('map').setView([-34.752, -58.224], 12);

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