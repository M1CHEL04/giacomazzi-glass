@extends('layouts.app-externo')
@section('title', 'Giacomazzi Glass - Aberturas de Aluminio')

@section('content')
<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-7">
                <h1 class="hero-title">Aberturas de Aluminio<br><span class="hero-highlight">Calidad & Diseño</span></h1>
                <p class="hero-subtitle">Productos estándar de alta calidad listos para tu proyecto. Fabricación local con garantía.</p>
                <div class="hero-buttons">
                    <a href="#productos" class="btn-hero-primary">Ver productos</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Características -->
<section class="features-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3 class="feature-title">Productos estándar</h3>
                    <p class="feature-text">Medidas estandarizadas de alta calidad, listas para entrega inmediata.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h3 class="feature-title">Fabricación local</h3>
                    <p class="feature-text">Producidos en nuestras instalaciones con materiales de primera calidad.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-award"></i>
                    </div>
                    <h3 class="feature-title">Garantía asegurada</h3>
                    <p class="feature-text">Respaldamos nuestros productos con garantía de fábrica.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Productos Destacados -->
<section class="products-section" id="productos">
    <div class="container">
        <div class="section-header text-center">
            <h2 class="section-title">Productos estándar</h2>
            <p class="section-subtitle">Seleccioná por categoría y elegí el producto perfecto para tu proyecto</p>
        </div>

        @if(isset($categoriasMenu) && $categoriasMenu->count() > 0)
        <div class="row g-4">
            @foreach($categoriasMenu->take(6) as $categoria)
            <div class="col-md-6 col-lg-4">
                <div class="product-category-card">
                    <div class="product-category-image">
                        <div class="product-category-placeholder">
                            <i class="bi bi-window"></i>
                            <span>{{ $categoria->nombre }}</span>
                        </div>
                    </div>
                    <div class="product-category-content">
                        <h3 class="product-category-title">{{ $categoria->nombre }}</h3>
                        <p class="product-category-count">{{ $categoria->productos_count ?? 0 }} productos disponibles</p>
                        <a href="#" class="product-category-link">Ver productos <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-5">
            <p class="text-muted">No hay categorías disponibles en este momento.</p>
        </div>
        @endif

        <div class="text-center mt-5">
            <a href="#" class="btn-view-all">Ver todas las categorías</a>
        </div>
    </div>
</section>

<!-- Productos a Medida -->
<section class="custom-section" id="medida">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="custom-image">
                    <div class="custom-image-placeholder">
                        <i class="bi bi-gear-fill"></i>
                        <span>Productos a medida</span>
                    </div>

                </div>
            </div>
            <div class="col-lg-6">
                <div class="custom-content">
                    <h2 class="custom-title">¿Necesitás algo a medida?</h2>
                    <p class="custom-text">Si nuestros productos estándar no se ajustan a tus necesidades, podemos fabricar aberturas personalizadas según tus especificaciones exactas.</p>
                    <ul class="custom-list">
                        <li><i class="bi bi-check2"></i> Diseño personalizado según tus medidas</li>
                        <li><i class="bi bi-check2"></i> Asesoramiento técnico profesional</li>
                        <li><i class="bi bi-check2"></i> Presupuesto sin compromiso</li>
                        <li><i class="bi bi-check2"></i> Instalación disponible</li>
                    </ul>
                    <a href="{{ route('contacto') }}" class="btn-custom-contact">Solicitar Presupuesto</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="cta-section">
    <div class="container">
        <div class="cta-card">
            <h2 class="cta-title">¿Listo para tu proyecto?</h2>
            <p class="cta-text">Explorá nuestro catálogo de productos estándar o contactános para una solución personalizada</p>
            <div class="cta-buttons">
                <a href="#productos" class="btn-cta-primary">Ver Catálogo</a>
                <a href="{{ route('contacto') }}" class="btn-cta-secondary">Contacto</a>
            </div>
        </div>
    </div>
</section>
@endsection