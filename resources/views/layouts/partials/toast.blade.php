@if (session('success') || session('error') || $errors->any())
<div class="internal-toast-stack" aria-live="polite" aria-atomic="true">
    @if (session('success'))
    <div class="internal-toast internal-toast-success" role="status" data-toast>
        <span class="internal-toast-icon" aria-hidden="true">
            <x-fluentui-checkmark-20-o />
        </span>
        <div>
            <div class="internal-toast-message">{{ session('success') }}</div>
        </div>
        <button class="internal-toast-close" type="button" aria-label="Cerrar" data-toast-close>
            <x-fluentui-dismiss-20-o />
        </button>
    </div>
    @endif

    @if (session('error'))
    <div class="internal-toast internal-toast-error" role="alert" data-toast>
        <span class="internal-toast-icon" aria-hidden="true">
            <x-fluentui-warning-20-o />
        </span>
        <div>
            <div class="internal-toast-message">{{ session('error') }}</div>
        </div>
        <button class="internal-toast-close" type="button" aria-label="Cerrar" data-toast-close>
            <x-fluentui-dismiss-20-o />
        </button>
    </div>
    @endif

    @if ($errors->any())
    @foreach ($errors->all() as $error)
    <div class="internal-toast internal-toast-error" role="alert" data-toast>
        <span class="internal-toast-icon" aria-hidden="true">
            <x-fluentui-warning-20-o />
        </span>
        <div>
            <div class="internal-toast-message">{{ $error }}</div>
        </div>
        <button class="internal-toast-close" type="button" aria-label="Cerrar" data-toast-close>
            <x-fluentui-dismiss-20-o />
        </button>
    </div>
    @endforeach
    @endif
</div>
@endif

{{--
    Moldes de los toasts que arma el JS (window.showToast).
    ─────────────────────────────────────────────────────────────
    Mismo markup que los de arriba: si cambia el icono o la estructura de uno,
    hay que tocar los dos. Están en este archivo justamente para que esa
    duplicación quede a la vista.

    Van fuera del @if: el stack de sesión aparece sólo cuando hay un flash,
    pero el toast por JS puede salir en cualquier momento. Sin los moldes,
    toast.js cae a su fallback y dibuja el texto "OK" dentro del cuadro del
    icono, que está pensado para un SVG de 16px.
--}}
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