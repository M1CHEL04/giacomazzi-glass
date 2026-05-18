/**
 * manageVariants.js
 * ─────────────────────────────────────────────────────────────
 * Gestión de imágenes y variantes en el formulario de creación
 * y edición de productos.
 *
 * REQUISITO: antes de cargar este archivo, el blade debe definir
 * el objeto global window.__prodConfig con los siguientes campos:
 *
 *   isEdit          {boolean} — true si el formulario es de edición
 *   existingImgCount {number} — cantidad de imágenes ya guardadas
 *   initialVariantes {array}  — pares variante:valor pre-cargados (edición)
 *   categoriaId     {string}  — ID de la categoría ya elegida (edición)
 *
 * ─────────────────────────────────────────────────────────────
 * ARQUITECTURA GENERAL
 * ─────────────────────────────────────────────────────────────
 * El archivo está dividido en dos módulos lógicos independientes:
 *
 *  1. MÓDULO IMÁGENES
 *     Controla la adición de nuevos inputs de imagen con
 *     previsualización al seleccionar archivo, y el toggle de
 *     eliminación de imágenes ya guardadas en modo edición.
 *
 *  2. MÓDULO VARIANTES
 *     Maneja el flujo de selección de pares variante:valor
 *     (categoría → variante → valor). Los pares elegidos se
 *     muestran como tags eliminables y se serializan en el campo
 *     hidden "variantes_json" para que el controlador los procese.
 */

