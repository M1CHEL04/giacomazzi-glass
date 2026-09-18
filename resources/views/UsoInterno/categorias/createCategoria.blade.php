@extends('layouts.app-interno')
@php
$isEdit = isset($categoria);
$formAction = $isEdit ? route('uso-interno.categorias.update', $categoria) : route('uso-interno.categorias.store');

$recuadrosHero = \App\Models\Categoria::RECUADROS_HERO;
$heroActual = $isEdit && $categoria->imagen_hero ? imagen_src($categoria->imagen_hero, 1400) : null;

// El editor muestra una variante liviana, pero la cota tiene que informar los
// píxeles que el hero va a servir de verdad — que salen de la copia más grande.
$heroMedidas = $heroActual ? imagen_medidas($categoria->imagen_hero) : null;

// El encuadre guardado alimenta los marcos al abrir la pantalla; si la
// validación rebotó el formulario mandan los old() para no perder el ajuste.
$encuadreHero = [];
foreach (array_keys($recuadrosHero) as $clave) {
    $guardado = $isEdit ? $categoria->encuadreHero($clave) : \App\Models\Categoria::ENCUADRE_DEFECTO;

    $encuadreHero[$clave] = [
        'x' => (float) old("hero_{$clave}_x", $guardado['x']),
        'y' => (float) old("hero_{$clave}_y", $guardado['y']),
        'zoom' => (float) old("hero_{$clave}_zoom", $guardado['zoom']),
    ];
}
@endphp

@section('title', 'Panel Interno - Aberturas Giacomazzi')
@section('page-title', $isEdit ? 'Editar categoria' : 'Crear categoria')
@section('subhead', 'Completa el nombre para registrar la categoria.')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/hero-encuadre.css') }}">
@endsection

@section('content')
<style>
    .form-check-input:checked {
        background-color: #287452;
        border-color: #287452;
    }

    .form-check-input:focus {
        border-color: #287452;
        box-shadow: 0 0 0 0.2rem rgba(40, 116, 82, 0.25);
    }
</style>

<div class="d-flex flex-column gap-3">
    <div>
        <a href="{{ route('uso-interno.categorias.index') }}" class="btn btn-outline-secondary btn-sm px-2 py-1 rounded-2 d-inline-flex align-items-center text-decoration-none" style="font-size: 13px;">
            <x-fluentui-arrow-left-20-o class="me-1" style="width:14px;height:14px;" />
            Volver
        </a>
    </div>

    <div class="border rounded-3 bg-white p-3">
        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="d-flex flex-column gap-3">
            @csrf

            <div class="row g-3">
                <div class="col-12">
                    <label for="nombre" class="form-label small mb-1">Nombre de la categoría</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="form-control form-control-sm py-2 rounded-2 @error('nombre') is-invalid @enderror"
                        placeholder="Ej: Mamparas"
                        value="{{ old('nombre', $categoria->nombre ?? '') }}"
                        autocomplete="off"
                        required>
                    @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if ($isEdit)
                <div class="col-12">
                    <input type="hidden" name="activo" value="0">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            class="form-check-input @error('activo') is-invalid @enderror"
                            id="activo"
                            name="activo"
                            value="1"
                            {{ old('activo', $categoria->activo ?? 0) == 1 ? 'checked' : '' }}>
                        <label class="form-check-label small" for="activo">
                            Categoría activa
                        </label>
                        @error('activo')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @if (($categoria->productos_activos_count ?? 0) > 0)
                    <div class="form-text small">
                        Al dar de baja la categoría también se dan de baja sus
                        {{ $categoria->productos_activos_count }}
                        {{ $categoria->productos_activos_count === 1 ? 'producto activo' : 'productos activos' }}.
                        Volver a activarla no los reactiva: hay que hacerlo producto por producto.
                    </div>
                    @endif
                </div>
                @endif

                {{-- Imagen hero --}}
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small mb-0">Imagen de portada</label>
                        <div class="d-flex gap-2">
                            <button type="button" id="btn-quitar-hero"
                                class="btn btn-outline-danger btn-sm px-2 py-1 d-inline-flex align-items-center rounded-2"
                                style="font-size:12px;{{ $heroActual ? '' : 'display:none;' }}">
                                <i class="bi bi-trash me-1" style="font-size:11px;"></i>
                                Quitar
                            </button>
                            <button type="button" id="btn-seleccionar-hero"
                                class="btn btn-outline-secondary btn-sm px-2 py-1 d-inline-flex align-items-center rounded-2"
                                style="font-size:12px;">
                                <i class="bi bi-image me-1" style="font-size:11px;"></i>
                                {{ $heroActual ? 'Cambiar imagen' : 'Seleccionar imagen' }}
                            </button>
                        </div>
                    </div>

                    <input type="file"
                        id="imagen_hero"
                        name="imagen_hero"
                        accept="image/*"
                        class="d-none @error('imagen_hero') is-invalid @enderror">
                    @if($isEdit)
                    <input type="hidden" name="eliminar_imagen_hero" id="eliminar-imagen-hero-input" value="0">
                    @endif

                    <p class="text-muted mb-2" style="font-size:11px;">
                        Subí la foto en grande &mdash; <strong>2400 px de ancho o más</strong>, JPG o PNG, máx. 4 MB.
                        No hay una medida que entre exacta: el hero es casi cuadrado en el teléfono
                        (≈1.9:1) y una franja bien apaisada en escritorio (≈4.8:1), así que
                        <strong>siempre se recorta algo</strong>. Una foto apaisada normal (≈3:1) anda bien
                        en los dos; abajo elegís qué parte se ve en cada uno.
                    </p>
                    @error('imagen_hero')
                    <div class="text-danger mb-2" style="font-size:12px;">{{ $message }}</div>
                    @enderror

                    {{-- Editor de encuadre. Oculto hasta que haya una imagen que encuadrar. --}}
                    <div class="hero-encuadre" id="hero-encuadre"
                        @if($heroMedidas)
                        data-hero-origen-ancho="{{ $heroMedidas['ancho'] }}"
                        data-hero-origen-alto="{{ $heroMedidas['alto'] }}"
                        @endif
                        {{ $heroActual ? '' : 'hidden' }}>
                        <p class="hero-encuadre-intro">
                            Arrastrá el recuadro para elegir qué parte de la foto se ve.
                            Lo que queda bajo el velo no se muestra. La cota indica cuántos
                            píxeles de la foto entran en cada uno.
                        </p>

                        <div class="hero-encuadre-marcos">
                            @foreach($recuadrosHero as $clave => $recuadro)
                            <figure class="hero-marco"
                                data-hero-frame="{{ $clave }}"
                                data-hero-nombre="{{ $recuadro['nombre'] }}"
                                data-hero-ratio="{{ $recuadro['ratio'] }}"
                                data-hero-minimo="{{ $recuadro['minimo'] }}">
                                <figcaption class="hero-marco-head">
                                    <span class="hero-marco-nombre">{{ $recuadro['nombre'] }}</span>
                                    <span class="hero-marco-forma">{{ $recuadro['leyenda'] }}</span>
                                </figcaption>

                                <div class="hero-lienzo"
                                    data-hero-lienzo
                                    tabindex="0"
                                    role="application"
                                    aria-label="Encuadre para {{ mb_strtolower($recuadro['nombre']) }}: arrastrá el recuadro o movelo con las flechas del teclado">
                                    {{-- Sin src cuando todavía no hay foto: un src="" vacío
                                         hace que el navegador vuelva a pedir la página. --}}
                                    <img class="hero-lienzo-img" data-hero-img alt=""
                                        @if($heroActual) src="{{ $heroActual }}" @endif
                                        decoding="async">

                                    {{-- El velo sobre lo descartado sale del box-shadow de
                                         este mismo elemento; el JS sólo lo posiciona. --}}
                                    <div class="hero-ventana" data-hero-ventana>
                                        <span class="hero-ventana-scrim"></span>
                                        <span class="hero-ventana-titulo">{{ old('nombre', $categoria->nombre ?? 'Nombre de la categoría') }}</span>
                                        <span class="hero-cota" data-hero-cota aria-hidden="true">
                                            <span class="hero-cota-tope"></span>
                                            <span class="hero-cota-linea"></span>
                                            <span class="hero-cota-medida" data-hero-medida></span>
                                            <span class="hero-cota-linea"></span>
                                            <span class="hero-cota-tope"></span>
                                        </span>
                                    </div>
                                </div>

                                <div class="hero-marco-controles">
                                    <button type="button" class="hero-paso" data-hero-zoom-menos
                                        aria-label="Alejar">&minus;</button>
                                    <input type="range" class="hero-zoom"
                                        id="hero-zoom-{{ $clave }}"
                                        data-hero-zoom
                                        min="1" max="3" step="0.01"
                                        aria-label="Zoom para {{ mb_strtolower($recuadro['nombre']) }}"
                                        value="{{ $encuadreHero[$clave]['zoom'] }}">
                                    <button type="button" class="hero-paso" data-hero-zoom-mas
                                        aria-label="Acercar">+</button>
                                    <span class="hero-zoom-valor" data-hero-zoom-valor></span>
                                    <button type="button" class="hero-btn-texto" data-hero-centrar>Centrar</button>
                                </div>

                                <input type="hidden" name="hero_{{ $clave }}_x" data-hero-input="x" value="{{ $encuadreHero[$clave]['x'] }}">
                                <input type="hidden" name="hero_{{ $clave }}_y" data-hero-input="y" value="{{ $encuadreHero[$clave]['y'] }}">
                                <input type="hidden" name="hero_{{ $clave }}_zoom" data-hero-input="zoom" value="{{ $encuadreHero[$clave]['zoom'] }}">
                            </figure>
                            @endforeach
                        </div>

                        <p class="hero-encuadre-nota" id="hero-resolucion"></p>
                    </div>
                </div>
            </div>{{-- /row --}}

            <div class="d-flex justify-content-end pt-2 border-top">
                <button type="submit" class="btn btn-success btn-sm px-3 py-1 rounded-2" style="font-size: 13px;">
                    {{ $isEdit ? 'Guardar' : 'Crear' }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script src="{{ versioned_asset('js/modules/hero-imagen.js') }}"></script>
@endsection