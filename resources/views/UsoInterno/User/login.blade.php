<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesion</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="{{ asset('css/interno.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body class="internal-body">
    @include('layouts.partials.toast')
    <div class="auth-shell">
        <main class="flex-grow-1 d-flex align-items-center py-5">
            <div class="container">
                <div class="row justify-content-center align-items-stretch g-4">
                    <div class="col-12 col-lg-6">
                        <div class="auth-visual h-100">
                            <div>
                                <small class="d-block mb-3">Filosofia de diseno</small>
                                <h2 class="h3 fw-bold mb-3">Arquitectura y precision</h2>
                                <p class="mb-0 text-white-50">Una experiencia interna clara, simple y segura para gestionar tu negocio.</p>
                            </div>
                            <div class="auth-cta">
                                Donde la robustez se encuentra con el detalle y el orden.
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="auth-panel h-100 p-4 p-lg-5">
                            <div class="mb-4">
                                <p class="text-uppercase small text-success-emphasis mb-2">Acceso interno</p>
                                <h1 class="h4 fw-bold text-success mb-1">Bienvenido</h1>
                                <p class="text-secondary mb-0">Ingresa tus credenciales para continuar.</p>
                            </div>

                            <form method="POST" action="{{ route('login') }}" class="d-grid gap-3">
                                @csrf
                                <div>
                                    <label for="email" class="form-label fw-semibold">Correo electronico</label>
                                    <input type="email" name="email" id="email" class="form-control" required
                                        placeholder="ejemplo@dominio.com">
                                </div>
                                <div>
                                    <label for="password" class="form-label fw-semibold">Contrasena</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control" required
                                            placeholder="Ingresa tu contrasena">
                                        <button class="btn btn-outline-secondary password-toggle" type="button" data-toggle="password"
                                            data-target="password" aria-label="Mostrar contrasena">
                                            <span data-eye="open">
                                                <x-fluentui-eye-20-o />
                                            </span>
                                            <span data-eye="closed" class="d-none">
                                                <x-fluentui-eye-off-20-o />
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="{{ route('change-password-view') }}" class="text-success text-decoration-none small">¿Olvidaste tu contrasena?</a>
                                </div>
                                <button type="submit" class="btn btn-success w-100 fw-semibold">
                                    Iniciar sesion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="internal-footer">
            <div class="container py-3">
                <div class="internal-footer-bottom border-0 p-0">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                        <span>© {{ date('Y') }} Giacomazzi Glass</span>
                        <span>Interfaz interna para gestion administrativa</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="{{ asset('js/toast.js') }}"></script>

    <script src="{{ asset('js/showPassword.js') }}"></script>
</body>

</html>