document.addEventListener('DOMContentLoaded', function () {

    // ─── Leer configuración inyectada desde Blade ────────────────
    // Los datos PHP se pasan mediante el atributo data-config del
    // elemento #prod-config, renderizado en @section('script').
    // Este patrón es inmune al formatter: no hay expresiones Blade
    // dentro de etiquetas <script>.
    const prodConfigEl = document.getElementById('prod-config');
    const cfg = prodConfigEl
        ? JSON.parse(prodConfigEl.dataset.config)
        : { isEdit: false, existingImgCount: 0, initialVariantes: [], categoriaId: '' };

    // ─── Iconos SVG (renderizados server-side por Blade Heroicons) ─
    // Los elementos #tpl-icon-* son divs ocultos cuyo innerHTML
    // contiene el SVG ya compilado. JS los lee y los reutiliza
    // al construir botones dinámicamente.
    const iconXMark     = document.getElementById('tpl-icon-x-mark')?.innerHTML     || '×';
    const iconArrowBack = document.getElementById('tpl-icon-arrow-uturn-left')?.innerHTML || '↩';

    // ═════════════════════════════════════════════════════════════
    // MÓDULO 1: IMÁGENES
    // ═════════════════════════════════════════════════════════════

    const imagenesContainer = document.getElementById('imagenes-container');
    const addImagenBtn      = document.getElementById('add-imagen-btn');
    const MAX_IMG = 5;

    // newImgCount:     tarjetas de nueva imagen añadidas en esta sesión
    // existingImgCount: imágenes ya guardadas que NO están marcadas para eliminar
    let newImgCount      = 0;
    let existingImgCount = cfg.existingImgCount;

    /** Suma de imágenes activas (ya guardadas + nuevas tarjetas) */
    function totalImagenes() {
        return newImgCount + existingImgCount;
    }

    /** Deshabilita el botón "Agregar imagen" cuando se alcanza el límite */
    function syncAddImagenBtn() {
        if (addImagenBtn) addImagenBtn.disabled = totalImagenes() >= MAX_IMG;
    }

    /**
     * Crea una tarjeta de carga de imagen.
     *
     * Cada tarjeta tiene DOS botones opcionales:
     *   - Limpiar imagen (↩, top-left, gris): solo visible cuando hay preview.
     *     Descarta el archivo seleccionado y vuelve al estado vacío.
     *   - Quitar tarjeta (×, top-right, rojo): elimina el input completo.
     *     NO aparece en la primera tarjeta (debe haber siempre al menos una).
     */
    function addImagenRow() {
        if (totalImagenes() >= MAX_IMG) return;

        const isFirst = newImgCount === 0;

        const wrapper = document.createElement('div');
        wrapper.className = 'imagen-input-card';

        wrapper.innerHTML = `
            ${!isFirst ? `<button type="button" class="imagen-remove-card-btn" title="Quitar">${iconXMark}</button>` : ''}
            <label class="imagen-dropzone">
                <input type="file" name="imagenes[]" accept="image/*" class="imagen-file-input">
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

        // Al seleccionar archivo: leer con FileReader y mostrar preview
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                dropzone.classList.add('d-none');
                previewBox.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });

        // Limpiar imagen: descarta el archivo y vuelve al estado vacío
        clearBtn.addEventListener('click', () => {
            fileInput.value = '';
            previewImg.src  = '';
            previewBox.classList.add('d-none');
            dropzone.classList.remove('d-none');
        });

        // Quitar tarjeta: elimina el input completo (ausente en la primera tarjeta)
        if (removeCardBtn) {
            removeCardBtn.addEventListener('click', () => {
                wrapper.remove();
                newImgCount--;
                syncAddImagenBtn();
            });
        }

        imagenesContainer.appendChild(wrapper);
        newImgCount++;
        syncAddImagenBtn();
    }

    if (addImagenBtn) addImagenBtn.addEventListener('click', addImagenRow);

    // Mostrar siempre al menos una tarjeta vacía al iniciar la página
    addImagenRow();

    /**
     * Alterna el estado "marcada para eliminar" de una imagen guardada.
     * Se llama desde el atributo onclick del botón × en cada thumbnail.
     *
     * Cuando se marca:  habilita el hidden input "imagenes_eliminar[]"
     *                   con el ID de la imagen → el servidor la borrará.
     * Cuando se desmarca: deshabilita el input (Laravel ignora campos
     *                     disabled en la request) y restaura la tarjeta.
     */
    window.toggleEliminarImagen = function (id, btn) {
        const input = document.getElementById('eliminar-' + id);
        const card  = document.getElementById('imagen-card-' + id);
        if (!input || !card) return;

        if (input.disabled) {
            // Marcar para eliminar
            input.value    = id;
            input.disabled = false;
            card.classList.add('marcada-eliminar');
            btn.innerHTML = iconArrowBack;
            btn.classList.replace('btn-danger', 'btn-secondary');
            btn.title = 'Deshacer';
            existingImgCount--;
        } else {
            // Desmarcar
            input.value    = '';
            input.disabled = true;
            card.classList.remove('marcada-eliminar');
            btn.innerHTML = iconXMark;
            btn.classList.replace('btn-secondary', 'btn-danger');
            btn.title = 'Eliminar';
            existingImgCount++;
        }
        syncAddImagenBtn();
    };

    syncAddImagenBtn();

    // ═════════════════════════════════════════════════════════════
    // MÓDULO 2: VARIANTES
    // ═════════════════════════════════════════════════════════════
    //
    // Flujo completo de selección:
    //
    //  categoriaSelect (change)
    //    └─ loadVariantes(id) [fetch AJAX]
    //         └─ buildVarianteSelect(data)
    //              └─ varianteSelect (change)
    //                   ├─ si value === 'nueva'  → muestra nuevaVarianteSection
    //                   └─ si value !== ''       → carga valores en valorSelect
    //                        └─ valorSelect (change)
    //                             └─ si value === 'nuevo' → muestra nuevoValorSection
    //
    //  addVarianteBtn (click)
    //    └─ construye objeto `item` según el caso:
    //         'existente'     : par ya registrado en la BD
    //         'nuevo_valor'   : valor nuevo para variante existente
    //         'nueva_variante': variante + valor completamente nuevos
    //    └─ renderVarianteTag(item)  → agrega el tag visual
    //    └─ syncVariantesJson()      → actualiza el hidden input
    //
    // Al enviar el formulario, el controlador lee `variantes_json`
    // y llama a `procesarVariantes()` que maneja los tres casos.

    const categoriaSelect      = document.getElementById('categoria_id');
    const variantesAlert       = document.getElementById('variantes-alert');
    const variantesSection     = document.getElementById('variantes-section');
    const varianteSelect       = document.getElementById('variante-select');
    const valorExistingSection = document.getElementById('valor-existing-section');
    const valorSelect          = document.getElementById('valor-select');
    const nuevoValorSection    = document.getElementById('nuevo-valor-section');
    const nuevoValorInput      = document.getElementById('nuevo-valor-input');
    const nuevaVarianteSection = document.getElementById('nueva-variante-section');
    const nuevaVarianteNombre  = document.getElementById('nueva-variante-nombre');
    const nuevaVarianteValor   = document.getElementById('nueva-variante-valor');
    const addVarianteBtn       = document.getElementById('add-variante-btn');
    const variantesLista       = document.getElementById('variantes-lista');
    const variantesJsonInput   = document.getElementById('variantes-json');
    const variantesEmptyMsg    = document.getElementById('variantes-empty-msg');

    /** Pares variante:valor seleccionados para este producto */
    let variantesData    = [];
    /** Variantes disponibles de la categoría actualmente seleccionada */
    let currentVariantes = [];

    // En modo edición, pre-poblar los pares ya guardados.
    // cfg.initialVariantes viene del controlador editProducto()
    // y contiene objetos {tipo, ..., display, _lid}.
    cfg.initialVariantes.forEach(item => {
        variantesData.push(item);
        renderVarianteTag(item);
    });
    syncVariantesJson();
    updateEmptyMsg();

    // ─── AJAX: obtener variantes de una categoría ────────────────
    function loadVariantes(categoriaId) {
        return fetch(`/uso-interno/api/categorias/${categoriaId}/variantes`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        });
    }

    /** Rellena el <select> de variantes con la respuesta AJAX */
    function buildVarianteSelect(data) {
        currentVariantes = data;
        varianteSelect.innerHTML = '<option value="" disabled selected>Seleccioná una variante...</option>';
        data.forEach(v => {
            const o = document.createElement('option');
            o.value       = v.id;
            o.textContent = v.nombre;
            varianteSelect.appendChild(o);
        });
        const grp = document.createElement('optgroup');
        grp.label = 'Crear nuevo';
        const nw = document.createElement('option');
        nw.value       = 'nueva';
        nw.textContent = 'Crear nueva variante';
        grp.appendChild(nw);
        varianteSelect.appendChild(grp);
    }

    // ─── Evento: cambio de categoría ────────────────────────────
    categoriaSelect.addEventListener('change', function () {
        const id = this.value;
        resetAddForm();
        if (!id) {
            variantesAlert.classList.remove('d-none');
            variantesSection.classList.add('d-none');
            return;
        }
        loadVariantes(id)
            .then(data => buildVarianteSelect(data))
            .catch(() => buildVarianteSelect([]))
            .finally(() => {
                variantesAlert.classList.add('d-none');
                variantesSection.classList.remove('d-none');
            });
    });

    // ─── Evento: cambio de variante seleccionada ────────────────
    varianteSelect.addEventListener('change', function () {
        valorExistingSection.classList.add('d-none');
        nuevoValorSection.classList.add('d-none');
        nuevaVarianteSection.classList.add('d-none');

        if (this.value === 'nueva') {
            nuevaVarianteSection.classList.remove('d-none');
        } else if (this.value) {
            const v = currentVariantes.find(x => String(x.id) === this.value);
            if (v) {
                valorSelect.innerHTML = '<option value="" disabled selected>Seleccioná un valor...</option>';
                v.valores.forEach(vl => {
                    const o = document.createElement('option');
                    o.value       = vl.id;
                    o.textContent = vl.valor;
                    valorSelect.appendChild(o);
                });
                const grp = document.createElement('optgroup');
                grp.label = 'Crear nuevo';
                const nw = document.createElement('option');
                nw.value       = 'nuevo';
                nw.textContent = 'Crear nuevo valor';
                grp.appendChild(nw);
                valorSelect.appendChild(grp);
                valorExistingSection.classList.remove('d-none');
            }
        }
    });

    // ─── Evento: cambio de valor en select de valores ───────────
    valorSelect.addEventListener('change', function () {
        nuevoValorSection.classList.toggle('d-none', this.value !== 'nuevo');
        if (this.value === 'nuevo') nuevoValorInput.value = '';
    });

    // ─── Evento: clic en "Agregar" variante ─────────────────────
    addVarianteBtn.addEventListener('click', function () {
        const vVal = varianteSelect.value;
        if (!vVal) { markInvalid(varianteSelect); return; }
        clearInvalid(varianteSelect);

        let item = null;

        if (vVal === 'nueva') {
            // ── Caso: variante completamente nueva ──────────────
            const nombre = nuevaVarianteNombre.value.trim();
            const valor  = nuevaVarianteValor.value.trim();
            if (!nombre) { markInvalid(nuevaVarianteNombre); return; }
            clearInvalid(nuevaVarianteNombre);
            if (!valor)  { markInvalid(nuevaVarianteValor);  return; }
            clearInvalid(nuevaVarianteValor);
            item = {
                tipo: 'nueva_variante',
                variante_nombre: nombre,
                valor,
                display: nombre + ': ' + valor,
                _lid: uid()
            };
        } else {
            const valVal = valorSelect.value;
            if (!valVal) { markInvalid(valorSelect); return; }
            clearInvalid(valorSelect);

            const variante    = currentVariantes.find(x => String(x.id) === vVal);
            const varianteNom = variante ? variante.nombre : 'Variante';

            if (valVal === 'nuevo') {
                // ── Caso: valor nuevo para variante existente ───
                const nv = nuevoValorInput.value.trim();
                if (!nv) { markInvalid(nuevoValorInput); return; }
                clearInvalid(nuevoValorInput);
                item = {
                    tipo: 'nuevo_valor',
                    variante_id: parseInt(vVal),
                    valor: nv,
                    display: varianteNom + ': ' + nv,
                    _lid: uid()
                };
            } else {
                // ── Caso: par existente — evitar duplicados ─────
                if (variantesData.some(x => x.tipo === 'existente' && String(x.valor_variante_id) === valVal)) {
                    markInvalid(valorSelect);
                    return;
                }
                const valObj = variante ? variante.valores.find(vl => String(vl.id) === valVal) : null;
                item = {
                    tipo: 'existente',
                    valor_variante_id: parseInt(valVal),
                    display: varianteNom + ': ' + (valObj ? valObj.valor : valVal),
                    _lid: uid()
                };
            }
        }

        variantesData.push(item);
        renderVarianteTag(item);
        syncVariantesJson();
        updateEmptyMsg();
        resetAddForm();
    });

    /**
     * Renderiza un tag visual para un par variante:valor.
     *
     * Estructura del tag:
     *   [NombreVariante] : [Valor]  ×
     *
     * El botón × filtra el item de variantesData por _lid
     * (ID local, solo existe en el navegador, nunca se envía al servidor)
     * y actualiza el hidden input.
     */
    function renderVarianteTag(item) {
        const colonIdx  = item.display.indexOf(': ');
        const labelText = colonIdx !== -1 ? item.display.slice(0, colonIdx) : item.display;
        const valueText = colonIdx !== -1 ? item.display.slice(colonIdx + 2) : '';

        const tag = document.createElement('div');
        tag.className = 'variante-tag';
        tag.setAttribute('data-lid', item._lid);
        tag.innerHTML = `
            <span class="variante-tag-label">${esc(labelText)}</span>
            <span class="variante-tag-sep">:</span>
            <span class="variante-tag-value">${esc(valueText)}</span>
            <button type="button" class="variante-tag-remove" aria-label="Quitar">${iconXMark}</button>
        `;
        tag.querySelector('.variante-tag-remove').addEventListener('click', () => {
            variantesData = variantesData.filter(x => x._lid !== item._lid);
            tag.remove();
            syncVariantesJson();
            updateEmptyMsg();
        });
        variantesLista.appendChild(tag);
    }

    /** Muestra u oculta el mensaje "sin variantes agregadas" */
    function updateEmptyMsg() {
        if (!variantesEmptyMsg) return;
        variantesEmptyMsg.classList.toggle('d-none', variantesData.length > 0);
    }

    /**
     * Serializa variantesData al input hidden "variantes_json".
     * Omite los campos internos _lid y display que solo sirven
     * para la gestión en el cliente; el servidor no los necesita.
     */
    function syncVariantesJson() {
        const payload = variantesData.map(({ _lid, display, ...rest }) => rest);
        variantesJsonInput.value = JSON.stringify(payload);
    }

    /** Resetea todos los inputs del sub-formulario de agregar variante */
    function resetAddForm() {
        varianteSelect.value = '';
        [valorExistingSection, nuevoValorSection, nuevaVarianteSection]
            .forEach(el => el.classList.add('d-none'));
        [varianteSelect, valorSelect, nuevoValorInput, nuevaVarianteNombre, nuevaVarianteValor]
            .forEach(el => {
                el.classList.remove('is-invalid');
                if (el.tagName === 'INPUT') el.value = '';
            });
    }

    function markInvalid(el)  { el.classList.add('is-invalid'); }
    function clearInvalid(el) { el.classList.remove('is-invalid'); }

    /** ID local único para cada par; nunca se envía al servidor */
    function uid() {
        return Date.now() + '_' + Math.random().toString(36).slice(2);
    }

    /** Escapa HTML para prevenir XSS al insertar texto dinámico en innerHTML */
    function esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // En modo edición, cargar las variantes de la categoría ya seleccionada
    // para que el select de variantes quede listo sin que el usuario
    // tenga que cambiar la categoría manualmente.
    if (cfg.isEdit && cfg.categoriaId) {
        loadVariantes(cfg.categoriaId)
            .then(data => buildVarianteSelect(data))
            .catch(() => buildVarianteSelect([]));
    }
});
