@extends('layouts.app-interno')
@section('title', 'Indice de productos - Panel interno - Giacomazzi Glass')
@section('page-title', 'Productos')
@section('subhead', 'Lista de productos registrados en el sistema')
@section('styles')

@endsection

@section('content')
<div class="d-flex flex-column gap-3">
    <!-- Filtros y botón crear -->
    <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center">
        <div class="position-relative flex-grow-1">
            <input
                type="text"
                name="search"
                class="form-control form-control-sm ps-5 py-2 rounded-2 border"
                placeholder="Buscar por nombre o código..."
                value="{{ request('search') }}"
                id="searchProducto">
            <x-fluentui-search-20-o class="position-absolute text-secondary" style="width:18px;height:18px;left:10px;top:50%;transform:translateY(-50%);" />
        </div>
        <div style="min-width: 200px;">
            <select class="form-select form-select-sm py-2 rounded-2 border" id="filterCategoria">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $cat)
                <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->nombre }}
                </option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 150px;">
            <select class="form-select form-select-sm py-2 rounded-2 border" id="filterActivo">
                <option value="">Todos los estados</option>
                <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>
        <a href="{{ route('uso-interno.productos.create') }}" class="btn btn-success btn-sm px-2 py-1 rounded-2 d-inline-flex align-items-center" style="font-size: 13px;">
            <x-fluentui-add-20-o class="me-1" style="width:14px;height:14px;" />
            Crear producto
        </a>
    </div>

    <div class="border rounded-3 bg-white position-relative">
        <!-- Spinner de carga -->
        <div class="d-none position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75 rounded-3" id="loading-spinner" style="min-height: 200px; z-index: 10;">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2 text-secondary small">Buscando productos...</div>
            </div>
        </div>

        <div class="d-none d-md-flex align-items-center text-uppercase small fw-semibold text-secondary border-bottom px-3 py-2" style="font-size: 11px;">
            <div style="width: 110px;">Código</div>
            <div class="flex-grow-1">Nombre</div>
            <div style="width: 180px;">Categoría</div>
            <div style="width: 100px;">Estado</div>
            <div class="text-end" style="width: 60px;">Acciones</div>
        </div>

        <div class="d-flex flex-column" id="productos-list">
            @forelse ($productos as $producto)
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 px-3 py-2 border-bottom producto-row" data-producto-id="{{ $producto->id }}">
                <div style="width: 110px;">
                    <span class="badge bg-secondary-subtle text-secondary rounded-1" style="font-size: 11px; font-weight: 600;">
                        {{ $producto->codigo ?? '—' }}
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1" style="font-size: 14px;">{{ $producto->nombre }}</div>
                    @if($producto->descripcion)
                    <div class="small text-secondary text-truncate" style="font-size: 12px; max-width: 400px;">{{ $producto->descripcion }}</div>
                    @endif
                </div>
                <div style="width: 180px;">
                    <span class="badge rounded-pill text-primary bg-primary-subtle" style="font-size: 10px; padding: 3px 10px;">
                        {{ $producto->categoria?->nombre ?? '—' }}
                    </span>
                </div>
                <div style="width: 100px;">
                    <span class="badge rounded-pill {{ $producto->activo ? 'text-success bg-success-subtle' : 'text-danger bg-danger-subtle' }}" style="font-size: 10px; padding: 3px 10px;">
                        {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                <div class="text-end" style="width: 60px;">
                    <a
                        href="{{ route('uso-interno.productos.show', $producto) }}"
                        class="text-secondary text-decoration-none p-1 d-inline-flex rounded hover-bg-light"
                        aria-label="Ver producto"
                        title="Ver">
                        <x-fluentui-eye-20-o style="width:16px;height:16px;" />
                    </a>
                </div>
            </div>
            @empty
            <div class="px-3 py-4 text-secondary text-center small">No se encontraron productos.</div>
            @endforelse
        </div>

        <div id="productos-pagination">
            @if ($productos->hasPages())
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 px-3 py-2 border-top">
                <div class="text-secondary" style="font-size: 12px;">
                    Mostrando {{ $productos->firstItem() }}-{{ $productos->lastItem() }} de {{ $productos->total() }} productos
                </div>
                <div>
                    {{ $productos->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('js/searchProducto.js') }}"></script>
@endsection