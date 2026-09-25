/**
 * imageManager.js
 * ─────────────────────────────────────────────────────────────
 * Gestión de imágenes en el formulario de producto:
 *   - Zona de carga única: se eligen o se sueltan varias de una
 *   - Una miniatura por imagen, con su input propio
 *   - Toggle de eliminación de imágenes guardadas (edición)
 *   - Orden de la galería por arrastre, teclado o estrella
 *
 * Hay un solo selector de archivos —el de la zona de carga, que no tiene
 * `name` y por lo tanto nunca se envía—. Cada archivo elegido se convierte en
 * una miniatura que lleva su propio input `imagenes[]` con un único archivo,
 * que es lo que el backend espera. Así "elegir varias" y "sumar de a una" son
 * el mismo gesto en el mismo lugar, en vez de dos botones que hacen lo mismo.
 *
 * El orden de las tarjetas dentro de #imagenes-container ES el orden que se
 * guarda, y la primera es la portada. Por eso no hay estado de portada en
 * variables: preguntarle al DOM hace imposible que lo que se ve y lo que se
 * envía se contradigan. El submit traduce ese orden a `imagenes_orden`, como
 * `existente:41,nueva:0,…`, donde `nueva:<i>` es la posición del archivo entre
 * las miniaturas nuevas —el mismo índice que le llega a PHP después de
 * array_filter—.
 */
import { asignar, avisar, filtrarPorPeso, initBajaGuardadas, initDropZone } from './imageIntake.js';

