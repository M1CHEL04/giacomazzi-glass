@foreach($todasCategorias as $cat)
<label class="filtro-opcion">
    <input type="checkbox"
        name="categorias[]"
        value="{{ $cat->id }}"
        data-categoria-url="{{ route('productos.categoria', $cat->id) }}"
        {{ in_array($cat->id, $categoriasFiltro ?? []) ? 'checked' : '' }}>
    <span class="filtro-label">{{ $cat->nombre }}</span>
</label>
@endforeach
