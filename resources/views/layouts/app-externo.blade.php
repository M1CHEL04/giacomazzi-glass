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

    {{-- El header es el que va pegado arriba (no el <nav>): sticky necesita
         que el elemento tenga lugar para desplazarse dentro de su padre. --}}
    <header class="external-header">
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
                    {{-- Menú de Productos en dos niveles: arriba el catálogo
                         completo, y debajo una rama por tipo. Al apuntar una
                         rama, el panel derecho lista su "ver todos" más las
                         categorías que hoy tienen producto activo de ese tipo
                         (ver App\Services\MenuCategorias). --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link external-menu-btn dropdown-toggle {{ request()->routeIs('productos.*') ? 'active' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Productos
                        </a>
                        <div class="dropdown-menu external-dropdown-menu nav-prod" id="nav-prod-menu">

                            <a class="nav-prod-todos {{ request()->routeIs('productos.todos') ? 'active' : '' }}"
                                href="{{ route('productos.todos') }}">
                                Ver todos los productos
                                <i class="bi bi-arrow-right"></i>
                            </a>

                            @foreach ([
                                'estandar' => [
                                    'label'      => 'Línea estándar',
                                    'titulo'     => 'Línea estándar',
                                    'verTodos'   => route('productos.todos', ['tipos' => ['estandar']]),
                                    'verLabel'   => 'Ver todos los estándar',
                                    'categorias' => $menuEstandar,
                                    'ruta'       => 'productos.categoria',
                                    'activaRama' => request()->routeIs('productos.categoria', 'productos.show'),
                                    'activaVer'  => false,
                                ],
                                'especial' => [
                                    'label'      => 'Línea adapta',
                                    'titulo'     => 'Línea adapta',
                                    'verTodos'   => route('productos.especiales'),
                                    'verLabel'   => 'Ver toda la línea adapta',
                                    'categorias' => $menuEspeciales,
                                    'ruta'       => 'productos.especial.categoria',
                                    'activaRama' => request()->routeIs('productos.especiales', 'productos.especial.*'),
                                    'activaVer'  => request()->routeIs('productos.especiales'),
                                ],
                            ] as $clave => $rama)
                            {{-- Cada rama contiene su propio panel, posicionado
                                 al costado. Así el despliegue es hover y
                                 focus-within de CSS: sin rama apuntada no hay
                                 panel, y sin JS el menú igual funciona. --}}
                            <div class="nav-prod-rama-wrap" data-rama-wrap>
                                <button type="button"
                                    class="nav-prod-rama {{ $rama['activaRama'] ? 'active' : '' }}"
                                    aria-haspopup="true" aria-expanded="false"
                                    aria-controls="nav-prod-panel-{{ $clave }}">
                                    <span>{{ $rama['label'] }}</span>
                                    <i class="bi bi-chevron-right"></i>
                                </button>

                                <div class="nav-prod-panel" id="nav-prod-panel-{{ $clave }}">
                                    <p class="nav-prod-panel-titulo">{{ $rama['titulo'] }}</p>

                                    <a class="nav-prod-vertodos {{ $rama['activaVer'] ? 'active' : '' }}"
                                        href="{{ $rama['verTodos'] }}">
                                        {{ $rama['verLabel'] }}
                                        <i class="bi bi-arrow-right"></i>
                                    </a>

                                    @if(!empty($rama['categorias']))
                                    <ul class="nav-prod-cats">
                                        @foreach($rama['categorias'] as $categoria)
                                        <li>
                                            <a class="nav-prod-cat {{ request()->routeIs($rama['ruta']) && request()->route('id') == $categoria['id'] ? 'active' : '' }}"
                                                href="{{ route($rama['ruta'], $categoria['id']) }}">
                                                {{ $categoria['nombre'] }}
                                            </a>
                                        </li>
                                        @endforeach
                                    </ul>
                                    @else
                                    <p class="nav-prod-vacio">Todavía no hay productos publicados.</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach

                        </div>
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

                {{-- Mismo árbol que en desktop, pero como acordeón anidado.
                     Las categorías van en filas a lo ancho y no en pastillas:
                     el nombre no se corta, la fila entera es el área táctil y
                     la lista se recorre con el pulgar sin apuntar. --}}
                <div class="mobile-nav-section" data-acordeon>
                    <button class="mobile-nav-section-toggle" data-acordeon-toggle
                        aria-expanded="false" aria-controls="mobile-products-content">
                        Productos
                        <i class="bi bi-chevron-down toggle-chevron"></i>
                    </button>
                    <div class="mobile-nav-section-content" id="mobile-products-content" data-acordeon-panel>
                        <div class="mobile-nav-sublista">

                            <a href="{{ route('productos.todos') }}" class="mobile-nav-destacado">
                                Ver todos los productos
                                <i class="bi bi-arrow-right"></i>
                            </a>

                            @foreach ([
                                'estandar' => [
                                    'label'      => 'Línea estándar',
                                    'verTodos'   => route('productos.todos', ['tipos' => ['estandar']]),
                                    'verLabel'   => 'Ver todos los estándar',
                                    'categorias' => $menuEstandar,
                                    'ruta'       => 'productos.categoria',
                                ],
                                'especial' => [
                                    'label'      => 'Línea adapta',
                                    'verTodos'   => route('productos.especiales'),
                                    'verLabel'   => 'Ver toda la línea adapta',
                                    'categorias' => $menuEspeciales,
                                    'ruta'       => 'productos.especial.categoria',
                                ],
                            ] as $clave => $rama)
                            <div class="mobile-nav-section mobile-nav-section--anidada" data-acordeon>
                                <button class="mobile-nav-section-toggle" data-acordeon-toggle
                                    aria-expanded="false" aria-controls="mobile-rama-{{ $clave }}">
                                    {{ $rama['label'] }}
                                    <i class="bi bi-chevron-down toggle-chevron"></i>
                                </button>
                                <div class="mobile-nav-section-content" id="mobile-rama-{{ $clave }}" data-acordeon-panel>
                                    <div class="mobile-nav-sublista">
                                        <a href="{{ $rama['verTodos'] }}" class="mobile-nav-destacado">
                                            {{ $rama['verLabel'] }}
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                        {{-- Sin chevron: son el último nivel del
                                             árbol y la guía de la izquierda ya
                                             las agrupa. --}}
                                        @forelse($rama['categorias'] as $categoria)
                                        <a href="{{ route($rama['ruta'], $categoria['id']) }}"
                                            class="mobile-nav-cat">
                                            {{ $categoria['nombre'] }}
                                        </a>
                                        @empty
                                        <p class="mobile-nav-vacio">Todavía no hay productos publicados.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
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
            $footerWa = whatsapp_numero();
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

    {{-- La ficha del producto a medida queda afuera a propósito: ya tiene su
         propia barra fija de consulta y los dos botones se pisarían. --}}
    @if(request()->routeIs('welcome', 'nosotros', 'productos.todos', 'productos.categoria', 'productos.especiales'))
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
