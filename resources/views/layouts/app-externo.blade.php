<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Giacomazzi Cotizador'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('css/externo.css') }}">

    @yield('css')
</head>

<body class="external-body d-flex flex-column min-vh-100">
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
                            <a class="nav-link external-menu-btn" href="#">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link external-menu-btn" href="#">Productos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link external-menu-btn" href="#">Contacto</a>
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
                    <div class="external-contact-block mb-2">
                        <strong>Sucursal 1</strong><br>
                        Dirección: Calle Ejemplo 123<br>
                        Tel: +54 9 11 1234-5678
                    </div>
                    <div class="external-contact-block">
                        <strong>Sucursal 2</strong><br>
                        Dirección: Avenida Ejemplo 456<br>
                        Tel: +54 9 11 8765-4321
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
                    <a href="#" class="external-social-link" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    @yield('script')
</body>

</html>