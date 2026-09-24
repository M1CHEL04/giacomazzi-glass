/**
 * imageManager.js
 * ─────────────────────────────────────────────────────────────
 * Gestión de imágenes en el formulario de producto:
 *   - Tarjetas de nueva imagen con previsualización
 *   - Toggle de eliminación de imágenes guardadas (edición)
 *   - Orden de la galería por arrastre, teclado o estrella
 *
 * El orden de las tarjetas dentro de #imagenes-container ES el orden que se
 * guarda, y la primera es la portada. Por eso no hay estado de portada en
 * variables: preguntarle al DOM hace imposible que lo que se ve y lo que se
 * envía se contradigan. El submit traduce ese orden a `imagenes_orden`, como
 * `existente:41,nueva:0,…`, donde `nueva:<i>` es la posición del archivo entre
 * los inputs con archivo —el mismo índice que le llega a PHP después de
 * array_filter—.
 */
export function initImageManager({ cfg, iconXMark, iconArrowBack, iconStarFill, iconStarOutline }) {

    const imagenesContainer = document.getElementById('imagenes-container');
    const addImagenBtn      = document.getElementById('add-imagen-btn');
    if (!imagenesContainer) return { addFile: () => false, collectFiles: () => [], applyOrder: () => {} };

    const MAX_IMG = cfg.maxImagenes || 5;

    // ── Estado = DOM ─────────────────────────────────────────────

    const cards         = () => Array.from(imagenesContainer.children).filter(el => el.matches('.imagen-card'));
    const esGuardada    = c => c.classList.contains('imagen-existente-card');
    const estaEliminada = c => c.classList.contains('marcada-eliminar');
    const tieneArchivo  = c => !!c.querySelector('.imagen-file-input')?.files?.length;

    /**
     * Las que van a existir después de guardar, en orden. La primera es la
     * portada. Una tarjeta nueva todavía vacía no cuenta —no produce fila—, así
     * que arrastrarla al frente no le roba la portada a la que sí tiene imagen.
     */
    const conImagen = () => cards().filter(c => esGuardada(c) ? !estaEliminada(c) : tieneArchivo(c));

    /**
     * Cupo: toda tarjeta nueva ocupa lugar aunque esté vacía (el usuario ya
     * reservó el espacio), y las guardadas sólo si no están marcadas para
     * borrar, porque esas liberan su lugar al guardar.
     */
    const cuentaParaCupo = c => !esGuardada(c) || !estaEliminada(c);

    function syncAddImagenBtn() {
        if (addImagenBtn) addImagenBtn.disabled = cards().filter(cuentaParaCupo).length >= MAX_IMG;
    }

    // ── Tarjetas de nueva imagen ─────────────────────────────────

    function addImagenRow() {
        if (cards().filter(cuentaParaCupo).length >= MAX_IMG) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'imagen-input-card imagen-card';
        wrapper.setAttribute('role', 'listitem');
        wrapper.tabIndex = 0;

        // Todas llevan botón de quitar: la vieja regla de "la primera no" era
        // posicional, y la posición ahora la mueve el usuario.
        wrapper.innerHTML = `
            <button type="button" class="imagen-remove-card-btn" title="Quitar">${iconXMark}</button>
            <button type="button" class="imagen-portada-btn d-none" title="Poner primera (portada)"
                    aria-label="Poner primera (portada)">${iconStarOutline}</button>
            <label class="imagen-dropzone">
                <input type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" class="imagen-file-input">
                <div class="imagen-dropzone-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159
                                 m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909
                                 M3 20.25h18M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Seleccionar</span>
                </div>
            </label>
            <div class="imagen-preview-box d-none">
                <img src="" alt="Vista previa" class="imagen-preview-thumb">
                <button type="button" class="imagen-clear-btn" title="Limpiar imagen">${iconArrowBack}</button>
            </div>
        `;

        const fileInput     = wrapper.querySelector('.imagen-file-input');
        const dropzone      = wrapper.querySelector('.imagen-dropzone');
        const previewBox    = wrapper.querySelector('.imagen-preview-box');
        const previewImg    = wrapper.querySelector('.imagen-preview-thumb');
        const clearBtn      = wrapper.querySelector('.imagen-clear-btn');
        const removeCardBtn = wrapper.querySelector('.imagen-remove-card-btn');
        const portadaBtn    = wrapper.querySelector('.imagen-portada-btn');

        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                dropzone.classList.add('d-none');
                previewBox.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
            portadaBtn.classList.remove('d-none');
            refrescarPortada();
        });

        clearBtn.addEventListener('click', () => {
            fileInput.value = '';
            previewImg.src  = '';
            previewBox.classList.add('d-none');
            dropzone.classList.remove('d-none');
            portadaBtn.classList.add('d-none');
            refrescarPortada();
        });

        removeCardBtn.addEventListener('click', () => {
            wrapper.remove();
            // Que el formulario nunca quede sin ninguna tarjeta.
            if (!cards().length) addImagenRow();
            syncAddImagenBtn();
            refrescarPortada();
        });

        imagenesContainer.appendChild(wrapper);
        syncAddImagenBtn();
        return wrapper;
    }

    if (addImagenBtn) addImagenBtn.addEventListener('click', () => {
        const card = addImagenRow();
        if (card) refrescarPortada();
    });

    // En alta no hay tarjetas guardadas, así que arranca con una vacía lista.
    if (!cards().length) addImagenRow();

    /** Reutiliza la primera tarjeta vacía si hay una; si no, crea una nueva. */
    function addFile(file) {
        let card = cards().find(c => {
            const inp = c.querySelector('.imagen-file-input');
            return inp && !inp.files.length;
        });

        if (!card) {
            card = addImagenRow();
            if (!card) return false; // MAX_IMG alcanzado
        }

        const input = card.querySelector('.imagen-file-input');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
        return true;
    }

    function collectFiles() {
        return Array.from(imagenesContainer.querySelectorAll('.imagen-file-input'))
            .map(input => input.files[0])
            .filter(Boolean);
    }

    // ── Borrado de imágenes guardadas ────────────────────────────

    function toggleEliminar(id, btn) {
        const input = document.getElementById('eliminar-' + id);
        const card  = document.getElementById('imagen-card-' + id);
        if (!input || !card) return;

        if (input.disabled) {
            input.value    = id;
            input.disabled = false;
            card.classList.add('marcada-eliminar');
            btn.innerHTML = iconArrowBack;
            btn.classList.replace('btn-danger', 'btn-secondary');
            btn.title = 'Deshacer';
        } else {
            input.value    = '';
            input.disabled = true;
            card.classList.remove('marcada-eliminar');
            btn.innerHTML = iconXMark;
            btn.classList.replace('btn-secondary', 'btn-danger');
            btn.title = 'Eliminar';
        }
        syncAddImagenBtn();
        refrescarPortada();
    }

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
        if (estrella && imagenesContainer.contains(estrella)) {
            const card = estrella.closest('.imagen-card');
            if (card && !estaEliminada(card)) alFrente(card);
            return;
        }

        const borrar = e.target.closest('[data-imagen-eliminar]');
        if (borrar && imagenesContainer.contains(borrar)) {
            toggleEliminar(borrar.dataset.imagenEliminar, borrar);
        }
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
            // La superficie de arrastre es la imagen. Quedan afuera los botones
            // y el dropzone vacío (que no tiene nada que ordenar).
            filter: '.marcada-eliminar, .imagen-dropzone, button',
            // Sortable lo trae en true, y eso hace preventDefault() sobre el
            // evento filtrado: rompería el click del label al selector de
            // archivos y todos los botones de la tarjeta.
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

        const nuevas = cards().filter(c => !esGuardada(c) && tieneArchivo(c));

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

        // Lo que el manifiesto no nombra (dropzones vacías) queda detrás.
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
                    // Tarjeta vacía: no viaja ningún archivo por ella, así que
                    // tampoco consume un índice de `nueva:`.
                    if (!tieneArchivo(card)) return;
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

    syncAddImagenBtn();
    refrescarPortada();

    return { addFile, collectFiles, applyOrder };
}
