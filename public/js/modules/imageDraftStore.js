/**
 * imageDraftStore.js
 * ─────────────────────────────────────────────────────────────
 * Guarda en IndexedDB los File que el usuario ya seleccionó en el
 * formulario de producto, para poder restaurarlos si el submit vuelve
 * con errores de validación (el input file se vacía solo al recargar
 * la página, por seguridad del navegador).
 *
 * IndexedDB y no sessionStorage: las imágenes de producto pesan hasta
 * 5 MB cada una y sessionStorage tiene una cuota de unos pocos MB por
 * origen, justo el rango que dispara el error que este cambio atiende.
 */

const DB_NAME = 'giacomazzi-imagenes-draft';
const STORE_NAME = 'archivos';

function abrirDb() {
    return new Promise((resolve, reject) => {
        if (!window.indexedDB) {
            reject(new Error('IndexedDB no disponible'));
            return;
        }
        const req = indexedDB.open(DB_NAME, 1);
        req.onupgradeneeded = () => req.result.createObjectStore(STORE_NAME);
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

/** Best-effort: si falla (cuota, navegador privado, etc.) simplemente no se restaura después. */
export async function guardarDraft(key, files) {
    try {
        const db = await abrirDb();
        await new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).put(files, key);
            tx.oncomplete = resolve;
            tx.onerror = () => reject(tx.error);
        });
        db.close();
    } catch (e) {
        // noop
    }
}

export async function leerDraft(key) {
    try {
        const db = await abrirDb();
        const files = await new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const req = tx.objectStore(STORE_NAME).get(key);
            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => reject(req.error);
        });
        db.close();
        return files;
    } catch (e) {
        return null;
    }
}

export async function borrarDraft(key) {
    try {
        const db = await abrirDb();
        await new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).delete(key);
            tx.oncomplete = resolve;
            tx.onerror = () => reject(tx.error);
        });
        db.close();
    } catch (e) {
        // noop
    }
}
