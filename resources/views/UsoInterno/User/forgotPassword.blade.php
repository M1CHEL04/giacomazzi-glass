<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Recuperar acceso</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('css/interno.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('css/forgotPassword.css') }}">
</head>

<body class="internal-body">
    @include('layouts.partials.toast')

    <template id="toast-success-template">
        <div class="internal-toast internal-toast-success" role="status" data-toast>
            <span class="internal-toast-icon" aria-hidden="true">
                <x-fluentui-checkmark-20-o />
            </span>
            <div>
                <div class="internal-toast-message" data-toast-message></div>
            </div>
            <button class="internal-toast-close" type="button" aria-label="Cerrar" data-toast-close>
                <x-fluentui-dismiss-20-o />
            </button>
        </div>
    </template>
    <template id="toast-error-template">
        <div class="internal-toast internal-toast-error" role="alert" data-toast>
            <span class="internal-toast-icon" aria-hidden="true">
                <x-fluentui-warning-20-o />
            </span>
            <div>
                <div class="internal-toast-message" data-toast-message></div>
            </div>
            <button class="internal-toast-close" type="button" aria-label="Cerrar" data-toast-close>
                <x-fluentui-dismiss-20-o />
            </button>
        </div>
    </template>

    <div class="auth-shell">
        <main class="flex-grow-1 d-flex align-items-center py-5">
            <div class="container">
                <div class="row justify-content-center align-items-stretch g-4">
                    <div class="col-12 col-lg-6">
                        <div class="auth-visual h-100">
                            <div>
                                <small class="d-block mb-3">Recuperación de acceso</small>
                                <h2 class="h3 fw-bold mb-3">Vuelve a tu panel</h2>
                                <p class="mb-0 text-white-50">Recupera tu acceso en tres pasos simples y seguros.</p>
                            </div>
                            <div class="auth-cta">
                                Seguridad moderna con procesos claros.
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="auth-panel h-100 p-4 p-lg-5">
                            <div class="mb-4">
                                <p class="text-uppercase small text-success-emphasis mb-2">Acceso seguro</p>
                                <h1 class="h4 fw-bold text-success mb-1">Recuperar contraseña</h1>
                                <p class="text-secondary mb-0">Confirma tu identidad para continuar.</p>
                            </div>

                            <div class="auth-stepper" aria-label="Progreso de recuperación">
                                <div class="auth-step is-active" data-step-indicator="1">
                                    <span class="auth-step-index">1</span>
                                    <span>Email</span>
                                </div>
                                <div class="auth-step" data-step-indicator="2">
                                    <span class="auth-step-index">2</span>
                                    <span>Código</span>
                                </div>
                                <div class="auth-step" data-step-indicator="3">
                                    <span class="auth-step-index">3</span>
                                    <span>Nueva contraseña</span>
                                </div>
                            </div>

                            <form id="request-code-form" class="auth-section" data-url="{{ route('send-verify-code') }}">
                                @csrf
                                <div>
                                    <label for="email" class="form-label fw-semibold">Correo electronico</label>
                                    <input type="email" name="email" id="email" class="form-control" required
                                        placeholder="ejemplo@dominio.com">
                                </div>
                                <div class="auth-helper">Te enviaremos un código de verificación.</div>
                                <div class="auth-action">
                                    <button type="submit" class="btn btn-sm btn-success fw-semibold" data-submit>
                                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" data-spinner></span>
                                        Enviar código
                                    </button>
                                    <a href="{{ route('login-view') }}" class="auth-muted-link">Volver al login</a>
                                </div>
                            </form>

                            <form id="verify-code-form" class="auth-section is-hidden" data-url="{{ route('verify-code') }}">
                                @csrf
                                <div>
                                    <label for="verification_code" class="form-label fw-semibold">Código de verificación</label>
                                    <input type="text" name="verification_code" id="verification_code" class="form-control" required
                                        placeholder="Ingresa el código recibido">
                                </div>
                                <div class="auth-helper">El código expira en pocos minutos.</div>
                                <div class="auth-action">
                                    <button type="submit" class="btn btn-sm btn-success fw-semibold" data-submit>
                                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" data-spinner></span>
                                        Verificar código
                                    </button>
                                </div>
                            </form>

                            <form id="reset-password-form" class="auth-section is-hidden" data-url="{{ route('change-password-after-code') }}">
                                @csrf
                                <div>
                                    <label for="new_password" class="form-label fw-semibold">Nueva contraseña</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="new_password" class="form-control" required
                                            placeholder="Ingresa tu nueva contraseña">
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
                                    <label for="new_password_confirmation" class="form-label fw-semibold">Confirmar contraseña</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required
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
                                <div class="auth-action">
                                    <button type="submit" class="btn btn-sm btn-success fw-semibold" data-submit>
                                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" data-spinner></span>
                                        Guardar nueva contraseña
                                    </button>
                                </div>
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
                        <span>Interfaz interna para gestión administrativa</span>
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
    <script src="{{ asset('js/forgotPassword.js') }}"></script>
</body>

</html>