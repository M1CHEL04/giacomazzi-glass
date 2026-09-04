/**
 * manageEspecial.js  —  punto de entrada del formulario de producto especial
 * ─────────────────────────────────────────────────────────────
 * Es manageVariants.js sin el módulo de variantes: los productos a medida no
 * tienen variantes ni SKUs. El resto —galería, imágenes técnicas, contadores
 * y estado del submit— es exactamente el mismo, y se reusa sin cambios.
 */
import { initImageManager }   from './modules/imageManager.js';
import { initSubmitState }    from './modules/submitState.js';
import { initCharCounters }   from './modules/charCounter.js';
import { initTecnicaImageManager } from './modules/tecnicaImageManager.js';
import { initImageDraftPersistence } from './modules/imageDraftPersistence.js';

document.addEventListener('DOMContentLoaded', function () {

    const prodConfigEl = document.getElementById('prod-config');
    const cfg = prodConfigEl
        ? JSON.parse(prodConfigEl.dataset.config)
        : { isEdit: false, existingImgCount: 0, existingTecnicasCount: 0, initialVariantes: [], categoriaId: '', portadaExistenteId: null, productoId: null, hasErrors: false };

    const iconXMark       = document.getElementById('tpl-icon-x-mark')?.innerHTML          || '×';
    const iconArrowBack   = document.getElementById('tpl-icon-arrow-uturn-left')?.innerHTML || '↩';
    const iconStarFill    = document.getElementById('tpl-icon-star-fill')?.innerHTML        || '★';
    const iconStarOutline = document.getElementById('tpl-icon-star-outline')?.innerHTML     || '☆';

    const gallery  = initImageManager({ cfg, iconXMark, iconArrowBack, iconStarFill, iconStarOutline });
    const tecnicas = initTecnicaImageManager({ cfg, iconXMark, iconArrowBack });
    initImageDraftPersistence({
        form: document.querySelector('form[enctype="multipart/form-data"]'),
        cfg, gallery, tecnicas,
    });
    initCharCounters();

    // Último: así su listener de submit corre después del de imágenes,
    // que es el que escribe el hidden de portada antes de que salga.
    initSubmitState(document.querySelector('form[enctype="multipart/form-data"]'));
});
