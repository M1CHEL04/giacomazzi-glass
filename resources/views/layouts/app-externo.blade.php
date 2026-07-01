<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Giacomazzi Glass'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('css/externo.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carrito.css') }}">

    @yield('css')
</head>

<body class="external-body d-flex flex-column min-vh-100">
    @include('layouts.partials.toast')

    <header>
        {{-- ── Navbar ──────────────────────────────────────────────────────── --}}
        <nav class="navbar navbar-expand-lg external-navbar py-2">
            <div class="container external-nav-container">

                {{-- Logo --}}
                <a class="navbar-brand external-logo" href="{{ route('welcome') }}" aria-label="Logo de la marca">
                    LOGO
                </a>

                {{-- ── Desktop nav (oculto en mobile) ──────────────────────── --}}
                <div class="d-none d-lg-flex flex-grow-1 align-items-center">
                    <ul class="navbar-nav mx-auto external-menu gap-2">
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
                            <a class="nav-link external-menu-btn {{ request()->routeIs('contacto') ? 'active' : '' }}"
                                href="{{ route('contacto') }}">Contacto</a>
                        </li>
                    </ul>

                    <div class="external-cart-wrapper d-flex">
                        <a href="#" class="external-cart-link" aria-label="Carrito de compras"
                            data-bs-toggle="offcanvas" data-bs-target="#carritoOffcanvas">
                            <x-heroicon-o-shopping-cart />
                            <span class="cart-badge" style="display:none;">0</span>
                        </a>
                    </div>
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
                <a href="{{ route('welcome') }}" class="mobile-drawer-logo" aria-label="Inicio">LOGO</a>
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

                <a href="{{ route('contacto') }}" class="mobile-nav-link">Contacto</a>
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

    <footer class="external-footer mt-auto py-4">
        <div class="container">
            <div class="row align-items-center gy-4">
                <div class="col-12 col-lg-4">
                    <h6 class="external-footer-title mb-3">Contacto</h6>
                    <div class="external-contact-block mb-3">
                        <strong>Fábrica</strong><br>
                        San Juan 1978 entre Av. La Plata y Madame Curie<br>
                        Quilmes Oeste, Buenos Aires<br>
                        011 6445-7059
                    </div>
                    <div class="external-contact-block">
                        <strong>Local al público</strong><br>
                        Au Dr. Ricardo Balbín Km 30 - Local 03B<br>
                        Guillermo Enrique Hudson, Buenos Aires<br>
                        011 9268-3417
                    </div>
                </div>

                <div class="col-12 col-lg-4 text-center">
                    <div class="external-footer-logo mx-auto" aria-label="Logo en footer">LOGO</div>
                </div>

                <div class="col-12 col-lg-4 d-flex justify-content-lg-end justify-content-center align-items-center gap-3">
                    <a href="#" class="external-social-link" aria-label="WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                    <a href="https://www.instagram.com/giacomazzi_srl/" target="_blank"
                        class="external-social-link" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    @include('layouts.partials.carrito')

    @if(request()->routeIs('welcome', 'productos.todos', 'productos.categoria'))
        @include('layouts.partials.whatsapp-float')
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script src="{{ asset('js/toast.js') }}"></script>
    <script src="{{ asset('js/modules/layout.js') }}"></script>

    <script>
        window.__carritoInit = {!! json_encode(['cantidad' => count(session('carrito', [])), 'carrito' => array_values(session('carrito', []))]) !!};
    </script>
    <script src="{{ asset('js/carrito.js') }}"></script>

    @yield('script')
</body>

</html>
