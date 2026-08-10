<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Aberturas Giacomazzi'))</title>

    <link rel="icon" href="{{ versioned_asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ versioned_asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800;900&family=Asap:wght@400;500;600;700&display=swap">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ versioned_asset('css/externo.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/toast.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/carrito.css') }}">

    @yield('css')
</head>

<body class="external-body d-flex flex-column min-vh-100">
    @include('layouts.partials.toast')

    <header>
        {{-- ── Navbar ──────────────────────────────────────────────────────── --}}
        <nav class="navbar navbar-expand-lg external-navbar py-2">
            <div class="container external-nav-container">

                {{-- Logo --}}
                <a class="navbar-brand external-logo" href="{{ route('welcome') }}" aria-label="Inicio - Aberturas Giacomazzi">
                    <img src="{{ versioned_asset('images/logo.svg') }}" alt="Aberturas Giacomazzi" class="external-logo-img" width="842" height="113" fetchpriority="high">
                </a>

                {{-- ── Menú desktop (columna central del grid) ──────────────── --}}
                <ul class="navbar-nav external-menu gap-2 d-none d-lg-flex">
                    <li class="nav-item">
                        <a class="nav-link external-menu-btn {{ request()->routeIs('welcome') ? 'active' : '' }}"
                            href="{{ route('welcome') }}">Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link external-menu-btn dropdown-toggle {{ request()->routeIs('productos.*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Productos
                        </a>
                        <ul class="dropdown-menu external-dropdown-menu">
                            @if(!empty($categoriasMenu))
                            @foreach($categoriasMenu as $categoria)
                            <li>
                                <a class="dropdown-item external-dropdown-item {{ request()->routeIs('productos.categoria') && request()->route('id') == $categoria['id'] ? 'active' : '' }}"
                                    href="{{ route('productos.categoria', $categoria['id']) }}">
                                    {{ $categoria['nombre'] }}
                                </a>
                            </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            @endif
                            <li>
                                <a class="dropdown-item external-dropdown-item fw-semibold {{ request()->routeIs('productos.todos') ? 'active' : '' }}"
                                    href="{{ route('productos.todos') }}">
                                    Ver todos los productos
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link external-menu-btn {{ request()->routeIs('nosotros') ? 'active' : '' }}"
                            href="{{ route('nosotros') }}">Nosotros</a>
                    </li>
                    {{-- Contacto se unificó dentro de Nosotros: una sola entrada
                         para una sola página. La ruta /contacto sigue viva como
                         redirect para los links ya publicados. --}}
                </ul>

                {{-- ── Carrito desktop (columna derecha del grid) ───────────── --}}
                <div class="external-cart-wrapper d-none d-lg-flex">
                    <a href="#" class="external-cart-link" aria-label="Carrito de compras"
                        data-bs-toggle="offcanvas" data-bs-target="#carritoOffcanvas">
                        <x-heroicon-o-shopping-cart />
                        <span class="cart-badge" style="display:none;">0</span>
                    </a>
                </div>

                {{-- ── Mobile controls (ocultos en desktop) ────────────────── --}}
                <div class="d-flex d-lg-none align-items-center gap-2">
                    <a href="#" class="external-cart-link" aria-label="Carrito de compras"
                        data-bs-toggle="offcanvas" data-bs-target="#carritoOffcanvas">
                        <x-heroicon-o-shopping-cart />
                        <span class="cart-badge" style="display:none;">0</span>
                    </a>
                    <button class="mobile-menu-btn" id="mobile-menu-btn"
                        aria-label="Abrir menú" aria-expanded="false" aria-controls="mobile-nav-drawer">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>

            </div>
        </nav>
    </header>

    {{-- ── Mobile drawer (fuera del header para que position:fixed sea relativo al viewport) --}}
    <div class="mobile-nav-drawer" id="mobile-nav-drawer" aria-hidden="true">
        <div class="mobile-drawer-backdrop" id="mobile-drawer-backdrop"></div>

        <div class="mobile-drawer-panel" role="dialog" aria-modal="true" aria-label="Menú de navegación">

            {{-- Header del drawer --}}
            <div class="mobile-drawer-header">
                <a href="{{ route('welcome') }}" class="mobile-drawer-logo" aria-label="Inicio">
                    <img src="{{ versioned_asset('images/logo.svg') }}" alt="Aberturas Giacomazzi" class="mobile-drawer-logo-img" width="842" height="113" decoding="async">
                </a>
                <button class="mobile-drawer-close" id="mobile-drawer-close" aria-label="Cerrar menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Navegación --}}
            <nav class="mobile-drawer-nav" aria-label="Navegación principal">
                <a href="{{ route('welcome') }}" class="mobile-nav-link">Inicio</a>

                <div class="mobile-nav-section" id="mobile-products-section">
                    <button class="mobile-nav-section-toggle" id="mobile-products-toggle"
                        aria-expanded="false" aria-controls="mobile-products-content">
                        Productos
                        <i class="bi bi-chevron-down toggle-chevron"></i>
                    </button>
                    <div class="mobile-nav-section-content" id="mobile-products-content">
                        @if(!empty($categoriasMenu))
                        <div class="mobile-nav-categories">
                            @foreach($categoriasMenu as $categoria)
                            <a href="{{ route('productos.categoria', $categoria['id']) }}"
                                class="mobile-nav-category-pill">
                                {{ $categoria['nombre'] }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                        <a href="{{ route('productos.todos') }}" class="mobile-nav-todos">
                            Ver todos los productos
                            <i class="bi bi-arrow-right-short"></i>
                        </a>
                    </div>
                </div>

                <a href="{{ route('nosotros') }}" class="mobile-nav-link">Nosotros</a>
            </nav>

            {{-- Footer del drawer — CTA carrito --}}
            <div class="mobile-drawer-footer">
                <a href="#" class="mobile-nav-cart-btn" aria-label="Ver carrito y cotizar">
                    <x-heroicon-o-shopping-cart />
                    Ver carrito y cotizar
                </a>
            </div>

        </div>
    </div>

    <main class="external-main flex-grow-1">
        @yield('content')
    </main>

    <footer class="external-footer mt-auto">
        @php
            $footerWa = preg_replace('/\D/', '', config('app.whatsapp_number', ''));
        @endphp
        <div class="container external-footer-inner">
            <a href="{{ route('welcome') }}" class="external-footer-logo" aria-label="Aberturas Giacomazzi">
                <img src="{{ versioned_asset('images/logo.svg') }}" alt="Aberturas Giacomazzi" class="external-footer-logo-img" width="842" height="113" loading="lazy" decoding="async">
            </a>

            <nav class="external-footer-social" aria-label="Redes sociales">
                <a href="{{ $footerWa ? 'https://wa.me/' . $footerWa : '#' }}"
                    @if($footerWa) target="_blank" rel="noopener" @endif aria-label="WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                </a>
                <a href="https://www.instagram.com/giacomazzi_srl/" target="_blank" rel="noopener" aria-label="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
            </nav>

            <div class="external-footer-locations">
                <address class="external-footer-loc">
                    <span class="external-footer-loc-label">Fábrica</span>
                    <span class="external-footer-loc-addr">San Juan 1978, Quilmes Oeste</span>
                    <a href="tel:+541164457059" class="external-footer-loc-phone">011 6445-7059</a>
                </address>
                <address class="external-footer-loc">
                    <span class="external-footer-loc-label">Local al público</span>
                    <span class="external-footer-loc-addr">Au Dr. Ricardo Balbín Km 30, G. E. Hudson</span>
                    <a href="tel:+541192683417" class="external-footer-loc-phone">011 9268-3417</a>
                </address>
            </div>

            <p class="external-footer-copy">
                <span>© {{ date('Y') }} Aberturas Giacomazzi</span>
                <a href="{{ route('login-view') }}" class="external-footer-lock"
                    target="_blank" rel="noopener"
                    aria-label="Acceso interno" title="Acceso interno">
                    <i class="bi bi-lock-fill"></i>
                </a>
            </p>
        </div>
    </footer>

    @include('layouts.partials.carrito')

    @if(request()->routeIs('welcome', 'nosotros', 'productos.todos', 'productos.categoria'))
        @include('layouts.partials.whatsapp-float')
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script src="{{ versioned_asset('js/toast.js') }}"></script>
    <script src="{{ versioned_asset('js/modules/layout.js') }}"></script>

    <script>
        // `cantidad` es la cantidad de líneas del carrito (ver CarritoController::totalLineas).
        window.__carritoInit = {!! json_encode(['cantidad' => count(session('carrito', [])), 'carrito' => array_values(session('carrito', []))]) !!};
    </script>
    <script src="{{ versioned_asset('js/carrito.js') }}"></script>

    @yield('script')
</body>

</html>
