{{-- Modal: elegir cuál de los dos catálogos PDF generar. --}}
<div class="modal fade" id="modalCatalogoPdf" tabindex="-1" aria-labelledby="modalCatalogoPdfLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-semibold m-0" id="modalCatalogoPdfLabel" style="font-size:16px;">
                    Generar catálogo PDF
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body pt-2">
                <p class="text-secondary mb-3" style="font-size:13px;line-height:1.6;">
                    Elegí qué versión del catálogo de productos estándar querés generar.
                </p>

                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('uso-interno.productos.catalogo-pdf') }}" target="_blank"
                        class="btn btn-success btn-sm px-3 py-2 rounded-2 d-flex align-items-center text-start"
                        style="font-size:13px;">
                        <x-fluentui-document-pdf-20-o class="me-2 flex-shrink-0" style="width:16px;height:16px;" />
                        <span>
                            <span class="d-block fw-semibold">Con marca</span>
                            <span class="d-block text-white-50" style="font-size:11px;">Incluye logo y nombre de Aberturas Giacomazzi</span>
                        </span>
                    </a>

                    <a href="{{ route('uso-interno.productos.catalogo-pdf-sin-marca') }}" target="_blank"
                        class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-2 d-flex align-items-center text-start"
                        style="font-size:13px;">
                        <x-fluentui-document-pdf-20-o class="me-2 flex-shrink-0" style="width:16px;height:16px;" />
                        <span>
                            <span class="d-block fw-semibold">Sin marca</span>
                            <span class="d-block text-secondary" style="font-size:11px;">Solo los productos, sin logos ni branding</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
