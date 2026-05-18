@extends('layouts.app-interno')
@section('title', $producto->nombre . ' · Panel interno · Giacomazzi Glass')
@section('page-title', $producto->nombre)
@section('subhead', 'Detalle del producto · ' . ($producto->categoria?->nombre ?? 'Sin categoría'))

@section('css')
<style>
    /* ── Galería compacta ─────────────────────────────────────── */
    .prod-photos-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.45rem;
    }

    .prod-photo-thumb {
        aspect-ratio: 1;
        object-fit: cover;
        border-radius: 0.5rem;
        border: 1.5px solid var(--internal-border);
        cursor: zoom-in;
        width: 100%;
        display: block;
        transition: opacity .15s, border-color .15s, transform .12s;
    }

    .prod-photo-thumb:hover {
        opacity: .82;
        border-color: var(--internal-primary-soft);
        transform: scale(1.03);
    }

    /* ── Estado vacío de imágenes ─────────────────────────────── */
    .prod-photos-empty {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.55rem 0.75rem;
        border-radius: 0.5rem;
        background: rgba(63, 63, 63, 0.04);
        color: var(--internal-secondary-soft);
        font-size: 13px;
    }

    .prod-photos-empty svg {
        opacity: .32;
        flex-shrink: 0;
    }

    /* ── Secciones de info ────────────────────────────────────── */
    .info-section {
        border: 1px solid var(--internal-border);
        border-radius: 0.75rem;
        background: var(--internal-surface);
        padding: 1rem 1.1rem;
    }

    .info-section-title {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--internal-secondary-soft);
        margin-bottom: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .info-section-title svg {
        color: var(--internal-primary);
        flex-shrink: 0;
    }

    .info-field-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--internal-secondary-soft);
        margin-bottom: 0.2rem;
    }

    /* ── Tabla de variantes ───────────────────────────────────── */
    .variante-row {
        display: grid;
        grid-template-columns: 105px 1fr;
        gap: 0.75rem;
        align-items: baseline;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--internal-border);
    }

    .variante-row:first-child {
        padding-top: 0;
    }

    .variante-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .variante-name {
        font-size: 12px;
        font-weight: 700;
        color: var(--internal-secondary);
    }

    .variante-values {
        font-size: 13px;
        color: var(--internal-secondary);
        line-height: 1.5;
    }

    .variante-sep {
        color: var(--internal-secondary-soft);
        margin: 0 0.25rem;
        font-size: 9px;
        vertical-align: middle;
    }

    /* ── Lightbox ─────────────────────────────────────────────── */
    #imgLightbox .modal-content {
        background: rgba(15, 20, 15, 0.88);
        backdrop-filter: blur(6px);
    }

    #imgLightbox .btn-close {
        filter: invert(1);
        opacity: .75;
    }

    #imgLightbox .btn-close:hover {
        opacity: 1;
    }
</style>
@endsection

@section('content')
@php
$varianteGroups = $producto->valoresVariantes
->groupBy(fn($vv) => $vv->variante?->nombre ?? 'Sin variante')
->sortKeys();

$imagenes = $producto->imagenes->where('activa', true)->values();
@endphp

