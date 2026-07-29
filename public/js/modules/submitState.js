/**
 * submitState.js  —  spinner y bloqueo del botón al enviar el formulario
 * ─────────────────────────────────────────────────────────────
 * Alta y edición de producto suben las imágenes por SFTP al file server,
 * así que entre el clic y la respuesta pueden pasar varios segundos con
 * la pantalla igual. Sin señal el usuario vuelve a apretar, y ese segundo
 * envío crea el producto de nuevo o repite la subida de las imágenes.
 *
 * Es el mismo gesto que ya usa forgotPassword.js: el spinner vive dentro
 * del botón (`[data-spinner]`) y se muestra sacándole `.d-none`.
 */
export function initSubmitState(form) {
    if (!form) return;

    const btn = form.querySelector('[data-submit]');
    if (!btn) return;

    const spinner = btn.querySelector('[data-spinner]');
    const label = btn.querySelector('[data-submit-text]');
    const loadingText = btn.dataset.loadingText || '';
    const idleText = label ? label.textContent : '';
    let sending = false;

    form.addEventListener('submit', function (e) {
        // Segundo clic mientras el primero todavía viaja: no hay nada
        // nuevo que enviar.
        if (sending) {
            e.preventDefault();
            return;
        }
        sending = true;

        if (spinner) spinner.classList.remove('d-none');
        if (label && loadingText) label.textContent = loadingText;
        btn.setAttribute('aria-busy', 'true');

        // El disabled va un tick más tarde: puesto dentro del propio
        // handler, hay navegadores que descartan el envío en curso.
        setTimeout(function () {
            btn.disabled = true;
        }, 0);
    });

    // Volver con el botón "atrás" restaura la página desde el bfcache tal
    // como quedó: con el botón deshabilitado y el spinner girando para
    // siempre. Acá se lo devuelve a su estado de reposo.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        sending = false;
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        if (spinner) spinner.classList.add('d-none');
        if (label) label.textContent = idleText;
    });
}
