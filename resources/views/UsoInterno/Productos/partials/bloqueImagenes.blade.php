@php
$nota = $nota ?? null;
$descripcion = $descripcion ?? null;
$ordenable = $ordenable ?? false;
$campoEliminar = $campo . '_eliminar';
@endphp

<div class="border rounded-3 bg-white p-3" id="{{ $prefijo }}-bloque">

    <div class="d-flex align-items-center justify-content-between {{ $descripcion ? 'mb-1' : 'mb-3' }}">
        <p class="text-uppercase fw-semibold text-secondary mb-0" style="font-size:11px;letter-spacing:.06em;">
            {{ $titulo }}
            @if ($nota)
            <span class="text-muted fw-normal text-lowercase">({{ $nota }})</span>
            @endif
        </p>
        <span class="imagenes-contador" id="{{ $prefijo }}-contador" aria-live="polite"></span>
    </div>

    @if ($descripcion)
    <p class="text-secondary mb-3" style="font-size:12px;">{{ $descripcion }}</p>
    @endif

    <label class="imagen-dropzone-grande" id="{{ $prefijo }}-dropzone">
        <input type="file" id="{{ $prefijo }}-picker" class="imagen-file-picker"
            accept="image/jpeg,image/png,image/webp" multiple>

        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" aria-hidden="true"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 16.5V4.5m0 0L7.5 9M12 4.5L16.5 9M3.75 15.75v2.25a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-2.25" />
        </svg>

        <span class="imagen-dropzone-titulo">Arrastrá imágenes acá o hacé clic para elegirlas</span>
        <span class="imagen-dropzone-detalle">Hasta {{ $maximo }} · JPG, PNG o WebP · 8 MB cada una</span>
    </label>

    <div id="{{ $prefijo }}-container" class="imagenes-galeria-row"
        @if ($ordenable) role="list" aria-describedby="imagenes-orden-ayuda" @endif>
        @foreach ($imagenes as $imagen)
        <div class="imagen-existente-card {{ $ordenable ? 'imagen-card' : '' }}"
            @if ($ordenable) data-imagen-id="{{ $imagen->id }}" role="listitem" tabindex="0" @endif>

            <img src="{{ $imagen->ruta_miniatura }}"
                alt="{{ $imagen->nombre_imagen }}"
                class="imagen-thumb"
                width="100" height="100"
                loading="lazy" decoding="async">

            <div class="imagen-eliminar-overlay">
                <button type="button"
                    class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
                    style="width:20px;height:20px;"
                    data-eliminar title="Eliminar">
                    <x-heroicon-m-x-mark style="width:12px;height:12px;" />
                </button>
            </div>

            @if ($ordenable)
            <button type="button"
                class="imagen-portada-btn {{ $loop->first ? 'activa' : '' }}"
                title="Poner primera (portada)"
                aria-label="Poner primera (portada)">
                @if ($loop->first)
                <x-heroicon-s-star style="width:12px;height:12px;" />
                @else
                <x-heroicon-o-star style="width:12px;height:12px;" />
                @endif
            </button>
            @if ($loop->first)
            <span class="imagen-portada-badge">Portada</span>
            @endif
            @endif

            {{-- Deshabilitado no se envía: el JS sólo alterna `disabled`, así que
                 el id nunca sale del servidor. --}}
            <input type="hidden" name="{{ $campoEliminar }}[]" value="{{ $imagen->id }}"
                data-eliminar-input disabled>
        </div>
        @endforeach
    </div>

    @if ($ordenable)
    <p class="imagenes-orden-ayuda d-none" id="imagenes-orden-ayuda">
        Arrastrá las imágenes para ordenarlas; la primera es la portada.
    </p>
    <div class="visually-hidden" id="imagenes-orden-estado" aria-live="polite"></div>

    <input type="hidden" name="imagenes_orden" id="imagenes-orden" value="{{ old('imagenes_orden') }}">
    <input type="hidden" name="imagen_portada" id="imagen-portada" value="">
    @endif

    @error($campo)
    <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
    @error($campo . '.*')
    <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>