<div class="modal fade" id="modalCrearCategoria" tabindex="-1" aria-labelledby="modalCrearCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content border-0 rounded-3">

            <div class="modal-header border-0 pb-0">
                <h2 class="fw-semibold m-0" id="modalCrearCategoriaLabel" style="font-size:16px;">
                    Nueva categoría
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body pt-2">
                <p class="text-secondary mb-3" style="font-size:13px;line-height:1.6;">
                    Se crea activa y queda seleccionada en el producto. La imagen de portada
                    se carga después, desde Categorías &rarr; Editar.
                </p>

                <label for="nueva-categoria-nombre" class="form-label small mb-1">
                    Nombre de la categoría <span class="text-danger">*</span>
                </label>
                <input type="text"
                    id="nueva-categoria-nombre"
                    class="form-control form-control-sm py-2 rounded-2"
                    placeholder="Ej: Mamparas"
                    maxlength="255"
                    autocomplete="off">

                {{-- Errores del servidor (422) y la validación de vacío en cliente.
                     d-block porque el input no siempre está en un grupo validado. --}}
                <div id="nueva-categoria-feedback" class="invalid-feedback d-none" style="display:block;"></div>

                {{-- Sólo se muestra si el formulario ya tiene variantes cargadas. --}}
                <div id="nueva-categoria-aviso-variantes"
                    class="alert alert-warning border-0 small mb-0 mt-3 py-2"
                    style="font-size:12px;line-height:1.5;" hidden>
                    Las variantes que ya cargaste se van a descartar: pertenecen a la
                    categoría anterior.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button"
                    class="btn btn-outline-secondary btn-sm px-3 rounded-2"
                    style="font-size:13px;"
                    data-bs-dismiss="modal">
                    Cancelar
                </button>
                {{-- El disabled con spinner mientras el POST va en vuelo evita el doble alta. --}}
                <button type="button"
                    id="nueva-categoria-crear"
                    class="btn btn-success btn-sm px-3 rounded-2 d-inline-flex align-items-center"
                    style="font-size:13px;">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"
                        data-spinner></span>
                    <span data-crear-texto>Crear categoría</span>
                </button>
            </div>

        </div>
    </div>
</div>