{{-- ── Modal: confirmar vaciar carrito ─────────────────────────────────── --}}
<div class="modal fade" id="vaciarCarritoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow carrito-confirm-modal">
            <div class="modal-body text-center py-4 px-4">
                <div class="carrito-confirm-icon mb-3">
                    <i class="bi bi-trash3"></i>
                </div>
                <h6 class="fw-bold mb-1">¿Vaciar carrito?</h6>
                <p class="text-secondary mb-4" style="font-size:.84rem; line-height:1.5;">
                    Se eliminarán todos los productos que agregaste.
                </p>
                <div class="d-flex gap-2">
                    <button type="button"
                        class="btn btn-outline-secondary flex-fill btn-sm py-1"
                        data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button"
                        class="btn btn-danger flex-fill btn-sm py-1"
                        id="vaciar-confirm-btn">
                        Vaciar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Offcanvas: panel del carrito ────────────────────────────────────── --}}
<div class="offcanvas offcanvas-end carrito-offcanvas" tabindex="-1"
    id="carritoOffcanvas" aria-labelledby="carritoOffcanvasLabel">

    <div class="carrito-offcanvas-header">
        <h5 class="carrito-offcanvas-title" id="carritoOffcanvasLabel">
            <x-heroicon-o-shopping-cart style="width:18px;height:18px;" />
            Mi carrito
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>

    <div class="offcanvas-body carrito-offcanvas-body" id="carrito-body">
        <p class="carrito-empty">Tu carrito está vacío.</p>
    </div>

    <div class="carrito-offcanvas-footer" id="carrito-footer" style="display:none;">
        <button type="button" class="carrito-vaciar-btn" id="carrito-vaciar-btn">
            <i class="bi bi-trash3"></i>
            Vaciar carrito
        </button>
        <button type="button" class="carrito-cotizar-btn" id="carrito-cotizar-btn"
            data-whatsapp="{{ config('app.whatsapp_number', '') }}">
            <i class="bi bi-whatsapp"></i>
            Solicitar cotización por WhatsApp
        </button>
    </div>

</div>
