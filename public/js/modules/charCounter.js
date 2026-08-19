const UMBRAL_AVISO = 0.9;

function formatear(n) {
    return n.toLocaleString('es-AR');
}

function pintar(campo, salida) {
    const max = parseInt(campo.dataset.charMax || campo.getAttribute('maxlength'), 10);
    if (!Number.isFinite(max) || max <= 0) return;

    // .value.length cuenta como lo hace maxlength (unidades UTF-16), que es
    // el número que el usuario ve frenarse al escribir.
    const usados = campo.value.length;
    const restantes = Math.max(0, max - usados);

    salida.textContent = `${formatear(usados)} / ${formatear(max)} caracteres · ${formatear(restantes)} restantes`;

    const cerca = usados >= max * UMBRAL_AVISO;
    salida.classList.toggle('text-danger', usados >= max);
    salida.classList.toggle('text-warning', cerca && usados < max);
    salida.classList.toggle('text-secondary', !cerca);
}

export function initCharCounters(root = document) {
    root.querySelectorAll('[data-char-count]').forEach(function (campo) {
        const salida = root.querySelector(`[data-char-count-for="${campo.id}"]`);
        if (!salida) return;

        const actualizar = () => pintar(campo, salida);

        // `input` cubre tipeo, pegado, arrastre y deshacer.
        campo.addEventListener('input', actualizar);
        // Estado inicial: el formulario de edición y el `old()` tras un
        // error de validación llegan con texto ya cargado.
        actualizar();
    });
}
