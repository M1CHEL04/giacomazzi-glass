@extends('layouts.app-interno')
@section('title', $producto->nombre . ' · Panel interno · Giacomazzi Glass')
@section('page-title', $producto->nombre)
@section('subhead', 'Detalle del producto · ' . ($producto->categoria?->nombre ?? 'Sin categoría'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/producto.css') }}">
<style>
.sku-grid {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
    max-height: 168px;
    overflow-y: auto;
}
.sku-chip {
    font-size: 11px;
    color: #287452;
    background: rgba(40, 116, 82, .07);
    border-radius: 4px;
    padding: 3px 8px;
    white-space: nowrap;
    letter-spacing: .03em;
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

                @if($imagenes->isNotEmpty())
                <div class="prod-photos-grid">
                    @foreach($imagenes->sortByDesc('es_principal') as $img)
                    <div class="prod-photo-wrap{{ $img->es_principal ? ' is-portada' : '' }}">
                        <img src="{{ $img->ruta }}"
                            alt="{{ $img->nombre_imagen }}"
                            class="prod-photo-thumb"
                            data-src="{{ $img->ruta }}"
                            data-alt="{{ $img->nombre_imagen }}"
                            onclick="openLightbox(this.dataset.src, this.dataset.alt)">
                        @if($img->es_principal)
                        <span class="portada-badge">
                            <x-heroicon-s-star style="width:11px;height:11px;" />
                        </span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-secondary mb-0" style="font-size:13px;">Sin imágenes cargadas.</p>
                @endif
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
                        <span class="variante-values">{{ $valores->pluck('valor')->implode(' · ') }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-secondary mb-0" style="font-size:13px;">Sin variantes asociadas.</p>
                @endif
            </div>

            {{-- SKUs / Combinaciones --}}
            <div class="info-section">
                <div class="info-section-title">
                    <x-heroicon-m-rectangle-stack style="width:14px;height:14px;" />
                    Combinaciones de SKU
                    @if($producto->variantes->isNotEmpty())
                    <span class="badge bg-success-subtle text-success ms-auto"
                        style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:0;">
                        {{ $producto->variantes->count() }}
                    </span>
                    @endif
                </div>

                @if($producto->variantes->isNotEmpty())
                <div class="sku-grid">
                    @foreach($producto->variantes->sortBy('sku') as $pv)
                    <code class="sku-chip">{{ $pv->sku }}</code>
                    @endforeach
                </div>
                @else
                <p class="text-secondary mb-0" style="font-size:13px;">Sin combinaciones de SKU generadas.</p>
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