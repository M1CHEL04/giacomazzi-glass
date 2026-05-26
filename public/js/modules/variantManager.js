/**
 * variantManager.js
 * ─────────────────────────────────────────────────────────────
 * Gestión de variantes y SKUs en el formulario de producto.
 *
 * Flujo de selección:
 *   categoría → variante → valor → [código] → Agregar
 *
 * Estructura de ítem en variantesData:
 *   tipo='existente':      { tipo, valor_variante_id, variante_id, codigo, display, _lid }
 *   tipo='nuevo_valor':    { tipo, variante_id, valor, codigo, display, _lid }
 *   tipo='nueva_variante': { tipo, variante_nombre, valor, codigo, display, _lid }
 *
 * Los ítems con el mismo variante_id (o variante_nombre) son
 * alternativas del mismo atributo; el producto cartesiano de
 * todos los grupos forma las combinaciones de SKU.
 *
 * SKU = producto.codigo + '-' + codigo_valor1 + '-' + codigo_valor2 + …
 */
export function initVariantManager({ cfg, iconXMark }) {

    const categoriaSelect      = document.getElementById('categoria_id');
    const variantesAlert       = document.getElementById('variantes-alert');
    const variantesSection     = document.getElementById('variantes-section');
    const varianteSelect       = document.getElementById('variante-select');
    const valorExistingSection = document.getElementById('valor-existing-section');
    const valorSelect          = document.getElementById('valor-select');
    const valorCodigoSection   = document.getElementById('valor-codigo-section');
    const valorCodigoInput     = document.getElementById('valor-codigo-input');
    const nuevoValorSection    = document.getElementById('nuevo-valor-section');
    const nuevoValorInput      = document.getElementById('nuevo-valor-input');
    const nuevoValorCodigo     = document.getElementById('nuevo-valor-codigo');
    const nuevaVarianteSection = document.getElementById('nueva-variante-section');
    const nuevaVarianteNombre  = document.getElementById('nueva-variante-nombre');
    const nuevaVarianteValor   = document.getElementById('nueva-variante-valor');
    const nuevaVarianteCodigo  = document.getElementById('nueva-variante-codigo');
    const addVarianteBtn       = document.getElementById('add-variante-btn');
    const variantesLista       = document.getElementById('variantes-lista');
    const variantesJsonInput   = document.getElementById('variantes-json');
    const variantesEmptyMsg    = document.getElementById('variantes-empty-msg');
    const skuPreview           = document.getElementById('sku-preview');
    const skuCombinationsList  = document.getElementById('sku-combinations-list');
    const codigoInput          = document.getElementById('codigo');

    let variantesData    = [];
    let currentVariantes = [];
    let nuevoValorCodigoManual    = false;
    let nuevaVarianteCodigoManual = false;
    let pendingLocalVarianteName  = null;   // nombre de variante pendiente seleccionada en el dropdown

    // ─── Pre-poblar en modo edición ──────────────────────────────
    cfg.initialVariantes.forEach(item => {
        variantesData.push(item);
        renderVarianteTag(item);
    });
    syncVariantesJson();
    updateEmptyMsg();
    updateSkuPreview();

    // ─── Escuchar cambios en el código base del producto ─────────
    if (codigoInput) {
        codigoInput.addEventListener('input', updateSkuPreview);
    }

    // ─── AJAX: variantes de una categoría ────────────────────────
    function loadVariantes(categoriaId) {
        return fetch(`/uso-interno/api/categorias/${categoriaId}/variantes`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        });
    }

    /** Rellena el <select> de variantes; almacena 'codigo' en dataset de cada opción de valor */
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
        injectPendingVariantes();
    }

    /**
     * Inserta/actualiza en varianteSelect una opción por cada variante "nueva" pendiente
     * (aún no guardada en BD). Permite agregar más valores sin reingresar el nombre.
     * Debe llamarse cada vez que variantesData cambie.
     */
    function injectPendingVariantes() {
        // Quitar las opciones pendientes anteriores para re-calcularlas
        varianteSelect.querySelectorAll('option[data-local]').forEach(o => o.remove());

        const pendingNames = [...new Set(
            variantesData
                .filter(x => x.tipo === 'nueva_variante')
                .map(x => x.variante_nombre)
        )];

        if (pendingNames.length === 0) return;

        const crearGrp = [...varianteSelect.querySelectorAll('optgroup')]
            .find(g => g.label === 'Crear nuevo');

        pendingNames.forEach(nombre => {
            const o = document.createElement('option');
            o.value = 'local:' + nombre;
            o.textContent = nombre;
            o.setAttribute('data-local', '1');
            varianteSelect.insertBefore(o, crearGrp || null);
        });
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
        [valorExistingSection, valorCodigoSection, nuevoValorSection, nuevaVarianteSection]
            .forEach(el => el?.classList.add('d-none'));

        if (this.value === 'nueva') {
            nuevaVarianteNombre.value = '';
            nuevaVarianteValor.value  = '';
            if (nuevaVarianteCodigo) { nuevaVarianteCodigo.value = ''; }
            nuevaVarianteCodigoManual = false;
            nuevaVarianteSection.classList.remove('d-none');
        } else if (this.value.startsWith('local:')) {
            // Variante nueva pendiente: solo permite agregar un nuevo valor
            pendingLocalVarianteName = this.value.slice('local:'.length);
            if (nuevoValorInput)  nuevoValorInput.value  = '';
            if (nuevoValorCodigo) nuevoValorCodigo.value = '';
            nuevoValorCodigoManual = false;
            nuevoValorSection?.classList.remove('d-none');
        } else if (this.value) {
            const v = currentVariantes.find(x => String(x.id) === this.value);
            if (v) {
                valorSelect.innerHTML = '<option value="" disabled selected>Seleccioná un valor...</option>';
                v.valores.forEach(vl => {
                    const o = document.createElement('option');
                    o.value          = vl.id;
                    o.textContent    = vl.valor;
                    o.dataset.codigo = vl.codigo;
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

    // ─── Evento: cambio de valor seleccionado ────────────────────
    valorSelect.addEventListener('change', function () {
        const isNuevo = this.value === 'nuevo';
        nuevoValorSection?.classList.toggle('d-none', !isNuevo);
        valorCodigoSection?.classList.toggle('d-none', isNuevo || !this.value);

        if (isNuevo) {
            if (nuevoValorInput)  nuevoValorInput.value  = '';
            if (nuevoValorCodigo) nuevoValorCodigo.value = '';
            nuevoValorCodigoManual = false;
        } else if (this.value) {
            // Pre-rellenar el código editable del valor existente
            const selectedOpt = this.options[this.selectedIndex];
            if (valorCodigoInput) {
                valorCodigoInput.value = selectedOpt?.dataset.codigo || '';
            }
        }
    });

    // ─── Auto-fill de código por consonantes ────────────────────
    if (nuevoValorInput && nuevoValorCodigo) {
        nuevoValorInput.addEventListener('input', function () {
            if (!nuevoValorCodigoManual) {
                nuevoValorCodigo.value = sugerirCodigo(this.value);
            }
        });
        nuevoValorCodigo.addEventListener('input', function () {
            nuevoValorCodigoManual = true;
            this.value = this.value.toUpperCase();
        });
    }

    if (nuevaVarianteValor && nuevaVarianteCodigo) {
        nuevaVarianteValor.addEventListener('input', function () {
            if (!nuevaVarianteCodigoManual) {
                nuevaVarianteCodigo.value = sugerirCodigo(this.value);
            }
        });
        nuevaVarianteCodigo.addEventListener('input', function () {
            nuevaVarianteCodigoManual = true;
            this.value = this.value.toUpperCase();
        });
    }

    // ─── Evento: clic en "Agregar" variante ─────────────────────
    addVarianteBtn.addEventListener('click', function () {
        const vVal = varianteSelect.value;
        if (!vVal) { markInvalid(varianteSelect); return; }
        clearInvalid(varianteSelect);

        let item = null;

        if (vVal === 'nueva') {
            // ── Variante completamente nueva ──────────────────────
            const nombre = nuevaVarianteNombre.value.trim();
            const valor  = nuevaVarianteValor.value.trim();
            if (!nombre) { markInvalid(nuevaVarianteNombre); return; }
            clearInvalid(nuevaVarianteNombre);
            if (!valor)  { markInvalid(nuevaVarianteValor);  return; }
            clearInvalid(nuevaVarianteValor);

            // Evitar nombre duplicado (ya pendiente o ya en BD)
            if (variantesData.some(x => x.tipo === 'nueva_variante'
                    && x.variante_nombre.toLowerCase() === nombre.toLowerCase())) {
                markError(nuevaVarianteNombre, 'Esta variante ya fue agregada al producto.');
                return;
            }
            if (currentVariantes.some(x => x.nombre.toLowerCase() === nombre.toLowerCase())) {
                markError(nuevaVarianteNombre, 'Esta variante ya existe en la categoría. Seleccionala desde el listado.');
                return;
            }

            const codigo = nuevaVarianteCodigo?.value.trim().toUpperCase() || sugerirCodigo(valor);
            item = {
                tipo: 'nueva_variante',
                variante_nombre: nombre,
                valor,
                codigo,
                display: nombre + ': ' + valor,
                _lid: uid()
            };

        } else if (vVal.startsWith('local:')) {
            // ── Nuevo valor para variante pendiente (no guardada) ─
            const nombre = vVal.slice('local:'.length);
            const nv = nuevoValorInput.value.trim();
            if (!nv) { markInvalid(nuevoValorInput); return; }
            clearInvalid(nuevoValorInput);

            // Evitar valor duplicado en esta variante pendiente
            if (variantesData.some(x => x.tipo === 'nueva_variante'
                    && x.variante_nombre.toLowerCase() === nombre.toLowerCase()
                    && x.valor.toLowerCase() === nv.toLowerCase())) {
                markError(nuevoValorInput, 'Este valor ya fue agregado a esta variante.');
                return;
            }

            const codigo = nuevoValorCodigo?.value.trim().toUpperCase() || sugerirCodigo(nv);
            item = {
                tipo: 'nueva_variante',
                variante_nombre: nombre,
                valor: nv,
                codigo,
                display: nombre + ': ' + nv,
                _lid: uid()
            };

        } else {
            const valVal = valorSelect.value;
            if (!valVal) { markInvalid(valorSelect); return; }
            clearInvalid(valorSelect);

            const variante    = currentVariantes.find(x => String(x.id) === vVal);
            const varianteNom = variante ? variante.nombre : 'Variante';

            if (valVal === 'nuevo') {
                // ── Valor nuevo para variante existente ───────────
                const nv = nuevoValorInput.value.trim();
                if (!nv) { markInvalid(nuevoValorInput); return; }
                clearInvalid(nuevoValorInput);

                // Evitar valor duplicado en esta variante
                if (variantesData.some(x => x.variante_id === parseInt(vVal)
                        && x.valor?.toLowerCase() === nv.toLowerCase())) {
                    markError(nuevoValorInput, 'Este valor ya fue agregado a esta variante.');
                    return;
                }

                const codigo = nuevoValorCodigo?.value.trim().toUpperCase() || sugerirCodigo(nv);
                item = {
                    tipo: 'nuevo_valor',
                    variante_id: parseInt(vVal),
                    valor: nv,
                    codigo,
                    display: varianteNom + ': ' + nv,
                    _lid: uid()
                };
            } else {
                // ── Par existente — evitar duplicar el mismo valor ─
                if (variantesData.some(x => x.tipo === 'existente' && String(x.valor_variante_id) === valVal)) {
                    markInvalid(valorSelect);
                    return;
                }
                const valObj  = variante?.valores.find(vl => String(vl.id) === valVal);
                // Usar el código editado por el usuario en valor-codigo-input,
                // o el código de la BD, o generarlo desde el texto del valor.
                const codigo  = (valorCodigoInput?.value.trim() || valObj?.codigo || sugerirCodigo(valObj?.valor || '')).toUpperCase();
                item = {
                    tipo: 'existente',
                    valor_variante_id: parseInt(valVal),
                    variante_id: parseInt(vVal),
                    codigo,
                    display: varianteNom + ': ' + (valObj ? valObj.valor : valVal),
                    _lid: uid()
                };
            }
        }

        variantesData.push(item);
        renderVarianteTag(item);
        injectPendingVariantes();
        syncVariantesJson();
        updateEmptyMsg();
        updateSkuPreview();
        resetAddForm();
    });

    // ─── Renderiza el tag visual de un par variante:valor ───────
    //
    // Estructura del tag:
    //   [NombreVariante] : [Valor]  [CODIGO]  ×
    //
    // El input de código es editable inline. Al cambiar:
    //   → actualiza variantesData[_lid].codigo
    //   → re-serializa variantes_json
    //   → recalcula el preview de SKUs
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
            <span class="variante-tag-code-wrap" title="Código del valor (editable)">
                <input type="text" class="variante-tag-code-input"
                       value="${esc(item.codigo || '')}"
                       maxlength="10" placeholder="—">
            </span>
            <button type="button" class="variante-tag-remove" aria-label="Quitar">${iconXMark}</button>
        `;

        const codeInput = tag.querySelector('.variante-tag-code-input');
        codeInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
            const found = variantesData.find(x => x._lid === item._lid);
            if (found) found.codigo = this.value.trim();
            syncVariantesJson();
            updateSkuPreview();
        });

        tag.querySelector('.variante-tag-remove').addEventListener('click', () => {
            variantesData = variantesData.filter(x => x._lid !== item._lid);
            tag.remove();
            injectPendingVariantes();
            syncVariantesJson();
            updateEmptyMsg();
            updateSkuPreview();
        });

        variantesLista.appendChild(tag);
    }

    // ─── Preview de SKUs ─────────────────────────────────────────
    //
    // Agrupa los ítems por variante (existente/nuevo_valor → variante_id,
    // nueva_variante → variante_nombre). Calcula el producto cartesiano
    // de los grupos y muestra cada combinación como un badge de SKU.

    /** Primera consonante (mayúscula) del nombre de la variante del ítem. */
    function getVariantePrefix(item) {
        let nombre = '';
        if (item.tipo === 'nueva_variante') {
            nombre = item.variante_nombre || '';
        } else {
            const v = currentVariantes.find(x => x.id === item.variante_id);
            nombre = v ? v.nombre : '';
        }
        const s = nombre.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
        const m = s.match(/[BCDFGHJKLMNPQRSTVWXYZ]/);
        return m ? m[0] : (s[0] || '');
    }

    function updateSkuPreview() {
        if (!skuPreview || !skuCombinationsList) return;

        if (variantesData.length === 0) {
            skuPreview.classList.add('d-none');
            return;
        }

        const codigoBase = codigoInput?.value.trim() || '???';

        const grupos = {};
        variantesData.forEach(item => {
            const key = item.tipo === 'nueva_variante'
                ? 'nv:' + item.variante_nombre
                : 'v:'  + item.variante_id;
            if (!grupos[key]) grupos[key] = [];
            grupos[key].push(item);
        });

        let combos = [[]];
        for (const grupo of Object.values(grupos)) {
            combos = combos.flatMap(combo => grupo.map(item => [...combo, item]));
        }

        skuCombinationsList.innerHTML = combos.map(combo => {
            const sku = codigoBase.toUpperCase()
                + combo.map(item => '-' + getVariantePrefix(item) + (item.codigo || '??').toUpperCase()).join('');
            return `<code class="sku-preview-item">${esc(sku)}</code>`;
        }).join('');

        skuPreview.classList.remove('d-none');
    }

    function updateEmptyMsg() {
        if (!variantesEmptyMsg) return;
        variantesEmptyMsg.classList.toggle('d-none', variantesData.length > 0);
    }

    /**
     * Serializa variantesData al input hidden "variantes_json".
     * Omite _lid y display (solo uso del cliente).
     */
    function syncVariantesJson() {
        const payload = variantesData.map(({ _lid, display, ...rest }) => rest);
        variantesJsonInput.value = JSON.stringify(payload);
    }

    function resetAddForm() {
        varianteSelect.value = '';
        pendingLocalVarianteName = null;
        [valorExistingSection, valorCodigoSection, nuevoValorSection, nuevaVarianteSection]
            .forEach(el => el?.classList.add('d-none'));
        [varianteSelect, valorSelect, nuevoValorInput, nuevaVarianteNombre,
         nuevaVarianteValor, nuevoValorCodigo, nuevaVarianteCodigo, valorCodigoInput]
            .forEach(el => {
                if (!el) return;
                clearInvalid(el);
                if (el.tagName === 'INPUT') el.value = '';
            });
        nuevoValorCodigoManual    = false;
        nuevaVarianteCodigoManual = false;
    }

    function markInvalid(el)  { el?.classList.add('is-invalid'); }
    function markError(el, msg) {
        if (!el) return;
        el.classList.add('is-invalid');
        const fb = document.getElementById(el.id + '-feedback');
        if (fb) {
            fb.textContent = msg;
            fb.classList.remove('d-none');
        }
    }
    function clearInvalid(el) {
        if (!el) return;
        el.classList.remove('is-invalid');
        const fb = document.getElementById(el.id + '-feedback');
        if (fb) {
            fb.textContent = '';
            fb.classList.add('d-none');
        }
    }

    function uid() {
        return Date.now() + '_' + Math.random().toString(36).slice(2);
    }

    /**
     * Sugiere un código de 2-4 caracteres a partir del texto del valor.
     * Extrae consonantes (sin tildes); si hay al menos 2, usa las primeras 4.
     * Si no, usa los primeros 4 caracteres alfanuméricos.
     */
    function sugerirCodigo(texto) {
        const s = texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
        const consonantes = s.replace(/[^BCDFGHJKLMNPQRSTVWXYZ]/g, '');
        if (consonantes.length >= 2) return consonantes.slice(0, 4);
        return s.replace(/[^A-Z0-9]/g, '').slice(0, 4);
    }

    function esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ─── Cargar variantes en modo edición ────────────────────────
    if (cfg.isEdit && cfg.categoriaId) {
        loadVariantes(cfg.categoriaId)
            .then(data => buildVarianteSelect(data))
            .catch(() => buildVarianteSelect([]));
    }
}
