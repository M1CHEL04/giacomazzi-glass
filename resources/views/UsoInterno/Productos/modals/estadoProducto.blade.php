{{--
    Modales de alta y baja de producto.
    ─────────────────────────────────────────────────────────────
    Hay un único par de modales para toda la lista, no uno por fila: el
    listado se vuelve a dibujar por AJAX al filtrar, así que unos modales
    por fila desaparecerían con el re-render. `productoEstado.js` completa
    el nombre visible y el hidden `producto_id` con los datos del badge
    que se tocó.
--}}

{{-- ── Dar de baja ─────────────────────────────────────────── --}}
<div class="modal fade" id="modalBajaProducto" tabindex="-1" aria-labelledby="modalBajaProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 rounded-3">
            <form method="POST" action="{{ route('uso-interno.productos.desactivar') }}">
                @csrf
                <input type="hidden" name="producto_id" value="" data-producto-id-input>

                <div class="modal-body p-4 text-center">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 text-white"
                        style="width:44px;height:44px;background:linear-gradient(135deg,#d75050 0%,#a93a3a 100%);">
                        <x-fluentui-warning-20-o style="width:22px;height:22px;" />
                    </span>

                    <h2 class="fw-semibold mb-2" id="modalBajaProductoLabel" style="font-size:16px;">
                        Dar de baja el producto
                    </h2>

                    <p class="text-secondary mb-1" style="font-size:13px;line-height:1.6;">
                        Vas a dar de baja
                        <span class="fw-semibold text-body" data-producto-nombre-output>este producto</span>.
                    </p>
                    <p class="text-secondary mb-0" style="font-size:12px;line-height:1.6;">
                        Dejará de mostrarse en el sitio. Podras volver a darlo de alta cuando quieras.
                    </p>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                    <button type="button"
                        class="btn btn-outline-secondary btn-sm px-3 py-1 rounded-2 flex-grow-1 m-0"
                        style="font-size:13px;"
                        data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="btn btn-danger btn-sm px-3 py-1 rounded-2 flex-grow-1 m-0"
                        style="font-size:13px;">
                        Dar de baja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Dar de alta ─────────────────────────────────────────── --}}
<div class="modal fade" id="modalAltaProducto" tabindex="-1" aria-labelledby="modalAltaProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 rounded-3">
            <form method="POST" action="{{ route('uso-interno.productos.activar') }}">
                @csrf
                <input type="hidden" name="producto_id" value="" data-producto-id-input>

                <div class="modal-body p-4 text-center">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 text-white"
                        style="width:44px;height:44px;background:linear-gradient(135deg,#2f8c63 0%,#1f5a40 100%);">
                        <x-fluentui-checkmark-20-o style="width:22px;height:22px;" />
                    </span>

                    <h2 class="fw-semibold mb-2" id="modalAltaProductoLabel" style="font-size:16px;">
                        Dar de alta el producto
                    </h2>

                    <p class="text-secondary mb-1" style="font-size:13px;line-height:1.6;">
                        Vas a dar de alta
                        <span class="fw-semibold text-body" data-producto-nombre-output>este producto</span>.
                    </p>
                    <p class="text-secondary mb-0" style="font-size:12px;line-height:1.6;">
                        Volverá a mostrarse en el sitio.
                    </p>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                    <button type="button"
                        class="btn btn-outline-secondary btn-sm px-3 py-1 rounded-2 flex-grow-1 m-0"
                        style="font-size:13px;"
                        data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="btn btn-success btn-sm px-3 py-1 rounded-2 flex-grow-1 m-0"
                        style="font-size:13px;">
                        Dar de alta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>