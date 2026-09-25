@extends('layouts.app-interno')
@php
$isEdit = isset($producto);
$formAction = $isEdit
? route('uso-interno.especiales.update', $producto)
: route('uso-interno.especiales.store');
$maxDescripcion = \App\Models\Producto::MAX_DESCRIPCION;
$maxDescripcionTecnica = \App\Models\Producto::MAX_DESCRIPCION_TECNICA;
$maxImagenes = \App\Models\Producto::MAX_IMAGENES;
$maxImagenesTecnicas = \App\Models\Producto::MAX_IMAGENES_TECNICAS;
@endphp
@section('title', ($isEdit ? 'Editar' : 'Crear') . ' producto especial - Panel interno - Aberturas Giacomazzi')
@section('page-title', $isEdit ? 'Editar producto especial' : 'Crear producto especial')
@section('subhead', $isEdit ? 'Modificá los datos del producto a medida' : 'Agregá un producto a medida al sistema')

@section('css')
<link rel="stylesheet" href="{{ versioned_asset('css/producto.css') }}">
@endsection

@section('content')
<div class="d-flex flex-column gap-3">

    {{-- Volver --}}
    <div>
        <a href="{{ route('uso-interno.especiales.index') }}"
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
                        placeholder="Ej: Puerta pivotante de doble hoja"
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
                        placeholder="Ej: ESP-001"
                        value="{{ old('codigo', $producto->codigo ?? '') }}"
                        autocomplete="off" required>
                    @error('codigo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Categoría --}}
                @include('UsoInterno.Productos.partials.selectCategoria', [
                'categorias' => $categorias,
                'categoriaSeleccionada' => old('categoria_id', $producto->categoria_id ?? ''),
                'colClass' => 'col-12',
                ])

                {{-- Descripción --}}
                <div class="col-12">
                    <label for="descripcion" class="form-label small mb-1">
                        Descripción <span class="text-danger">*</span>
                    </label>
                    <textarea id="descripcion" name="descripcion" rows="3"
                        class="form-control form-control-sm py-2 rounded-2 @error('descripcion') is-invalid @enderror"
                        placeholder="Qué es y para qué sirve, en pocas líneas..." required
                        maxlength="{{ $maxDescripcion }}"
                        data-char-count data-char-max="{{ $maxDescripcion }}"
                        aria-describedby="descripcion-contador">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
                    <div id="descripcion-contador" class="form-text text-end small"
                        data-char-count-for="descripcion" aria-live="polite"></div>
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
                    <textarea id="descripcion_tecnica" name="descripcion_tecnica" rows="8"
                        class="form-control form-control-sm py-2 rounded-2 @error('descripcion_tecnica') is-invalid @enderror"
                        placeholder="Materiales, sistemas de apertura, vidrios, herrajes, tolerancias..."
                        maxlength="{{ $maxDescripcionTecnica }}"
                        data-char-count data-char-max="{{ $maxDescripcionTecnica }}"
                        aria-describedby="descripcion_tecnica-contador">{{ old('descripcion_tecnica', $producto->descripcion_tecnica ?? '') }}</textarea>
                    <div id="descripcion_tecnica-contador" class="form-text text-end small"
                        data-char-count-for="descripcion_tecnica" aria-live="polite"></div>
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
        @include('UsoInterno.Productos.partials.bloqueImagenes', [
        'prefijo' => 'imagenes',
        'campo' => 'imagenes',
        'titulo' => 'Imágenes',
        'maximo' => $maxImagenes,
        'imagenes' => $isEdit ? $producto->imagenes : collect(),
        'ordenable' => true,
        ])

        {{-- ── IMÁGENES TÉCNICAS ── --}}
        @include('UsoInterno.Productos.partials.bloqueImagenes', [
        'prefijo' => 'tecnicas',
        'campo' => 'imagenes_tecnicas',
        'titulo' => 'Imágenes técnicas',
        'nota' => 'opcional',
        'descripcion' => 'Planos, cortes, despieces o tablas de medidas.',
        'maximo' => $maxImagenesTecnicas,
        'imagenes' => $isEdit ? $producto->imagenesTecnicas : collect(),
        ])


        {{-- Submit --}}
        <div class="d-flex justify-content-end border rounded-3 bg-white p-3">
            {{-- El envío sube las imágenes por SFTP y puede tardar: el
                 spinner y el disabled evitan el doble alta. --}}
            <button type="submit" class="btn btn-success btn-sm px-3 py-1 rounded-2" style="font-size:13px;"
                data-submit data-loading-text="{{ $isEdit ? 'Guardando…' : 'Creando…' }}">
                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"
                    data-spinner></span>
                <span data-submit-text>{{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}</span>
            </button>
        </div>

    </form>
</div>
@endsection

@section('script')
@php
$prodConfigJson = json_encode([
'isEdit' => $isEdit,
'productoId' => $producto->id ?? null,
'hasErrors' => $errors->any(),
'initialVariantes' => [],
'categoriaId' => old('categoria_id', $producto->categoria_id ?? ''),
'maxImagenes' => $maxImagenes,
'maxImagenesTecnicas' => $maxImagenesTecnicas,
], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
@endphp
{{-- data-config es inmune al formatter; JSON_HEX_* evita conflictos con htmlspecialchars --}}
<div id="prod-config" class="d-none" aria-hidden="true" data-config="{{ $prodConfigJson }}"></div>
{{-- Iconos Heroicons renderizados server-side; JS los lee vía innerHTML --}}
<div id="tpl-icon-x-mark" class="d-none" aria-hidden="true"><x-heroicon-m-x-mark /></div>
<div id="tpl-icon-arrow-uturn-left" class="d-none" aria-hidden="true"><x-heroicon-m-arrow-uturn-left /></div>
<div id="tpl-icon-star-fill" class="d-none" aria-hidden="true"><x-heroicon-s-star /></div>
<div id="tpl-icon-star-outline" class="d-none" aria-hidden="true"><x-heroicon-o-star /></div>
{{-- Fuera del <form> del producto: acá no queda anidado. --}}
@include('UsoInterno.Productos.modals.crearCategoria')
<script defer src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"
    integrity="sha384-HZZ/fukV+9G8gwTNjN7zQDG0Sp7MsZy5DDN6VfY3Be7V9dvQpEpR2jF2HlyFUUjU"
    crossorigin="anonymous"></script>
<script type="module" src="{{ versioned_asset('js/manageEspecial.js') }}"></script>
@endsection