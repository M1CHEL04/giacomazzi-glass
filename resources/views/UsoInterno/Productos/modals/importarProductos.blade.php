{{-- Modal: carga masiva desde Excel/CSV. Lo comparten las dos líneas; `rutaImportar` dice a cuál van. --}}
<div class="modal fade" id="modalImportarProductos" tabindex="-1" aria-labelledby="modalImportarProductosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content border-0 rounded-3">
            <form method="POST" action="{{ $rutaImportar }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-semibold m-0" id="modalImportarProductosLabel" style="font-size:16px;">
                        Carga masiva
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body pt-2">
                    <p class="text-secondary mb-2" style="font-size:13px;line-height:1.6;">
                        Subí un Excel o CSV con una fila por producto. La primera fila tiene que tener estos encabezados:
                    </p>
                    <p class="mb-2" style="font-size:13px;">
                        <strong>Nombre</strong>, <strong>Código</strong>, <strong>Categoría</strong>,
                        @isset($unidades)<strong>Unidad</strong>, @endisset
                        <strong>Descripción</strong>, Descripción técnica <span class="text-secondary">(opcional)</span>
                    </p>
                    {{-- El input real cubre toda la zona (invisible): el clic, el arrastre
                         y el foco con teclado son los nativos. El JS sólo pinta el estado. --}}
                    <div class="internal-dropzone" data-dropzone>
                        <input type="file" name="archivo" accept=".xlsx,.xls,.csv" required
                            aria-labelledby="importarZonaTexto" aria-describedby="importarFormatos">
                        <x-fluentui-document-table-20-o class="internal-dropzone-icon" aria-hidden="true" />
                        <div class="internal-dropzone-body">
                            <div id="importarZonaTexto" class="internal-dropzone-title" data-dropzone-title>
                                Elegí el archivo o arrastralo acá
                            </div>
                            <div id="importarFormatos" class="internal-dropzone-hint" data-dropzone-hint>
                                Excel (.xlsx, .xls) o CSV (.csv), hasta 5 MB
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-2" data-bs-dismiss="modal" style="font-size:13px;">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm px-3 rounded-2" style="font-size:13px;">Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-dropzone]').forEach(function(zona) {
        var input = zona.querySelector('input[type="file"]');
        var titulo = zona.querySelector('[data-dropzone-title]');
        var pista = zona.querySelector('[data-dropzone-hint]');
        var tituloVacio = titulo.textContent.trim();
        var pistaVacia = pista.textContent.trim();

        function pintar() {
            var archivo = input.files && input.files[0];
            zona.classList.toggle('is-filled', !!archivo);
            titulo.textContent = archivo ? archivo.name : tituloVacio;
            pista.textContent = archivo ?
                (archivo.size / 1024 < 1024 ?
                    Math.max(1, Math.round(archivo.size / 1024)) + ' KB · tocá para cambiarlo' :
                    (archivo.size / 1048576).toFixed(1).replace('.', ',') + ' MB · tocá para cambiarlo') :
                pistaVacia;
        }

        input.addEventListener('change', pintar);
        input.addEventListener('dragenter', function() {
            zona.classList.add('is-over');
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            input.addEventListener(ev, function() {
                zona.classList.remove('is-over');
            });
        });

        // Al cerrar el modal el form se limpia, así no queda un archivo viejo elegido.
        var modal = zona.closest('.modal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function() {
                zona.closest('form').reset();
                pintar();
            });
        }
    });
</script>