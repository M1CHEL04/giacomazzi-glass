/**
 * categoriaCreator.js
 * ─────────────────────────────────────────────────────────────
 * Alta de categoría sin salir del formulario de producto.
 *
 * Inyecta un optgroup "Crear nueva" al final del select de categoría —el mismo
 * gesto que usa el select de variantes más abajo en el formulario— y al elegirlo
 * abre un modal que pide sólo el nombre. La categoría se crea por POST, y la
 * opción nueva entra en el select en su posición alfabética, seleccionada y con
 * un destello, para que se vea que quedó elegida.
 *
 * Lo usan las dos líneas: la estándar (manageVariants.js) y la a medida
 * (manageEspecial.js). El aviso de variantes del modal se decide leyendo el DOM,
 * así que no hace falta ramificar por línea.
 */

/** Valor centinela de la opción que abre el modal. No viaja nunca al servidor. */
const SENTINELA = '__nueva';

const URL_ALTA = '/uso-interno/api/categorias';

export function initCategoriaCreator() {

    const select = document.getElementById('categoria_id');
    const modalEl = document.getElementById('modalCrearCategoria');
    if (!select || !modalEl) return;

    const nombreInput  = document.getElementById('nueva-categoria-nombre');
    const feedback     = document.getElementById('nueva-categoria-feedback');
    const avisoVariantes = document.getElementById('nueva-categoria-aviso-variantes');
    const crearBtn     = document.getElementById('nueva-categoria-crear');
    const spinner      = crearBtn?.querySelector('[data-spinner]');
    const crearTexto   = crearBtn?.querySelector('[data-crear-texto]');

    if (!nombreInput || !crearBtn) return;

    const modal = new bootstrap.Modal(modalEl);

    /** Valor que tenía el select antes de abrir el modal, para poder volver. */
    let valorPrevio = select.value;
    let enVuelo = false;
    /** El modal terminó de aparecer. Ver cerrarModal(). */
    let estaMostrado = false;

    // ─── Opción que dispara el modal ─────────────────────────────
    // Se inyecta desde el JS y no desde el Blade: si el JS no carga, nadie puede
    // elegir un valor que el servidor va a rechazar.
    const grupo = document.createElement('optgroup');
    grupo.label = 'Crear nueva';
    const opcionNueva = document.createElement('option');
    opcionNueva.value = SENTINELA;
    opcionNueva.textContent = '+ Crear nueva categoría…';
    grupo.appendChild(opcionNueva);
    select.appendChild(grupo);

    // ─── Intercepción del change ─────────────────────────────────
    // En captura sobre document, no sobre el select: variantManager también
    // escucha 'change' en #categoria_id y con el centinela dispararía su
    // confirm() y un fetch a /categorias/__nueva/variantes. La fase de captura
    // en un ancestro corre antes que los listeners de fase target del select,
    // así que acá se puede frenar. Engancharlo primero en el select también
    // funcionaría —por orden de registro— pero quedaría atado al orden de los
    // init* del entrypoint.
    document.addEventListener('change', function (e) {
        if (e.target !== select) return;

        if (select.value !== SENTINELA) {
            // Cambio normal de categoría: sólo actualizar el punto de retorno.
            valorPrevio = select.value;
            return;
        }

        e.stopPropagation();

        // Volver al valor previo ya mismo: si el usuario cancela el modal, el
        // select queda como estaba y no hay centinela seleccionado en ningún
        // momento visible.
        select.value = valorPrevio;

        abrirModal();
    }, true);

    function abrirModal() {
        limpiarError();
        nombreInput.value = '';

        // Los tags de variante son la señal de que hay trabajo que se va a
        // perder. En la línea a medida no existen, así que el aviso no aparece.
        if (avisoVariantes) {
            avisoVariantes.hidden = document.querySelectorAll('.variante-tag').length === 0;
        }

        modal.show();
    }

    modalEl.addEventListener('shown.bs.modal', function () {
        estaMostrado = true;
        nombreInput.focus();
    });

    // Al cerrar, devolver el foco al select: el usuario venía de ahí.
    modalEl.addEventListener('hidden.bs.modal', function () {
        estaMostrado = false;
        select.focus();
    });

    /**
     * hide() de Bootstrap no hace nada si el modal todavía está entrando —
     * `_isTransitioning` lo bloquea sin avisar. Con Enter rápido y el alta
     * resuelta al toque, el modal quedaba abierto con la categoría ya creada.
     * Si llega antes de tiempo, se cierra apenas termina de aparecer.
     */
    function cerrarModal() {
        if (estaMostrado) {
            modal.hide();
            return;
        }
        modalEl.addEventListener('shown.bs.modal', () => modal.hide(), { once: true });
    }

    // ─── Errores ─────────────────────────────────────────────────
    function mostrarError(mensaje) {
        nombreInput.classList.add('is-invalid');
        if (feedback) {
            feedback.textContent = mensaje;
            feedback.classList.remove('d-none');
        }
    }

    function limpiarError() {
        nombreInput.classList.remove('is-invalid');
        if (feedback) {
            feedback.textContent = '';
            feedback.classList.add('d-none');
        }
    }

    nombreInput.addEventListener('input', limpiarError);

    // El modal no tiene <form>, así que el Enter se maneja acá.
    nombreInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            crear();
        }
    });

    crearBtn.addEventListener('click', crear);

    // ─── Alta ────────────────────────────────────────────────────
    function ocupado(valor) {
        enVuelo = valor;
        crearBtn.disabled = valor;
        spinner?.classList.toggle('d-none', !valor);
        if (crearTexto) crearTexto.textContent = valor ? 'Creando…' : 'Crear categoría';
    }

    function crear() {
        if (enVuelo) return;

        const nombre = nombreInput.value.trim();
        if (!nombre) {
            mostrarError('El nombre de la categoria es obligatorio.');
            nombreInput.focus();
            return;
        }

        limpiarError();
        ocupado(true);

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        fetch(URL_ALTA, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ nombre: nombre }),
        })
            .then(r => r.json().then(datos => ({ ok: r.ok, status: r.status, datos })))
            .then(({ ok, status, datos }) => {
                if (!ok) {
                    // 422 trae {message, errors}; el resto, {message} nuestro.
                    const mensaje = (status === 422 && datos.errors?.nombre?.[0])
                        || datos.message
                        || 'No se pudo crear la categoría.';
                    mostrarError(mensaje);
                    nombreInput.focus();
                    return;
                }
                seleccionarNueva(datos.id, datos.nombre);
            })
            .catch(() => {
                mostrarError('No se pudo conectar con el servidor. Probá de nuevo.');
            })
            .finally(() => ocupado(false));
    }

    // ─── Inyectar la opción y dejarla seleccionada ───────────────
    function seleccionarNueva(id, nombre) {
        insertarOpcion(id, nombre);

        select.value = String(id);
        valorPrevio = select.value;

        // El detail le dice a variantManager que no pregunte por las variantes
        // que va a descartar: el usuario ya lo vio avisado en el modal.
        select.dispatchEvent(new CustomEvent('change', {
            bubbles: true,
            detail: { categoriaNueva: true },
        }));

        cerrarModal();
        nombreInput.value = '';
        limpiarError();

        destacar();

        if (window.showToast) {
            window.showToast('Categoría "' + nombre + '" creada y seleccionada.', 'success');
        }
    }

    /**
     * Inserta la opción en su lugar alfabético. El servidor sirve el select con
     * orderBy('nombre'), así que después de esto queda igual que tras recargar.
     */
    function insertarOpcion(id, nombre) {
        const opcion = document.createElement('option');
        opcion.value = String(id);
        opcion.textContent = nombre;

        // Sólo las opciones sueltas del select: quedan fuera el placeholder de
        // value vacío y las del optgroup centinela.
        const existentes = [...select.children]
            .filter(el => el.tagName === 'OPTION' && el.value !== '');

        const siguiente = existentes.find(
            o => o.textContent.trim().localeCompare(nombre, 'es', { sensitivity: 'base' }) > 0
        );

        select.insertBefore(opcion, siguiente || grupo);
    }

    // ─── Destello ────────────────────────────────────────────────
    function destacar() {
        select.classList.remove('select-destacado');
        // Forzar reflow para poder repetir la animación en altas sucesivas.
        void select.offsetWidth;
        select.classList.add('select-destacado');

        const limpiar = () => select.classList.remove('select-destacado');
        select.addEventListener('animationend', limpiar, { once: true });
        // Red de contención si no hay animación (prefers-reduced-motion).
        setTimeout(limpiar, 2000);
    }
}
