/**
 * imageDraftPersistence.js
 * ─────────────────────────────────────────────────────────────
 * Coordina imageManager.js / tecnicaImageManager.js con imageDraftStore.js:
 * antes de enviar el formulario guarda los archivos ya seleccionados, y si
 * la vista se recarga con errores de validación los vuelve a cargar en los
 * inputs para que el usuario no tenga que elegir de nuevo las imágenes que
 * sí estaban bien.
 *
 * `gallery` y `tecnicas` son lo que devuelven initImageManager /
 * initTecnicaImageManager: { addFile(file), collectFiles() }.
 */
import { leerDraft, guardarDraft, borrarDraft } from './imageDraftStore.js';

export function initImageDraftPersistence({ form, cfg, gallery, tecnicas }) {
    if (!form) return;

    const scope = cfg.isEdit ? ('producto:' + cfg.productoId) : 'producto:nuevo';
    const keyGaleria  = scope + ':imagenes';
    const keyTecnicas = scope + ':tecnicas';

    async function restaurar(key, manager) {
        if (!manager) return;
        const files = await leerDraft(key);
        if (!files || !files.length) return;
        files.forEach(file => manager.addFile(file));
        borrarDraft(key);
    }

    if (cfg.hasErrors) {
        restaurar(keyGaleria, gallery);
        restaurar(keyTecnicas, tecnicas);
    } else {
        // Carga "limpia" del formulario: cualquier borrador viejo (de un
        // intento anterior que nunca se reintentó) queda obsoleto.
        borrarDraft(keyGaleria);
        borrarDraft(keyTecnicas);
    }

    // El submit real se pospone hasta que termina de guardar: si se dejara
    // seguir de largo, la navegación puede cortar la escritura a IndexedDB
    // a mitad de camino y el borrador quedaría vacío o corrupto.
    let reenviando = false;
    form.addEventListener('submit', function (e) {
        if (reenviando) return;
        e.preventDefault();

        Promise.all([
            guardarDraft(keyGaleria, gallery ? gallery.collectFiles() : []),
            guardarDraft(keyTecnicas, tecnicas ? tecnicas.collectFiles() : []),
        ]).catch(() => {}).finally(() => {
            reenviando = true;
            form.requestSubmit();
        });
    });
}
