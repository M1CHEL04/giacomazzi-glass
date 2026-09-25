/**
 * imageIntake.js  —  lo común a galería y técnicas
 * ─────────────────────────────────────────────────────────────
 * Lo que comparten imageManager.js y tecnicaImageManager.js: filtrar archivos
 * por peso, meterlos en un input de a uno, la zona donde se pueden soltar, y
 * la baja de una imagen ya guardada.
 *
 * El reparto en tarjetas no vive acá: cada manager tiene su propia noción de
 * cupo, de tarjeta y de orden. Acá está sólo lo que es idéntico en los dos.
 */

/**
 * Espejo de ImagenProductoValida::PESO_MAX_KB (8192 KB). Está duplicado a
 * propósito: el servidor sigue siendo el que manda, esto es sólo para que el
 * usuario no espere a subir 40 MB para enterarse. Si allá cambia, acá también.
 */
const PESO_MAX_BYTES = 8 * 1024 * 1024;

export function avisar(mensaje) {
    if (typeof window.showToast === 'function') window.showToast(mensaje, 'error');
    else console.warn(mensaje);
}

/**
 * Deja `file` como único archivo de `input`. Asignar `.files` no dispara
 * `change`, así que quien llama decide si lo emite.
 */
export function asignar(input, file) {
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
}

/**
 *
 * Devuelve sólo los que pasan el tope y avisa por los que no, nombrándolos:
 * con cinco archivos elegidos de una, "la imagen pesa demasiado" no dice cuál.
 * Mismo criterio que el mensaje de ImagenProductoValida.
 */
export function filtrarPorPeso(files) {
    const validos = [];
    const pesados = [];

    Array.from(files).forEach(file => {
        (file.size > PESO_MAX_BYTES ? pesados : validos).push(file);
    });

    if (pesados.length) {
        const max = PESO_MAX_BYTES / 1024 / 1024;
        avisar(pesados
            .map(f => `«${f.name}» pesa ${(f.size / 1024 / 1024).toFixed(1)} MB`)
            .join('; ') + `. El máximo permitido es ${max} MB.`);
    }

    return validos;
}

/** El arrastre de reordenamiento de Sortable no lleva archivos; éste sí. */
const traeArchivos = e => Array.from(e.dataTransfer?.types || []).includes('Files');

let bloqueoGlobal = false;

/**
 * Si el usuario falla la zona y suelta la imagen en cualquier otro lado, el
 * navegador la abre y se lleva puesto el formulario a medio llenar. Esto lo
 * impide sin tocar el resto de la página.
 */
function bloquearDropFueraDeZona() {
    if (bloqueoGlobal) return;
    bloqueoGlobal = true;

    ['dragover', 'drop'].forEach(tipo => {
        window.addEventListener(tipo, e => {
            if (traeArchivos(e)) e.preventDefault();
        });
    });
}

/**
 * Permite soltar archivos del escritorio sobre `container`. `recibir` recibe
 * la FileList tal cual; el filtrado por peso y el cupo los resuelve el manager.
 *
 * Los listeners van en captura y cortan la propagación cuando el arrastre trae
 * archivos, para que el handler de Sortable —que espera un arrastre interno y
 * no tiene tarjeta que mover— no llegue a verlos. Por eso hay que llamar a esto
 * antes de Sortable.create(): si el arrastre no trae archivos, no se toca nada
 * y el reordenamiento sigue funcionando igual.
 */
export function initDropZone(container, recibir) {
    if (!container) return;

    bloquearDropFueraDeZona();

    const opciones = { capture: true };
    const marcar = e => {
        if (!traeArchivos(e)) return;
        e.preventDefault();
        e.stopPropagation();
        container.classList.add('arrastre-activo');
    };

    container.addEventListener('dragenter', marcar, opciones);
    container.addEventListener('dragover', marcar, opciones);

    container.addEventListener('dragleave', e => {
        // Pasar de una tarjeta a otra dispara dragleave sin haber salido de la
        // fila: sólo cuenta si el destino quedó fuera del contenedor.
        if (!traeArchivos(e)) return;
        if (e.relatedTarget && container.contains(e.relatedTarget)) return;
        container.classList.remove('arrastre-activo');
    }, opciones);

    container.addEventListener('drop', e => {
        if (!traeArchivos(e)) return;
        e.preventDefault();
        e.stopPropagation();
        container.classList.remove('arrastre-activo');
        if (e.dataTransfer.files.length) recibir(e.dataTransfer.files);
    }, opciones);
}

/**
 * Baja de una imagen ya guardada (sólo en edición): alterna el hidden que la
 * marca para borrar. Es el mismo gesto en galería y en técnicas, así que vive
 * acá en vez de estar copiado en los dos managers.
 *
 * El id ya viene en el `value` desde el blade y el input arranca `disabled`
 * —uno deshabilitado no se envía—, así que marcar y desmarcar es alternar esa
 * sola propiedad: el JS no necesita saber ningún id ni armar ningún selector.
 * Y es reversible hasta el submit.
 */
export function initBajaGuardadas(container, { iconXMark, iconArrowBack, alCambiar }) {
    if (!container) return;

    container.addEventListener('click', e => {
        const btn = e.target.closest('[data-eliminar]');
        if (!btn || !container.contains(btn)) return;

        const card  = btn.closest('.imagen-existente-card');
        const input = card?.querySelector('[data-eliminar-input]');
        if (!card || !input) return;

        const marcando = input.disabled;
        input.disabled = !marcando;

        card.classList.toggle('marcada-eliminar', marcando);
        btn.innerHTML = marcando ? iconArrowBack : iconXMark;
        btn.classList.replace(
            marcando ? 'btn-danger' : 'btn-secondary',
            marcando ? 'btn-secondary' : 'btn-danger'
        );
        btn.title = marcando ? 'Deshacer' : 'Eliminar';

        alCambiar?.();
    });
}
