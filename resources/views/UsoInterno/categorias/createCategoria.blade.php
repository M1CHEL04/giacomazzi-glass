@extends('layouts.app-interno')
@php
$isEdit = isset($categoria);
$formAction = $isEdit ? route('uso-interno.categorias.update', $categoria) : route('uso-interno.categorias.store');
@endphp

@section('title', 'Panel Interno - Giacomazzi Glass')
@section('page-title', $isEdit ? 'Editar categoria' : 'Crear categoria')
@section('subhead', 'Completa el nombre para registrar la categoria.')

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
                </div>
                @endif

                {{-- Imagen hero --}}
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small mb-0">Imagen del hero</label>
                        <button type="button" id="btn-seleccionar-hero"
                            class="btn btn-outline-secondary btn-sm px-2 py-1 d-inline-flex align-items-center rounded-2"
                            style="font-size:12px;">
                            <i class="bi bi-image me-1" style="font-size:11px;"></i>
                            Seleccionar imagen
                        </button>
                    </div>

                    <input type="file"
                        id="imagen_hero"
                        name="imagen_hero"
                        accept="image/*"
                        class="d-none @error('imagen_hero') is-invalid @enderror">
                    @error('imagen_hero')
                    <div class="text-danger" style="font-size:12px;">{{ $message }}</div>
                    @enderror

                    <div class="d-flex flex-wrap gap-2">
                        {{-- Imagen existente (solo edición) --}}
                        @if($isEdit && $categoria->imagen_hero)
                        <div id="hero-preview-existente" class="position-relative" style="display:inline-block;">
                            <img src="{{ asset($categoria->imagen_hero) }}"
                                alt="Hero actual"
                                class="rounded-2 border"
                                style="width:80px;height:80px;object-fit:cover;display:block;">
                            <button type="button" id="btn-quitar-hero"
                                class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center position-absolute"
                                style="width:20px;height:20px;top:4px;right:4px;"
                                title="Quitar imagen">
                                <i class="bi bi-x" style="font-size:12px;line-height:1;"></i>
                            </button>
                        </div>
                        <input type="hidden" name="eliminar_imagen_hero" id="eliminar-imagen-hero-input" value="0">
                        @endif

                        {{-- Preview nueva imagen --}}
                        <div id="hero-new-preview" class="position-relative" style="display:none;">
                            <img id="hero-new-img" src="" alt=""
                                class="rounded-2 border"
                                style="width:80px;height:80px;object-fit:cover;display:block;">
                            <button type="button" id="btn-quitar-hero-new"
                                class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center position-absolute"
                                style="width:20px;height:20px;top:4px;right:4px;"
                                title="Quitar">
                                <i class="bi bi-x" style="font-size:12px;line-height:1;"></i>
                            </button>
                        </div>
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
<script src="{{ asset('js/modules/hero-imagen.js') }}"></script>
@endsection