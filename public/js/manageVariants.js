/**
 * manageVariants.js  —  punto de entrada principal
 * ─────────────────────────────────────────────────────────────
 * Lee la configuración y los iconos del DOM, luego inicializa
 * los dos módulos independientes: imágenes y variantes/SKUs.
 */
import { initImageManager }   from './modules/imageManager.js';
import { initVariantManager } from './modules/variantManager.js';

document.addEventListener('DOMContentLoaded', function () {

    const prodConfigEl = document.getElementById('prod-config');
    const cfg = prodConfigEl
        ? JSON.parse(prodConfigEl.dataset.config)
        : { isEdit: false, existingImgCount: 0, initialVariantes: [], categoriaId: '', portadaExistenteId: null };

    const iconXMark       = document.getElementById('tpl-icon-x-mark')?.innerHTML          || '×';
    const iconArrowBack   = document.getElementById('tpl-icon-arrow-uturn-left')?.innerHTML || '↩';
    const iconStarFill    = document.getElementById('tpl-icon-star-fill')?.innerHTML        || '★';
    const iconStarOutline = document.getElementById('tpl-icon-star-outline')?.innerHTML     || '☆';

    initImageManager({ cfg, iconXMark, iconArrowBack, iconStarFill, iconStarOutline });
    initVariantManager({ cfg, iconXMark });
});