export function initImageManager({ cfg, iconXMark, iconArrowBack, iconStarFill, iconStarOutline }) {

    const imagenesContainer = document.getElementById('imagenes-container');
    if (!imagenesContainer) return { addFile: () => null, collectFiles: () => [], applyOrder: () => {} };

    const bloque   = document.getElementById('imagenes-bloque') || imagenesContainer;
    const dropzone = document.getElementById('imagenes-dropzone');
    const picker   = document.getElementById('imagenes-picker');
    const contador = document.getElementById('imagenes-contador');
    const titulo   = dropzone?.querySelector('.imagen-dropzone-titulo');
    const tituloOriginal = titulo?.textContent ?? '';

    const MAX_IMG = cfg.maxImagenes || 5;

    // ── Estado = DOM ─────────────────────────────────────────────

    const cards         = () => Array.from(imagenesContainer.children).filter(el => el.matches('.imagen-card'));
    const esGuardada    = c => c.classList.contains('imagen-existente-card');
    const estaEliminada = c => c.classList.contains('marcada-eliminar');

    /**
     * Las que van a existir después de guardar, en orden. La primera es la
     * portada. Toda miniatura tiene imagen —no se crea ninguna vacía—, así que
     * lo único que sobra son las guardadas marcadas para borrar.
     */
    const conImagen = () => cards().filter(c => !estaEliminada(c));

    /** Cupo y contador salen del mismo lugar: las que van a quedar guardadas. */
    function syncCupo() {
        const usadas = conImagen().length;
        const lleno  = usadas >= MAX_IMG;

        if (contador) contador.textContent = `${usadas} de ${MAX_IMG}`;

        // Con menos de dos imágenes no hay nada que ordenar.
        const ayuda = document.getElementById('imagenes-orden-ayuda');
        if (ayuda) ayuda.classList.toggle('d-none', usadas < 2);

        if (picker) picker.disabled = lleno;
        if (dropzone) dropzone.classList.toggle('lleno', lleno);
        if (titulo) {
            titulo.textContent = lleno
                ? `Llegaste al máximo de ${MAX_IMG} imágenes`
                : tituloOriginal;
        }
    }

    // ── Miniaturas de imagen nueva ───────────────────────────────

    /**
     * `anchor` es la tarjeta detrás de la cual va la nueva; sin él va al final.
     * Es lo que mantiene el orden cuando entran varios archivos de una: cada
     * uno se encadena detrás del anterior.
     */
    function crearTarjeta(file, anchor) {
        if (conImagen().length >= MAX_IMG) return null;

        const wrapper = document.createElement('div');
        wrapper.className = 'imagen-input-card imagen-card';
        wrapper.setAttribute('role', 'listitem');
        wrapper.tabIndex = 0;

        wrapper.innerHTML = `
            <input type="file" name="imagenes[]" class="imagen-file-input">
            <img src="" alt="Vista previa" class="imagen-thumb">
            <button type="button" class="imagen-remove-card-btn" title="Quitar">${iconXMark}</button>
            <button type="button" class="imagen-portada-btn" title="Poner primera (portada)"
                    aria-label="Poner primera (portada)">${iconStarOutline}</button>
        `;

        asignar(wrapper.querySelector('.imagen-file-input'), file);

        const reader = new FileReader();
        reader.onload = e => { wrapper.querySelector('.imagen-thumb').src = e.target.result; };
        reader.readAsDataURL(file);

        wrapper.querySelector('.imagen-remove-card-btn').addEventListener('click', () => {
            wrapper.remove();
            syncCupo();
            refrescarPortada();
        });

        imagenesContainer.insertBefore(wrapper, anchor ? anchor.nextSibling : null);
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

        refrescarPortada();

        if (omitidos) {
            avisar(`Solo se pueden cargar ${MAX_IMG} imágenes: ` +
                (omitidos === 1 ? 'se omitió 1.' : `se omitieron ${omitidos}.`));
        }
    }

    if (picker) picker.addEventListener('change', function () {
        if (this.files.length) repartir(this.files);
        // El picker no guarda nada: cada archivo ya vive en su miniatura. Además
        // vaciarlo permite volver a elegir el mismo archivo si se lo quitó.
        this.value = '';
    });

    initDropZone(bloque, repartir);

    /** Para imageDraftPersistence, que repone los archivos de a uno. */
    function addFile(file) {
        const card = crearTarjeta(file);
        refrescarPortada();
        return card;
    }

    function collectFiles() {
        return Array.from(imagenesContainer.querySelectorAll('.imagen-file-input'))
            .map(input => input.files[0])
            .filter(Boolean);
    }

    // ── Borrado de imágenes guardadas ────────────────────────────

    initBajaGuardadas(imagenesContainer, {
        iconXMark,
        iconArrowBack,
        alCambiar: () => {
            syncCupo();
            refrescarPortada();
        },
    });

    // ── Portada ──────────────────────────────────────────────────

    /**
     * Pinta quién es la portada: la primera con imagen. No recibe nada y no
     * guarda nada, así que no puede desincronizarse del orden real.
     */
    function refrescarPortada() {
        const primera = conImagen()[0] || null;

        cards().forEach(card => {
            const esPortada = card === primera;

            const estrella = card.querySelector('.imagen-portada-btn');
            if (estrella) {
                estrella.classList.toggle('activa', esPortada);
                estrella.innerHTML = esPortada ? iconStarFill : iconStarOutline;
            }

            const badge = card.querySelector('.imagen-portada-badge');
            if (esPortada && !badge) {
                const nuevo = document.createElement('span');
                nuevo.className = 'imagen-portada-badge';
                nuevo.textContent = 'Portada';
                card.appendChild(nuevo);
            } else if (!esPortada && badge) {
                badge.remove();
            }
        });
    }

    /** Le dice a un lector de pantalla dónde quedó la tarjeta que se movió. */
    function anunciar(card) {
        const estado = document.getElementById('imagenes-orden-estado');
        if (!estado) return;
        const lista = conImagen();
        const pos   = lista.indexOf(card);
        if (pos < 0) return;
        estado.textContent = `Imagen ${pos + 1} de ${lista.length}${pos === 0 ? ', portada' : ''}`;
    }

    function alFrente(card) {
        imagenesContainer.insertBefore(card, imagenesContainer.firstElementChild);
        refrescarPortada();
        anunciar(card);
    }

    // ── Eventos delegados ────────────────────────────────────────
    // Un solo listener en el contenedor en lugar de los onclick inline que
    // tenían los blades: las tarjetas se mueven de lugar, y así no hay que
    // reenganchar nada.

    imagenesContainer.addEventListener('click', e => {
        const estrella = e.target.closest('.imagen-portada-btn');
        if (!estrella || !imagenesContainer.contains(estrella)) return;

        const card = estrella.closest('.imagen-card');
        if (card && !estaEliminada(card)) alFrente(card);
    });

    // Reordenar por teclado: sin esto la feature no existe para quien no puede
    // arrastrar. Con Alt para no pisar el uso normal de las flechas.
    imagenesContainer.addEventListener('keydown', e => {
        if (!e.altKey) return;

        const card = e.target.closest?.('.imagen-card');
        if (!card || !imagenesContainer.contains(card) || estaEliminada(card)) return;

        const movibles = cards().filter(c => !estaEliminada(c));
        const i = movibles.indexOf(card);
        if (i < 0) return;

        if (e.key === 'Home') {
            e.preventDefault();
            alFrente(card);
        } else if (e.key === 'ArrowLeft' && i > 0) {
            e.preventDefault();
            imagenesContainer.insertBefore(card, movibles[i - 1]);
            refrescarPortada();
            anunciar(card);
        } else if (e.key === 'ArrowRight' && i < movibles.length - 1) {
            e.preventDefault();
            imagenesContainer.insertBefore(movibles[i + 1], card);
            refrescarPortada();
            anunciar(card);
        } else {
            return;
        }

        card.focus();
    });

    // ── Arrastre ─────────────────────────────────────────────────

    if (window.Sortable) {
        window.Sortable.create(imagenesContainer, {
            draggable: '.imagen-card',
            // Quedan afuera las marcadas para borrar (no tienen posición) y los
            // botones de la tarjeta.
            filter: '.marcada-eliminar, button',
            // Sortable lo trae en true, y eso hace preventDefault() sobre el
            // evento filtrado: rompería todos los botones de la tarjeta.
            preventOnFilter: false,
            animation: 150,
            // En touch el arrastre arranca con pulsación larga, así el scroll
            // vertical de la página sigue funcionando sobre la fila.
            delay: 150,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            ghostClass: 'imagen-card-ghost',
            chosenClass: 'imagen-card-chosen',
            onEnd: evt => {
                refrescarPortada();
                if (evt.item) anunciar(evt.item);
            },
        });
    }

    // ── Restaurar el orden tras un error de validación ───────────

    /**
     * Reubica las tarjetas según un manifiesto ya emitido. Los archivos los
     * repone imageDraftPersistence desde IndexedDB; esto repone el orden, que
     * de otro modo se perdería para las imágenes guardadas (el blade las
     * vuelve a pintar en orden de base).
     *
     * Si los archivos no volvieron, los tokens `nueva:<i>` no resuelven y se
     * saltean: las guardadas igual quedan en el orden del usuario.
     */
    function applyOrder(raw) {
        if (!raw) return;

        const nuevas = cards().filter(c => !esGuardada(c));

        const enOrden = raw.split(',').map(token => {
            token = token.trim();
            if (token.startsWith('existente:')) {
                return imagenesContainer.querySelector(
                    `.imagen-card[data-imagen-id="${CSS.escape(token.slice(10))}"]`
                );
            }
            if (token.startsWith('nueva:')) {
                return nuevas[Number(token.slice(6))] || null;
            }
            return null;
        }).filter(Boolean);

        if (!enOrden.length) return;

        const resto = cards().filter(c => !enOrden.includes(c));
        [...enOrden, ...resto].forEach(c => imagenesContainer.appendChild(c));
        refrescarPortada();
    }

    // ── Submit: escribe el manifiesto ───────────────────────────

    const prodForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (prodForm) {
        prodForm.addEventListener('submit', function () {
            const tokens = [];
            let idxNueva = 0;

            cards().forEach(card => {
                if (esGuardada(card)) {
                    // Marcada para borrar: no va a existir, no ocupa posición.
                    if (estaEliminada(card)) return;
                    tokens.push('existente:' + card.dataset.imagenId);
                } else {
                    tokens.push('nueva:' + (idxNueva++));
                }
            });

            const orden = document.getElementById('imagenes-orden');
            if (orden) orden.value = tokens.join(',');

            // Respaldo para el caso de que el manifiesto no llegue: la portada
            // es la posición 0.
            const portada = document.getElementById('imagen-portada');
            if (portada) portada.value = tokens[0] || '';
        });
    }

    syncCupo();
    refrescarPortada();

    return { addFile, collectFiles, applyOrder };
}
