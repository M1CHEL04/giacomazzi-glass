/**
 * tecnicaImageManager.js  —  imágenes técnicas del formulario de producto
 * ─────────────────────────────────────────────────────────────
 * Mismo gesto que imageManager.js (tarjeta con dropzone, preview, limpiar,
 * quitar, y baja lógica de las ya guardadas) pero sin nada de portada: una
 * imagen técnica nunca es principal, así que acá no hay estrella ni hidden
 * de portada que escribir al enviar.
 *
 * Va en un módulo aparte en lugar de parametrizar imageManager.js porque
 * ese archivo tiene la lógica de portada entretejida en casi todos sus
 * caminos (portadaCard, portadaExistenteId, updatePortadaVisuals, el
 * listener de submit); un modo "sin portada" ahí sería más frágil que
 * este archivo, que reutiliza las mismas clases CSS de producto.css.
 */
export function initTecnicaImageManager({ cfg, iconXMark, iconArrowBack }) {

    const container = document.getElementById('tecnicas-container');
    const addBtn = document.getElementById('add-imagen-tecnica-btn');

    if (!container) return { addFile: () => false, collectFiles: () => [] };

    const MAX_TECNICAS = 5;

    let nuevasCount = 0;
    let existentesCount = cfg.existingTecnicasCount || 0;

    function total() {
        return nuevasCount + existentesCount;
    }

    function syncAddBtn() {
        if (addBtn) addBtn.disabled = total() >= MAX_TECNICAS;
    }

    function addTecnicaRow() {
        if (total() >= MAX_TECNICAS) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'imagen-input-card';

        wrapper.innerHTML = `
            <button type="button" class="imagen-remove-card-btn" title="Quitar">${iconXMark}</button>
            <label class="imagen-dropzone">
                <input type="file" name="imagenes_tecnicas[]" accept="image/jpeg,image/png,image/webp" class="imagen-file-input">
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

        const fileInput = wrapper.querySelector('.imagen-file-input');
        const dropzone = wrapper.querySelector('.imagen-dropzone');
        const previewBox = wrapper.querySelector('.imagen-preview-box');
        const previewImg = wrapper.querySelector('.imagen-preview-thumb');
        const clearBtn = wrapper.querySelector('.imagen-clear-btn');
        const removeBtn = wrapper.querySelector('.imagen-remove-card-btn');

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
        });

        clearBtn.addEventListener('click', () => {
            fileInput.value = '';
            previewImg.src = '';
            previewBox.classList.add('d-none');
            dropzone.classList.remove('d-none');
        });

        removeBtn.addEventListener('click', () => {
            wrapper.remove();
            nuevasCount--;
            syncAddBtn();
        });

        container.appendChild(wrapper);
        nuevasCount++;
        syncAddBtn();
    }

    if (addBtn) addBtn.addEventListener('click', addTecnicaRow);

    /** Reutiliza la primera tarjeta vacía si hay una; si no, crea una nueva. */
    function addFile(file) {
        const cardsAntes = container.querySelectorAll('.imagen-input-card');
        let card = Array.from(cardsAntes).find(c => !c.querySelector('.imagen-file-input').files.length);

        if (!card) {
            addTecnicaRow();
            const cardsDespues = container.querySelectorAll('.imagen-input-card');
            if (cardsDespues.length === cardsAntes.length) return false; // MAX_TECNICAS alcanzado
            card = cardsDespues[cardsDespues.length - 1];
        }

        const input = card.querySelector('.imagen-file-input');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
        return true;
    }

    function collectFiles() {
        return Array.from(container.querySelectorAll('.imagen-file-input'))
            .map(input => input.files[0])
            .filter(Boolean);
    }

    // ── Baja de las técnicas ya guardadas (solo edición) ─────────
    // Delegado: el botón vive en el blade y no se vuelve a dibujar, pero
    // así queda un solo listener en vez de uno por tarjeta.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-tecnica-eliminar]');
        if (!btn) return;

        const id = btn.dataset.tecnicaEliminar;
        const input = document.getElementById('eliminar-tecnica-' + id);
        const card = document.getElementById('tecnica-card-' + id);
        if (!input || !card) return;

        if (input.disabled) {
            input.value = id;
            input.disabled = false;
            card.classList.add('marcada-eliminar');
            btn.innerHTML = iconArrowBack;
            btn.classList.replace('btn-danger', 'btn-secondary');
            btn.title = 'Deshacer';
            existentesCount--;
        } else {
            input.value = '';
            input.disabled = true;
            card.classList.remove('marcada-eliminar');
            btn.innerHTML = iconXMark;
            btn.classList.replace('btn-secondary', 'btn-danger');
            btn.title = 'Eliminar';
            existentesCount++;
        }
        syncAddBtn();
    });

    syncAddBtn();

    return { addFile, collectFiles };
}
