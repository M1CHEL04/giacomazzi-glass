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

    @yield('css')
</head>

<body class="external-body d-flex flex-column min-vh-100">
    @include('layouts.partials.toast')
    <header>
        <nav class="navbar navbar-expand-lg external-navbar py-3">
            <div class="container external-nav-container">
                <a class="navbar-brand external-logo" href="#" aria-label="Logo de la marca">
                    LOGO
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#externalNavbar"
                    aria-controls="externalNavbar" aria-expanded="false" aria-label="Mostrar navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="externalNavbar">
                    <ul class="navbar-nav mx-auto external-menu gap-lg-2">
                        <li class="nav-item">
                            <a class="nav-link external-menu-btn" href="{{ route('welcome') }}">Inicio</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link external-menu-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Productos
                            </a>
                            <ul class="dropdown-menu external-dropdown-menu">
                                @if(isset($categoriasMenu) && $categoriasMenu->count() > 0)
                                @foreach($categoriasMenu as $categoria)
                                <li>
                                    <a class="dropdown-item external-dropdown-item" href="#">
                                        {{ $categoria->nombre }}
                                    </a>
                                </li>
                                @endforeach
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                @endif
                                <li>
                                    <a class="dropdown-item external-dropdown-item fw-semibold" href="#">
                                        Ver todos los productos
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link external-menu-btn" href="{{ route('contacto') }}">Contacto</a>
                        </li>
                    </ul>

                    <div class="external-cart-wrapper d-lg-flex justify-content-end">
                        <a href="#" class="external-cart-link" aria-label="Carrito de compras">
                            <x-fluentui-cart-24-o />
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

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
                        <i class="bi bi-telephone-fill"></i> 011 6445-7059
                    </div>
                    <div class="external-contact-block">
                        <strong>Local al público</strong><br>
                        Au Dr. Ricardo Balbín Km 30 - Local 03B<br>
                        Guillermo Enrique Hudson, Buenos Aires<br>
                    </div>
                </div>

                <div class="col-12 col-lg-4 text-center">
                    <div class="external-footer-logo mx-auto" aria-label="Logo en footer">
                        LOGO
                    </div>
                </div>

                <div class="col-12 col-lg-4 d-flex justify-content-lg-end justify-content-center align-items-center gap-3">
                    <a href="#" class="external-social-link" aria-label="WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                    <a href="https://www.instagram.com/giacomazzi_srl/" target="_blank" class="external-social-link" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="{{ asset('js/toast.js') }}"></script>

    <script>
        // Dropdown con hover en desktop
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownElement = document.querySelector('.dropdown');
            const dropdownToggle = document.querySelector('.dropdown-toggle');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            let hideTimeout;

            if (dropdownElement && window.innerWidth >= 992) {
                dropdownElement.addEventListener('mouseenter', function() {
                    clearTimeout(hideTimeout);
                    dropdownToggle.classList.add('show');
                    dropdownMenu.classList.add('show');
                });

                dropdownElement.addEventListener('mouseleave', function() {
                    hideTimeout = setTimeout(function() {
                        dropdownToggle.classList.remove('show');
                        dropdownMenu.classList.remove('show');
                    }, 150); // Delay de 150ms antes de ocultar
                });
            }

            // Recalcular en resize
            window.addEventListener('resize', function() {
                if (window.innerWidth < 992) {
                    clearTimeout(hideTimeout);
                    dropdownToggle.classList.remove('show');
                    dropdownMenu.classList.remove('show');
                }
            });
        });
    </script>

    @yield('script')
</body>

</html>