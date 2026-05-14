@extends('layouts.app-interno')
@section('title', 'Panel Interno - Giacomazzi Glass')
@section('page-title', 'Categorias')
@section('subhead', 'En esta sección podras crear y gestionar las categorias disponibles')
@section('content')
<div class="d-flex flex-column gap-3">
    <!-- Search bar y Create button -->
    <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center">
        <div class="position-relative flex-grow-1">
            <input
                type="text"
                name="search"
                class="form-control form-control-sm ps-5 py-2 rounded-2 border"
                placeholder="Buscar por nombre..."
                value="{{ request('search') }}"
                id="searchCategory">
            <x-fluentui-search-20-o class="position-absolute text-secondary" style="width:18px;height:18px;left:10px;top:50%;transform:translateY(-50%);" />
        </div>
        <a href="{{ route('categorias.create') }}" class="btn btn-success btn-sm px-2 py-1 rounded-2 d-inline-flex align-items-center" style="font-size: 13px;">
            <x-fluentui-add-20-o class="me-1" style="width:14px;height:14px;" />
            Crear categoría
        </a>
    </div>

    <div class="border rounded-3 bg-white position-relative">
        <!-- Spinner de carga -->
        <div class="d-none position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75 rounded-3" id="loading-spinner" style="min-height: 200px; z-index: 10;">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2 text-secondary small">Buscando categorías...</div>
            </div>
        </div>

        <div class="d-none d-md-flex align-items-center text-uppercase small fw-semibold text-secondary border-bottom px-3 py-2" style="font-size: 11px;">
            <div class="flex-grow-1">Nombre</div>
            <div style="width: 140px;">Estado</div>
            <div class="text-end" style="width: 60px;">Acciones</div>
        </div>

        <div class="d-flex flex-column" id="categorias-list">
            @forelse ($categorias as $categoria)
            @php
            $estadoRaw = data_get($categoria, 'activo', data_get($categoria, 'activa', data_get($categoria, 'estado')));
            $isActive = is_bool($estadoRaw)
            ? $estadoRaw
            : in_array($estadoRaw, [1, '1', 'activo', 'activa', 'ACTIVO', 'ACTIVA'], true);
            @endphp
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 px-3 py-2 border-bottom category-row" data-category-id="{{ $categoria->id }}">
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1 category-name" style="font-size: 14px;">{{ $categoria->nombre }}</div>
                    <div class="small text-secondary" style="font-size: 12px;">{{ $categoria->descripcion ?? ($categoria->productos_count . ' productos asignados') }}</div>
                </div>
                <div class="d-flex align-items-center" style="width: 140px;">
                    <span class="badge rounded-pill {{ $isActive ? 'text-success bg-success-subtle' : 'text-danger bg-danger-subtle' }}" style="font-size: 10px; padding: 3px 10px;">
                        {{ $isActive ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>
                <div class="text-end" style="width: 60px;">
                    <a
                        href="{{ route('categorias.edit', $categoria) }}"
                        class="text-secondary text-decoration-none p-1 d-inline-flex rounded hover-bg-light"
                        aria-label="Editar categoria"
                        title="Editar">
                        <x-fluentui-pen-20-o style="width:16px;height:16px;" />
                    </a>
                </div>
            </div>
            @empty
            <div class="px-3 py-4 text-secondary text-center small">No se encontraron categorías.</div>
            @endforelse
        </div>

        <div id="categorias-pagination">
            @if ($categorias->hasPages())
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 px-3 py-2 border-top">
                <div class="text-secondary" style="font-size: 12px;">
                    Mostrando {{ $categorias->firstItem() }}-{{ $categorias->lastItem() }} de {{ $categorias->total() }} categorías
                </div>
                <div>
                    {{ $categorias->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('js/searchCategory.js') }}"></script>
@endsection