<div class="d-flex flex-column gap-3">

    {{-- ── Barra de acción ──────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <a href="{{ route('uso-interno.productos.index') }}"
            class="d-inline-flex align-items-center gap-1 text-secondary text-decoration-none small fw-semibold">
            <x-heroicon-m-arrow-left style="width:15px;height:15px;" />
            Volver a productos
        </a>

        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill {{ $producto->activo ? 'text-success bg-success-subtle' : 'text-danger bg-danger-subtle' }}"
                style="font-size:11px; padding:3px 12px; font-weight:600;">
                {{ $producto->activo ? 'Activo' : 'Inactivo' }}
            </span>
            <a href="{{ route('uso-interno.productos.edit', $producto) }}"
                class="btn btn-outline-success btn-sm px-2 d-inline-flex align-items-center gap-1"
                style="font-size:12px; padding-top:3px; padding-bottom:3px;">
                <x-heroicon-m-pencil-square style="width:13px;height:13px;" />
                Editar
            </a>
        </div>
    </div>

    {{-- ── Cuerpo principal ──────────────────────────────────────── --}}
    <div class="row g-3 align-items-start">

        {{-- Columna izquierda: galería ──────────────────────────── --}}
        <div class="col-lg-4">
            <div class="info-section">
                <div class="info-section-title">
                    <x-heroicon-m-photo style="width:14px;height:14px;" />
                    Imágenes
                </div>

                @php
                $demoImgs = [
                ['src' => asset('images/homehero.jpg'), 'alt' => 'Vista frontal'],
                ['src' => asset('images/contactohero.jpg'),'alt' => 'Detalle lateral'],
                ['src' => asset('images/homehero.jpg'), 'alt' => 'Vista trasera'],
                ['src' => asset('images/contactohero.jpg'),'alt' => 'Detalle interior'],
                ['src' => asset('images/homehero.jpg'), 'alt' => 'Medidas'],
                ];
                @endphp
                <div class="prod-photos-grid">
                    @foreach($demoImgs as $demo)
                    <img src="{{ $demo['src'] }}"
                        alt="{{ $demo['alt'] }}"
                        class="prod-photo-thumb"
                        data-src="{{ $demo['src'] }}"
                        data-alt="{{ $demo['alt'] }}"
                        onclick="openLightbox(this.dataset.src, this.dataset.alt)">
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Columna derecha: información ───────────────────────── --}}
        <div class="col-lg-8 d-flex flex-column gap-3">

            {{-- Información general --}}
            <div class="info-section">
                <div class="info-section-title">
                    <x-heroicon-m-information-circle style="width:14px;height:14px;" />
                    Información general
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="info-field-label">Código</div>
                        <span class="badge bg-secondary-subtle text-secondary rounded-1"
                            style="font-size:12px; font-weight:600; padding:4px 10px;">
                            {{ $producto->codigo ?? '—' }}
                        </span>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-field-label">Categoría</div>
                        <span class="badge rounded-pill text-primary bg-primary-subtle"
                            style="font-size:11px; padding:3px 12px; font-weight:600;">
                            {{ $producto->categoria?->nombre ?? '—' }}
                        </span>
                    </div>
                    <div class="col-12">
                        <div class="info-field-label">Descripción</div>
                        <p class="mb-0 text-secondary" style="font-size:14px; line-height:1.65;">
                            {{ $producto->descripcion ?? '—' }}
                        </p>
                    </div>
                    @if($producto->descripcion_tecnica)
                    <div class="col-12">
                        <div class="info-field-label">Descripción técnica</div>
                        <p class="mb-0 text-secondary" style="font-size:13px; line-height:1.65;">
                            {{ $producto->descripcion_tecnica }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Variantes --}}
            <div class="info-section">
                <div class="info-section-title">
                    <x-heroicon-m-tag style="width:14px;height:14px;" />
                    Variantes asociadas
                </div>

                @if($varianteGroups->isNotEmpty())
                <div>
                    @foreach($varianteGroups as $nombre => $valores)
                    <div class="variante-row">
                        <span class="variante-name">{{ $nombre }}</span>
                        <div class="variante-values">
                            @foreach($valores as $vv)
                            {{ $vv->valor }}@if(!$loop->last)<span class="variante-sep">&#9632;</span>@endif
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-secondary mb-0" style="font-size:13px;">Sin variantes asociadas.</p>
                @endif
            </div>

            {{-- Metadatos --}}
            <div class="info-section">
                <div class="info-section-title">
                    <x-heroicon-m-clock style="width:14px;height:14px;" />
                    Registro
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="info-field-label">Creado el</div>
                        <div style="font-size:13px;" class="text-secondary">
                            {{ $producto->created_at?->format('d/m/Y · H:i') ?? '—' }}
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-field-label">Última modificación</div>
                        <div style="font-size:13px;" class="text-secondary">
                            {{ $producto->updated_at?->format('d/m/Y · H:i') ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /col-8 --}}
    </div>{{-- /row --}}

</div>

{{-- ── Lightbox ──────────────────────────────────────────────── --}}
<div class="modal fade" id="imgLightbox" tabindex="-1" aria-label="Imagen ampliada" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-body p-2 text-center position-relative">
                <button type="button"
                    class="btn-close position-absolute"
                    style="top:.65rem; right:.65rem; z-index:5;"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
                <img id="lightbox-img" src="" alt=""
                    style="max-height:80vh; max-width:100%; object-fit:contain; border-radius:.5rem;">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function openLightbox(src, alt) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox-img').alt = alt || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('imgLightbox')).show();
    }
</script>
@endsection