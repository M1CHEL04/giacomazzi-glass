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
        // El orden se aplica recién cuando los archivos ya están en sus inputs:
        // los tokens `nueva:<i>` se resuelven por posición entre las tarjetas con
        // archivo, así que antes de reponerlos no habría a qué apuntar. addFile()
        // setea los files de forma sincrónica (sólo el preview es async), por eso
        // alcanza con esperar el Promise.all.
        Promise.all([
            restaurar(keyGaleria, gallery),
            restaurar(keyTecnicas, tecnicas),
        ]).then(() => {
            gallery?.applyOrder?.(document.getElementById('imagenes-orden')?.value);
        });
    } else {
        // Carga "limpia" del formulario: cualquier borrador viejo (de un
        // intento anterior que nunca se reintentó) queda obsoleto.
        borrarDraft(keyGaleria);
        borrarDraft(keyTecnicas);
    }

    // El submit real se pospone hasta que termina de guardar: si se dejara
    // seguir de largo, la navegación puede cortar la escritura a IndexedDB
    // a mitad de camino y el borrador quedaría vacío o corrupto.
    //
    // Sin imágenes no hay nada que preservar, así que ni se toca IndexedDB:
    // eso evita por completo que un submit de solo texto dependa de que
    // IndexedDB responda (si queda bloqueada por otra pestaña, el submit
    // no debe pagar ese costo).
    let reenviando = false;
    form.addEventListener('submit', function (e) {
        if (reenviando) return;

        const archivosGaleria  = gallery ? gallery.collectFiles() : [];
        const archivosTecnicas = tecnicas ? tecnicas.collectFiles() : [];
        if (!archivosGaleria.length && !archivosTecnicas.length) return;

        e.preventDefault();

        // Resguardo: si IndexedDB no contesta ni por onsuccess/onerror ni
        // por el onblocked de abrirDb(), no vale la pena colgar el submit
        // esperando (el usuario pierde a lo sumo el redraft de imágenes,
        // no el envío del formulario).
        const conTimeout = (promesa, ms) => Promise.race([
            promesa,
            new Promise(resolve => setTimeout(resolve, ms)),
        ]);

        conTimeout(Promise.all([
            guardarDraft(keyGaleria, archivosGaleria),
            guardarDraft(keyTecnicas, archivosTecnicas),
        ]), 800).catch(() => {}).finally(() => {
            reenviando = true;
            form.requestSubmit();
        });
    });
}
