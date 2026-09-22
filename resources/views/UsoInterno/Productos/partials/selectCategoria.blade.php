{{--
    Select de categoría del formulario de producto.
    ─────────────────────────────────────────────────────────────
    Lo comparten las dos líneas —estándar y a medida— porque ambas apuntan al
    mismo `categoria_id` de la misma tabla; lo único que cambia entre una y otra
    es el ancho de la columna.

    La opción "+ Crear nueva categoría…" no está acá: la inyecta
    `categoriaCreator.js` al arrancar. Así, si el JS no carga, nadie puede
    elegir un valor que el servidor va a rechazar.

    Parámetros:
      $categorias            colección de categorías, ya ordenada por nombre
      $categoriaSeleccionada id seleccionado, resuelto por la vista (old() incluido)
      $colClass              clases de la columna (default: col-12)
--}}
@php
$colClass = $colClass ?? 'col-12';
@endphp

<div class="{{ $colClass }}">
    <label for="categoria_id" class="form-label small mb-1">
        Categoría <span class="text-danger">*</span>
    </label>
    <select id="categoria_id" name="categoria_id"
        class="form-select form-select-sm py-2 rounded-2 @error('categoria_id') is-invalid @enderror"
        required>
        <option value="">Seleccioná una categoría...</option>
        @foreach ($categorias as $cat)
        <option value="{{ $cat->id }}"
            {{ $categoriaSeleccionada == $cat->id ? 'selected' : '' }}>
            {{ $cat->nombre }}
        </option>
        @endforeach
    </select>
    @error('categoria_id')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
