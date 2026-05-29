/**
 * imageManager.js
 * ─────────────────────────────────────────────────────────────
 * Gestión de imágenes en el formulario de producto:
 *   - Tarjetas de nueva imagen con previsualización
 *   - Toggle de eliminación de imágenes guardadas (edición)
 *   - Lógica de imagen portada (estrella)
 */
export function initImageManager({ cfg, iconXMark, iconArrowBack, iconStarFill, iconStarOutline }) {

    const imagenesContainer = document.getElementById('imagenes-container');
    const addImagenBtn      = document.getElementById('add-imagen-btn');
    const MAX_IMG = 5;

    let newImgCount      = 0;
    let existingImgCount = cfg.existingImgCount;
    let portadaCard        = null;
    let portadaExistenteId = cfg.portadaExistenteId ? String(cfg.portadaExistenteId) : null;

    function totalImagenes() {
        return newImgCount + existingImgCount;
    }

    function syncAddImagenBtn() {
        if (addImagenBtn) addImagenBtn.disabled = totalImagenes() >= MAX_IMG;
    }

    function addImagenRow() {
        if (totalImagenes() >= MAX_IMG) return;

        const isFirst = totalImagenes() === 0;

        const wrapper = document.createElement('div');
        wrapper.className = 'imagen-input-card';

        wrapper.innerHTML = `
            ${!isFirst ? `<button type="button" class="imagen-remove-card-btn" title="Quitar">${iconXMark}</button>` : ''}
            <button type="button" class="imagen-portada-btn d-none" title="Marcar como portada">${iconStarOutline}</button>
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
            if (portadaCard === null && portadaExistenteId === null) {
                portadaCard = wrapper;
                updatePortadaVisuals();
            }
        });

        clearBtn.addEventListener('click', () => {
            fileInput.value = '';
            previewImg.src  = '';
            previewBox.classList.add('d-none');
            dropzone.classList.remove('d-none');
            portadaBtn.classList.add('d-none');
            if (portadaCard === wrapper) {
                portadaCard = null;
                autoSelectPortadaIfOnlyOne();
            }
        });

        portadaBtn.addEventListener('click', () => {
            portadaCard = wrapper;
            portadaExistenteId = null;
            updatePortadaVisuals();
        });

        if (removeCardBtn) {
            removeCardBtn.addEventListener('click', () => {
                if (portadaCard === wrapper) portadaCard = null;
                wrapper.remove();
                newImgCount--;
                syncAddImagenBtn();
                autoSelectPortadaIfOnlyOne();
            });
        }

        imagenesContainer.appendChild(wrapper);
        newImgCount++;
        syncAddImagenBtn();
    }

    if (addImagenBtn) addImagenBtn.addEventListener('click', addImagenRow);
    if (existingImgCount === 0) addImagenRow();

    // ── Funciones globales (llamadas desde onclick en el blade) ──

    window.toggleEliminarImagen = function (id, btn) {
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
            if (String(id) === portadaExistenteId) {
                portadaExistenteId = null;
                updatePortadaVisuals();
            }
            existingImgCount--;
        } else {
            input.value    = '';
            input.disabled = true;
            card.classList.remove('marcada-eliminar');
            btn.innerHTML = iconXMark;
            btn.classList.replace('btn-secondary', 'btn-danger');
            btn.title = 'Eliminar';
            existingImgCount++;
        }
        syncAddImagenBtn();
        autoSelectPortadaIfOnlyOne();
    };

    window.setPortadaExistente = function (btn) {
        portadaExistenteId = btn.dataset.imagenId;
        portadaCard = null;
        updatePortadaVisuals();
    };

    syncAddImagenBtn();

    // ── Portada ──────────────────────────────────────────────────

    function updatePortadaVisuals() {
        document.querySelectorAll('.imagen-portada-btn[data-imagen-id]').forEach(b => {
            const active = b.dataset.imagenId === portadaExistenteId;
            b.classList.toggle('activa', active);
            b.innerHTML = active ? iconStarFill : iconStarOutline;
        });
        imagenesContainer.querySelectorAll('.imagen-input-card').forEach(card => {
            const b = card.querySelector('.imagen-portada-btn');
            if (!b) return;
            const active = card === portadaCard;
            b.classList.toggle('activa', active);
            b.innerHTML = active ? iconStarFill : iconStarOutline;
        });
    }

    function autoSelectPortadaIfOnlyOne() {
        if (portadaCard !== null || portadaExistenteId !== null) return;
        const cardsConArchivo = Array.from(imagenesContainer.querySelectorAll('.imagen-input-card'))
            .filter(c => !c.querySelector('.imagen-portada-btn')?.classList.contains('d-none'));
        const totalActivo = existingImgCount + cardsConArchivo.length;
        if (totalActivo !== 1) return;
        if (existingImgCount === 1) {
            const existCard = document.querySelector('.imagen-existente-card:not(.marcada-eliminar)');
            const btn = existCard?.querySelector('.imagen-portada-btn[data-imagen-id]');
            if (btn) { portadaExistenteId = btn.dataset.imagenId; updatePortadaVisuals(); }
        } else if (cardsConArchivo.length === 1) {
            portadaCard = cardsConArchivo[0];
            updatePortadaVisuals();
        }
    }

    // ── Submit: escribe el hidden input de portada ───────────────

    const prodForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (prodForm) {
        prodForm.addEventListener('submit', function () {
            const hiddenPortada = document.getElementById('imagen-portada');
            if (!hiddenPortada) return;
            if (portadaExistenteId) {
                hiddenPortada.value = 'existente:' + portadaExistenteId;
            } else if (portadaCard) {
                const allCards = Array.from(imagenesContainer.querySelectorAll('.imagen-input-card'))
                    .filter(c => { const inp = c.querySelector('.imagen-file-input'); return inp && inp.files && inp.files.length > 0; });
                const idx = allCards.indexOf(portadaCard);
                hiddenPortada.value = idx >= 0 ? 'nueva:' + idx : '';
            } else {
                hiddenPortada.value = '';
            }
        });
    }
}
