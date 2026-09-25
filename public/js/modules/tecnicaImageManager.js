/**
 * tecnicaImageManager.js  —  imágenes técnicas del formulario de producto
 * ─────────────────────────────────────────────────────────────
 * Mismo gesto que imageManager.js (zona de carga única, una miniatura por
 * imagen con su input propio, y baja lógica de las ya guardadas) pero sin
 * portada ni orden: una imagen técnica nunca es principal y van por orden de
 * carga, así que acá no hay estrella, ni badge, ni arrastre, ni hidden que
 * escribir al enviar.
 *
 * Va en un módulo aparte en lugar de parametrizar imageManager.js porque ese
 * archivo tiene entretejidos la portada y el orden en casi todos sus caminos
 * (conImagen, refrescarPortada, Sortable, el manifiesto del submit); un modo
 * "sin nada de eso" ahí sería más frágil que este archivo, que reutiliza las
 * mismas clases CSS de producto.css.
 *
 * La entrada de archivos —tope de peso y zona de drop— sí es la misma, y vive
 * en imageIntake.js.
 */
import { asignar, avisar, filtrarPorPeso, initBajaGuardadas, initDropZone } from './imageIntake.js';

export function initTecnicaImageManager({ cfg, iconXMark, iconArrowBack }) {

    const container = document.getElementById('tecnicas-container');
    if (!container) return { addFile: () => null, collectFiles: () => [] };

    const bloque   = document.getElementById('tecnicas-bloque') || container;
    const dropzone = document.getElementById('tecnicas-dropzone');
    const picker   = document.getElementById('tecnicas-picker');
    const contador = document.getElementById('tecnicas-contador');
    const titulo   = dropzone?.querySelector('.imagen-dropzone-titulo');
    const tituloOriginal = titulo?.textContent ?? '';

    const MAX_TECNICAS = cfg.maxImagenesTecnicas || 5;

    /**
     * El cupo se le pregunta al DOM, como en imageManager.js, en vez de llevar
     * contadores aparte: el aviso de "se omitieron N" dice un número que el
     * usuario lee, así que no puede salir de un estado que se desincroniza del
     * que se ve. Una guardada marcada para borrar libera su lugar al guardar,
     * así que no cuenta.
     */
    function usadas() {
        return container.querySelectorAll(
            '.imagen-input-card, .imagen-existente-card:not(.marcada-eliminar)'
        ).length;
    }

    function syncCupo() {
        const n = usadas();
        const lleno = n >= MAX_TECNICAS;

        if (contador) contador.textContent = `${n} de ${MAX_TECNICAS}`;
        if (picker) picker.disabled = lleno;
        if (dropzone) dropzone.classList.toggle('lleno', lleno);
        if (titulo) {
            titulo.textContent = lleno
                ? `Llegaste al máximo de ${MAX_TECNICAS} imágenes`
                : tituloOriginal;
        }
    }

    // ── Miniaturas de imagen nueva ───────────────────────────────

    /** `anchor` es la tarjeta detrás de la cual va la nueva; sin él va al final. */
    function crearTarjeta(file, anchor) {
        if (usadas() >= MAX_TECNICAS) return null;

        const wrapper = document.createElement('div');
        wrapper.className = 'imagen-input-card';

        wrapper.innerHTML = `
            <input type="file" name="imagenes_tecnicas[]" class="imagen-file-input">
            <img src="" alt="Vista previa" class="imagen-thumb">
            <button type="button" class="imagen-remove-card-btn" title="Quitar">${iconXMark}</button>
        `;

        asignar(wrapper.querySelector('.imagen-file-input'), file);

        const reader = new FileReader();
        reader.onload = e => { wrapper.querySelector('.imagen-thumb').src = e.target.result; };
        reader.readAsDataURL(file);

        wrapper.querySelector('.imagen-remove-card-btn').addEventListener('click', () => {
            wrapper.remove();
            syncCupo();
        });

        container.insertBefore(wrapper, anchor ? anchor.nextSibling : null);
        syncCupo();
        return wrapper;
    }

    /**
     * Entrada de a varios: el selector de archivos y lo que se suelta. Los que
     * no entran en el cupo se descartan con aviso, en vez de perderse en
     * silencio.
     */
    function repartir(files) {
        let anchor = null;
        let omitidos = 0;

        filtrarPorPeso(files).forEach(file => {
            const card = crearTarjeta(file, anchor);
            if (!card) {
                omitidos++;
                return;
            }
            anchor = card;
        });

        if (omitidos) {
            avisar(`Solo se pueden cargar ${MAX_TECNICAS} imágenes técnicas: ` +
                (omitidos === 1 ? 'se omitió 1.' : `se omitieron ${omitidos}.`));
        }
    }

    if (picker) picker.addEventListener('change', function () {
        if (this.files.length) repartir(this.files);
        // El picker no guarda nada: cada archivo ya vive en su miniatura.
        this.value = '';
    });

    initDropZone(bloque, repartir);

    /** Para imageDraftPersistence, que repone los archivos de a uno. */
    function addFile(file) {
        return crearTarjeta(file);
    }

    function collectFiles() {
        return Array.from(container.querySelectorAll('.imagen-file-input'))
            .map(input => input.files[0])
            .filter(Boolean);
    }

    // ── Baja de las técnicas ya guardadas (solo edición) ─────────

    initBajaGuardadas(container, { iconXMark, iconArrowBack, alCambiar: syncCupo });

    syncCupo();

    return { addFile, collectFiles };
}
