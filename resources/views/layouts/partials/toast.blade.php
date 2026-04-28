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