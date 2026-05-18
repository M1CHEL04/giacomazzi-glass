@extends('layouts.app-interno')
@php
$isEdit = isset($producto);
$formAction = $isEdit
? route('uso-interno.productos.update', $producto)
: route('uso-interno.productos.store');
@endphp
@section('title', ($isEdit ? 'Editar' : 'Crear') . ' producto - Panel interno - Giacomazzi Glass')
@section('page-title', $isEdit ? 'Editar producto' : 'Crear producto')
@section('subhead', $isEdit ? 'Modificá los datos del producto' : 'Agrega un nuevo producto al sistema')

@section('css')
<style>
    .form-check-input:checked {
        background-color: #287452;
        border-color: #287452;
    }

    .form-check-input:focus {
        border-color: #287452;
        box-shadow: 0 0 0 .2rem rgba(40, 116, 82, .25);
    }

    /* ── Imágenes existentes (edición) ── */
    .imagen-thumb {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .imagen-existente-card {
        position: relative;
        display: inline-block;
    }

    .imagen-eliminar-overlay {
        position: absolute;
        top: 4px;
        right: 4px;
    }

    .imagen-existente-card.marcada-eliminar {
        opacity: .35;
    }

    /* ── Tarjetas de nueva imagen ── */
    .imagen-input-card {
        width: 100px;
        height: 100px;
        border: 2px dashed #ced4da;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        flex-shrink: 0;
        transition: border-color .15s;
    }

    .imagen-dropzone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        cursor: pointer;
        background: #f8f9fa;
        margin: 0;
        transition: background .15s;
    }

    .imagen-dropzone:hover {
        background: #e9ecef;
    }

    .imagen-file-input {
        display: none;
    }

    .imagen-dropzone-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        color: #adb5bd;
        font-size: 11px;
        text-align: center;
        padding: 8px;
        user-select: none;
    }

    .imagen-preview-box {
        position: absolute;
        inset: 0;
        z-index: 1;
    }

    .imagen-preview-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* Botón quitar tarjeta (×, top-right): visible en estado vacío y con preview.
       Ausente en la primera tarjeta. */
    .imagen-remove-card-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(220, 53, 69, .85);
        color: #fff;
        border: none;
        font-size: 13px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        z-index: 3;
        transition: background .1s;
    }

    .imagen-remove-card-btn:hover {
        background: #dc3545;
    }

    /* Botón limpiar imagen (↩, top-left): solo visible cuando hay preview.
       Descarta el archivo y vuelve al estado vacío sin eliminar el input. */
    .imagen-clear-btn {
        position: absolute;
        top: 4px;
        left: 4px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(108, 117, 125, .75);
        color: #fff;
        border: none;
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        z-index: 2;
        transition: background .1s;
    }

    .imagen-clear-btn:hover {
        background: #6c757d;
    }

    /* ── Tags de variante ── */
    .variante-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 3px 6px 3px 8px;
        font-size: 12px;
        line-height: 1.4;
        color: #495057;
    }

    .variante-tag-label {
        font-weight: 600;
        color: #212529;
    }

    .variante-tag-sep {
        color: #adb5bd;
        margin: 0 1px;
    }

    .variante-tag-value {
        color: #495057;
    }

    .variante-tag-remove {
        background: none;
        border: none;
        color: #adb5bd;
        font-size: 15px;
        line-height: 1;
        cursor: pointer;
        padding: 0 0 0 4px;
        display: flex;
        align-items: center;
    }

    .variante-tag-remove:hover {
        color: #dc3545;
    }

    /* Tamaño de iconos SVG dentro de botones de imagen y variante */
    .imagen-remove-card-btn svg,
    .imagen-clear-btn svg {
        width: 12px;
        height: 12px;
        display: block;
    }

    .variante-tag-remove svg {
        width: 13px;
        height: 13px;
        display: block;
    }
</style>
@endsection

