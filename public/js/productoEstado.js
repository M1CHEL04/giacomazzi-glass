/**
 * productoEstado.js  —  alta y baja de producto desde el badge del listado
 * ─────────────────────────────────────────────────────────────
 * El badge de estado abre el modal que corresponde: rojo para dar de baja
 * uno activo, verde para dar de alta uno inactivo. Los modales son dos y
 * viven fuera de la lista (ver modals/estadoProducto.blade.php), así que
 * acá solo se completa el nombre visible y el hidden `producto_id`.
 *
 * El listener va delegado en el documento porque searchProducto.js vuelve
 * a dibujar las filas al filtrar o paginar: un listener por badge se
 * perdería en el primer re-render.
 */
document.addEventListener('DOMContentLoaded', function () {
    const modalBaja = document.getElementById('modalBajaProducto');
    const modalAlta = document.getElementById('modalAltaProducto');

    if (!modalBaja || !modalAlta) return;

    document.addEventListener('click', function (e) {
        const badge = e.target.closest('[data-estado-toggle]');
        if (!badge) return;

        const estaActivo = badge.dataset.activo === '1';
        const modal = estaActivo ? modalBaja : modalAlta;

        const inputId = modal.querySelector('[data-producto-id-input]');
        const salidaNombre = modal.querySelector('[data-producto-nombre-output]');

        if (inputId) inputId.value = badge.dataset.productoId || '';
        if (salidaNombre) {
            // textContent, no innerHTML: el nombre lo carga el usuario.
            salidaNombre.textContent = badge.dataset.productoNombre
                ? `"${badge.dataset.productoNombre}"`
                : 'este producto';
        }

        bootstrap.Modal.getOrCreateInstance(modal).show();
    });
});
