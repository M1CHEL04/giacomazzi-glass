<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cambiar contraseña</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="{{ asset('css/interno.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('css/changePassword.css') }}">

</head>

<body class="internal-body d-flex flex-column min-vh-100">
    @include('layouts.partials.toast')
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4 p-lg-5">
                            <div class="mb-4">
                                <h1 class="h4 fw-bold text-success mb-1">Cambiar contraseña</h1>
                                <p class="text-secondary mb-0">Actualiza tu acceso con una clave segura.</p>
                            </div>

                            <form method="POST" action="{{ route('change-password') }}" class="d-grid gap-3">
                                @csrf
                                <div>
                                    <label for="current_password" class="form-label fw-semibold">Contraseña actual</label>
                                    <div class="input-group">
                                        <input type="password" name="current_password" id="current_password"
                                            class="form-control" required autocomplete="current-password"
                                            placeholder="Ingrese la contraseña actual">
                                        <button class="btn btn-outline-secondary password-toggle" type="button" data-toggle="password"
                                            data-target="current_password" aria-label="Mostrar contraseña">
                                            <span data-eye="open">
                                                <x-fluentui-eye-20-o />
                                            </span>
                                            <span data-eye="closed" class="d-none">
                                                <x-fluentui-eye-off-20-o />
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label for="new_password" class="form-label fw-semibold">Nueva contraseña</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="new_password"
                                            class="form-control" required autocomplete="new-password"
                                            placeholder="Ingrese la nueva contraseña">
                                        <button class="btn btn-outline-secondary password-toggle" type="button" data-toggle="password"
                                            data-target="new_password" aria-label="Mostrar contraseña">
                                            <span data-eye="open">
                                                <x-fluentui-eye-20-o />
                                            </span>
                                            <span data-eye="closed" class="d-none">
                                                <x-fluentui-eye-off-20-o />
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label for="new_password_confirmation" class="form-label fw-semibold">Confirmar nueva contraseña</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password_confirmation"
                                            id="new_password_confirmation" class="form-control" required
                                            autocomplete="new-password"
                                            placeholder="Repite la nueva contraseña">
                                        <button class="btn btn-outline-secondary password-toggle" type="button" data-toggle="password"
                                            data-target="new_password_confirmation" aria-label="Mostrar contraseña">
                                            <span data-eye="open">
                                                <x-fluentui-eye-20-o />
                                            </span>
                                            <span data-eye="closed" class="d-none">
                                                <x-fluentui-eye-off-20-o />
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success w-100 fw-semibold">
                                    Guardar cambios
                                </button>
                            </form>
                        </div>
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
                    <span>Interfaz interna para gestión administrativa</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script src="{{ asset('js/showPassword.js') }}"></script>
    <script src="{{ asset('js/toast.js') }}"></script>
</body>

</html>