@section('content')
<div class="d-flex flex-column gap-3">

    {{-- Volver --}}
    <div>
        <a href="{{ route('uso-interno.productos.index') }}"
            class="btn btn-outline-secondary btn-sm px-2 py-1 rounded-2 d-inline-flex align-items-center text-decoration-none"
            style="font-size:13px;">
            <x-fluentui-arrow-left-20-o class="me-1" style="width:14px;height:14px;" />
            Volver
        </a>
    </div>

    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="d-flex flex-column gap-3">
        @csrf

        {{-- ── DATOS DEL PRODUCTO ── --}}
        <div class="border rounded-3 bg-white p-3">
            <p class="text-uppercase fw-semibold text-secondary mb-3" style="font-size:11px;letter-spacing:.06em;">
                Datos del producto
            </p>
            <div class="row g-3">

                {{-- Nombre --}}
                <div class="col-12 col-md-8">
                    <label for="nombre" class="form-label small mb-1">
                        Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="nombre" name="nombre"
                        class="form-control form-control-sm py-2 rounded-2 @error('nombre') is-invalid @enderror"
                        placeholder="Ej: Mampara de ducha corrediza"
                        value="{{ old('nombre', $producto->nombre ?? '') }}"
                        autocomplete="off" required>
                    @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Código --}}
                <div class="col-12 col-md-4">
                    <label for="codigo" class="form-label small mb-1">
                        Código <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="codigo" name="codigo"
                        class="form-control form-control-sm py-2 rounded-2 @error('codigo') is-invalid @enderror"
                        placeholder="Ej: MDP-001"
                        value="{{ old('codigo', $producto->codigo ?? '') }}"
                        autocomplete="off" required>
                    @error('codigo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Categoría --}}
                <div class="col-12">
                    <label for="categoria_id" class="form-label small mb-1">
                        Categoría <span class="text-danger">*</span>
                    </label>
                    <select id="categoria_id" name="categoria_id"
                        class="form-select form-select-sm py-2 rounded-2 @error('categoria_id') is-invalid @enderror"
                        required>
                        <option value="">Seleccioná una categoría...</option>
                        @foreach ($categorias as $cat)
                        <option value="{{ $cat->id }}"
                            {{ old('categoria_id', $producto->categoria_id ?? '') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nombre }}
                        </option>
                        @endforeach
                    </select>
                    @error('categoria_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div class="col-12">
                    <label for="descripcion" class="form-label small mb-1">
                        Descripción <span class="text-danger">*</span>
                    </label>
                    <textarea id="descripcion" name="descripcion" rows="3"
                        class="form-control form-control-sm py-2 rounded-2 @error('descripcion') is-invalid @enderror"
                        placeholder="Descripción del producto..." required
                        maxlength="255">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
                    @error('descripcion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Descripción técnica --}}
                <div class="col-12">
                    <label for="descripcion_tecnica" class="form-label small mb-1">
                        Descripción técnica
                        <span class="text-secondary fw-normal">(opcional)</span>
                    </label>
                    <textarea id="descripcion_tecnica" name="descripcion_tecnica" rows="3"
                        class="form-control form-control-sm py-2 rounded-2 @error('descripcion_tecnica') is-invalid @enderror"
                        placeholder="Especificaciones técnicas..."
                        maxlength="255">{{ old('descripcion_tecnica', $producto->descripcion_tecnica ?? '') }}</textarea>
                    @error('descripcion_tecnica')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Activo (solo edición) --}}
                @if ($isEdit)
                <div class="col-12">
                    <input type="hidden" name="activo" value="0">
                    <div class="form-check form-switch">
                        <input type="checkbox"
                            class="form-check-input @error('activo') is-invalid @enderror"
                            id="activo" name="activo" value="1"
                            {{ old('activo', $producto->activo ?? 0) == 1 ? 'checked' : '' }}>
                        <label class="form-check-label small" for="activo">Producto activo</label>
                        @error('activo')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                @endif

            </div>
        </div>

        {{-- ── IMÁGENES ── --}}
        <div class="border rounded-3 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <p class="text-uppercase fw-semibold text-secondary mb-0" style="font-size:11px;letter-spacing:.06em;">
                    Imágenes <span class="text-muted fw-normal text-lowercase">(máx. 5)</span>
                </p>
                <button type="button" id="add-imagen-btn"
                    class="btn btn-outline-secondary btn-sm px-2 py-1 d-inline-flex align-items-center rounded-2"
                    style="font-size:12px;">
                    <x-fluentui-add-20-o class="me-1" style="width:12px;height:12px;" />
                    Agregar imagen
                </button>
            </div>

            {{-- Imágenes existentes (edición) --}}
            @if ($isEdit && $producto->imagenes->count() > 0)
            <div class="d-flex flex-wrap gap-3 mb-3">
                @foreach ($producto->imagenes as $imagen)
                <div class="imagen-existente-card" id="imagen-card-{{ $imagen->id }}">
                    <img src="{{ Storage::url($imagen->ruta) }}"
                        alt="{{ $imagen->nombre_imagen }}"
                        class="imagen-thumb">
                    <div class="imagen-eliminar-overlay">
                        <button type="button"
                            class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
                            style="width:20px;height:20px;"
                            onclick="toggleEliminarImagen({{ $imagen->id }}, this)"
                            title="Eliminar">
                            <x-heroicon-m-x-mark style="width:12px;height:12px;" />
                        </button>
                    </div>
                    <input type="hidden" name="imagenes_eliminar[]"
                        id="eliminar-{{ $imagen->id }}" value="" disabled>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Inputs nuevas imágenes --}}
            <div id="imagenes-container" class="d-flex flex-wrap gap-3"></div>

            @error('imagenes.*')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        {{-- ── VARIANTES ── --}}
        <div class="border rounded-3 bg-white p-3">
            <p class="text-uppercase fw-semibold text-secondary mb-3" style="font-size:11px;letter-spacing:.06em;">
                Variantes del producto
            </p>

            <div id="variantes-alert"
                class="alert alert-light border small py-2 mb-0 {{ $isEdit ? 'd-none' : '' }}"
                style="font-size:12px;">
                Seleccioná primero una categoría para cargar las variantes disponibles.
            </div>

            <div id="variantes-section" class="d-flex flex-column gap-3 {{ $isEdit ? '' : 'd-none' }}">

                {{-- Tags seleccionados --}}
                <div id="variantes-lista" class="d-flex flex-wrap gap-2 align-items-center" style="min-height:32px;">
                    <p id="variantes-empty-msg" class="mb-0 fst-italic" style="font-size:12px;color:#adb5bd;">Sin variantes agregadas aún.</p>
                </div>

                {{-- Formulario agregar variante --}}
                <div class="border rounded-2 p-3 bg-light">
                    <p class="small fw-semibold text-secondary mb-2" style="font-size:11px;">Agregar variante al producto</p>
                    <div class="row g-2 align-items-end">

                        {{-- Select variante --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1" style="font-size:11px;">Variante</label>
                            <select id="variante-select" class="form-select form-select-sm">
                                <option value="">Seleccioná una variante...</option>
                            </select>
                        </div>

                        {{-- Select valor (variante existente) --}}
                        <div class="col-12 col-md-4 d-none" id="valor-existing-section">
                            <label class="form-label small mb-1" style="font-size:11px;">Valor</label>
                            <select id="valor-select" class="form-select form-select-sm">
                                <option value="">Seleccioná un valor...</option>
                            </select>
                        </div>

                        {{-- Input nuevo valor (para variante existente) --}}
                        <div class="col-12 col-md-4 d-none" id="nuevo-valor-section">
                            <label class="form-label small mb-1" style="font-size:11px;">Nuevo valor</label>
                            <input type="text" id="nuevo-valor-input"
                                class="form-control form-control-sm"
                                placeholder="Ej: Transparente">
                        </div>

                        {{-- Nueva variante --}}
                        <div class="col-12 d-none" id="nueva-variante-section">
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1" style="font-size:11px;">Nombre de la variante</label>
                                    <input type="text" id="nueva-variante-nombre"
                                        class="form-control form-control-sm"
                                        placeholder="Ej: Material">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small mb-1" style="font-size:11px;">Valor</label>
                                    <input type="text" id="nueva-variante-valor"
                                        class="form-control form-control-sm"
                                        placeholder="Ej: Aluminio">
                                </div>
                            </div>
                        </div>

                        {{-- Botón agregar --}}
                        <div class="col-auto">
                            <button type="button" id="add-variante-btn"
                                class="btn btn-success btn-sm px-3 py-1 rounded-2"
                                style="font-size:12px;">
                                Agregar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden: JSON de variantes --}}
        <input type="hidden" name="variantes_json" id="variantes-json"
            value="{{ old('variantes_json', '[]') }}">

        {{-- Submit --}}
        <div class="d-flex justify-content-end border rounded-3 bg-white p-3">
            <button type="submit" class="btn btn-success btn-sm px-3 py-1 rounded-2" style="font-size:13px;">
                {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
            </button>
        </div>

    </form>
</div>
@endsection

@section('script')
@php
$prodConfigJson = json_encode([
'isEdit' => $isEdit,
'existingImgCount' => $isEdit ? $producto->imagenes->count() : 0,
'initialVariantes' => $initialVariantes ?? [],
'categoriaId' => old('categoria_id', $producto->categoria_id ?? ''),
], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
@endphp
{{-- data-config es inmune al formatter; JSON_HEX_* evita conflictos con htmlspecialchars --}}
<div id="prod-config" class="d-none" aria-hidden="true" data-config="{{ $prodConfigJson }}"></div>
{{-- Iconos Heroicons renderizados server-side; JS los lee vía innerHTML --}}
<div id="tpl-icon-x-mark" class="d-none" aria-hidden="true"><x-heroicon-m-x-mark /></div>
<div id="tpl-icon-arrow-uturn-left" class="d-none" aria-hidden="true"><x-heroicon-m-arrow-uturn-left /></div>
<script src="{{ asset('js/manageVariants.js') }}"></script>
@endsection