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

    <link rel="stylesheet" href="{{ versioned_asset('css/interno.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/toast.css') }}">

    @yield('css')
</head>

<body class="internal-body d-flex flex-column min-vh-100">
    @php
    $fullName = trim(session('user_name', 'Usuario'));
    $nameParts = preg_split('/\s+/', $fullName);
    $userName = $nameParts[0] ?? 'Usuario';
    $userInitial = mb_substr($userName, 0, 1);
    @endphp

    <header class="internal-topbar">
        <div class="container-fluid py-3 px-3 px-xl-4">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <a href="{{ route('uso-interno.home-interno') }}" class="internal-brand" aria-label="Inicio del panel interno">
                    <span class="internal-brand-mark">
                        <i class="bi bi-building-fill"></i>
                    </span>
                    <span class="internal-brand-text">
                        <span class="internal-brand-name">Giacomazzi Glass</span>
                        <span class="internal-brand-subtitle">Panel administrativo</span>
                    </span>
                </a>

                <div class="dropdown internal-user-dropdown">
                    <button class="internal-user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <span class="internal-user-avatar" aria-hidden="true">{{ $userInitial }}</span>
                        <span>
                            <span class="internal-user-label">Sesión actual</span>
                            <span class="internal-user-name">{{ $userName }}</span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end internal-user-menu">
                        <li>
                            <a class="dropdown-item internal-user-item" href="{{ route('uso-interno.profile') }}">Mi perfil</a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item internal-user-item">
                                    Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    @include('layouts.partials.toast')

    <div class="container-fluid flex-grow-1 px-3 px-xl-4">
        <div class="internal-layout">
            <aside class="internal-sidebar" aria-label="Menú lateral">
                <div class="internal-sidebar-header">
                    <div class="fw-semibold text-uppercase small text-success-emphasis">Categorías</div>
                    <div class="text-secondary small">Gestión principal del sistema</div>
                </div>

                <div class="internal-sidebar-menu">
                    <a href="{{route('uso-interno.productos.index')}}"
                        class="internal-sidebar-link {{ request()->routeIs('home-interno') ? 'active' : '' }}">
                        <i class="bi bi-window-stack"></i>
                        <span>Productos</span>
                    </a>
                    <a href="{{ route('uso-interno.categorias.index') }}"
                        class="internal-sidebar-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap"></i>
                        <span>Categorías</span>
                    </a>
                    <a href="#" class="internal-sidebar-link {{ request()->routeIs('variantes.*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam"></i>
                        <span>Variantes</span>
                    </a>
                </div>
            </aside>

            <main class="internal-main-panel">
                <div class="internal-page-header">
                    <div>
                        <h1 class="internal-page-title">@yield('page-title', 'Panel interno')</h1>
                        <div class="internal-page-breadcrumb">@yield('subhead', 'Administración general')</div>
                    </div>
                </div>

                <section class="internal-content-card">
                    @yield('content')
                </section>
            </main>
        </div>
    </div>

    <footer class="internal-footer mt-auto">

        <div class="container-fluid px-3 px-xl-4 internal-footer-bottom">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                <span>© {{ date('Y') }} Giacomazzi Glass</span>
                <span>Interfaz interna para gestión administrativa</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="{{ versioned_asset('js/toast.js') }}"></script>

    @yield('script')
</body>

</html>