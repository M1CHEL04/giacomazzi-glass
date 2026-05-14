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
        <form method="POST" action="{{ $formAction }}" class="d-flex flex-column gap-3">
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
            </div>

            <div class="d-flex justify-content-end pt-2 border-top">
                <button type="submit" class="btn btn-success btn-sm px-3 py-1 rounded-2" style="font-size: 13px;">
                    {{ $isEdit ? 'Guardar' : 'Crear' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection