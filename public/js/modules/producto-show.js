(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initCarousel();
        initVariantes();
        initLightbox();
        initCarrito();
    });

    /* ---- Carrusel + sincronización de thumbnails ---- */
    function initCarousel() {
        var carouselEl = document.getElementById('ps-carousel');
        var thumbs = document.querySelectorAll('.ps-thumb');
        if (!carouselEl || thumbs.length === 0) return;

        var bsCarousel = new bootstrap.Carousel(carouselEl, { touch: true, interval: false });

        carouselEl.addEventListener('slide.bs.carousel', function (e) {
            thumbs.forEach(function (t) { t.classList.remove('active'); });
            if (thumbs[e.to]) {
                thumbs[e.to].classList.add('active');
                thumbs[e.to].scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        });

        thumbs.forEach(function (thumb, i) {
            thumb.addEventListener('click', function () { bsCarousel.to(i); });
        });
    }

    /* ---- Selector de variantes (sin navegación, solo UI) ---- */
    function initVariantes() {
        document.querySelectorAll('.ps-variante').forEach(function (grupo) {
            var opciones = grupo.querySelectorAll('.ps-opcion');

            opciones.forEach(function (opcion) {
                opcion.addEventListener('click', function () {
                    if (this.classList.contains('active')) return;

                    // Desactivar todas las opciones del grupo
                    opciones.forEach(function (o) { o.classList.remove('active'); });

                    // Activar la seleccionada
                    this.classList.add('active');

                    // Actualizar el label inline que muestra el valor elegido
                    var labelId = this.dataset.labelId;
                    if (labelId) {
                        var labelEl = document.getElementById(labelId);
                        if (labelEl) labelEl.textContent = '— ' + this.textContent.trim();
                    }
                });
            });
        });
    }

    /* ---- Lightbox ---- */
    function initLightbox() {
        var lightbox   = document.getElementById('ps-lightbox');
        var lbImg      = document.getElementById('ps-lightbox-img');
        var closeBtn   = document.getElementById('ps-lightbox-close');
        var lbThumbs   = document.querySelectorAll('.ps-lightbox-thumb');
        var carouselEl = document.getElementById('ps-carousel');

        if (!lightbox || !lbImg) return;

        var currentIndex = 0;

        function openLightbox(index) {
            var thumbs = document.querySelectorAll('.ps-lightbox-thumb');
            if (!thumbs[index]) return;
            currentIndex = index;
            lbImg.src = thumbs[index].dataset.src;
            syncLbThumbs(index);
            lightbox.showModal();
        }

        function syncLbThumbs(index) {
            lbThumbs.forEach(function (t) { t.classList.remove('active'); });
            if (lbThumbs[index]) {
                lbThumbs[index].classList.add('active');
                lbThumbs[index].scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        }

        if (carouselEl) {
            carouselEl.addEventListener('click', function (e) {
                var img = e.target.closest('.ps-carousel-img');
                if (!img) return;
                var activeItem = carouselEl.querySelector('.carousel-item.active');
                var items = carouselEl.querySelectorAll('.carousel-item');
                openLightbox(Array.from(items).indexOf(activeItem));
            });
        }

        var ampliarBtn = document.getElementById('ps-ampliar-btn');
        if (ampliarBtn && carouselEl) {
            ampliarBtn.addEventListener('click', function () {
                var activeItem = carouselEl.querySelector('.carousel-item.active');
                var items = carouselEl.querySelectorAll('.carousel-item');
                openLightbox(Array.from(items).indexOf(activeItem));
            });
        }

        lbThumbs.forEach(function (thumb, i) {
            thumb.addEventListener('click', function () {
                currentIndex = i;
                lbImg.style.opacity = '0';
                setTimeout(function () {
                    lbImg.src = thumb.dataset.src;
                    lbImg.style.opacity = '1';
                }, 140);
                syncLbThumbs(i);
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () { lightbox.close(); });
        }

        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) lightbox.close();
        });

        lightbox.addEventListener('keydown', function (e) {
            var total = lbThumbs.length;
            if (total === 0) return;
            if (e.key === 'ArrowRight') lbThumbs[(currentIndex + 1) % total].click();
            if (e.key === 'ArrowLeft')  lbThumbs[(currentIndex - 1 + total) % total].click();
        });
    }

    /* ---- Agregar al carrito ---- */
    var MAX_UNIDADES = 10;
    var MAX_METROS   = 100;   // debe coincidir con CarritoController::MAX_METROS
    var MIN_METROS   = 0.001;

    /** Interpreta una medida tipeada a mano: acepta coma o punto decimal. */
    function parseMetros(str) {
        var n = parseFloat(String(str == null ? '' : str).trim().replace(',', '.'));
        return isNaN(n) ? null : Math.round(n * 1000) / 1000;
    }

    /** Formatea metros con coma decimal y sin ceros de relleno (2,5 / 12,756). */
    function fmtMetros(n) {
        if (n == null || isNaN(n)) return '';
        return (Math.round(n * 1000) / 1000).toString().replace('.', ',');
    }

    function initCarrito() {
        var btn     = document.getElementById('btn-agregar-carrito');
        var icon    = document.getElementById('btn-carrito-icon');
        var spinner = document.getElementById('btn-carrito-spinner');
        var textEl  = document.getElementById('btn-carrito-text');
        if (!btn || !window.Carrito) return;

        // ── Medidas en metros (sólo si el producto se cotiza por medida) ──
        var medidasEl = document.getElementById('ps-medidas');
        var altoInput  = document.getElementById('ps-alto');
        var anchoInput = document.getElementById('ps-ancho');
        var m2El       = document.getElementById('ps-m2');
        var errorEl    = document.getElementById('ps-medidas-error');

        function mostrarError(msg) {
            if (!errorEl) return;
            errorEl.textContent = msg;
            errorEl.classList.toggle('d-none', !msg);
        }

        /** Lee y valida una medida; marca el input y devuelve null si no sirve. */
        function leerMedida(input, etiqueta, errores) {
            if (!input) return null;
            var n = parseMetros(input.value);
            var valido = n !== null && n >= MIN_METROS && n <= MAX_METROS;
            input.classList.toggle('is-invalid', !valido);
            if (!valido) {
                errores.push(n === null || n <= 0
                    ? 'Ingresá el ' + etiqueta + ' en metros.'
                    : 'El ' + etiqueta + ' no puede superar los ' + MAX_METROS + ' m.');
                return null;
            }
            return n;
        }

        function actualizarM2() {
            if (!m2El) return;
            var alto  = parseMetros(altoInput ? altoInput.value : '');
            var ancho = parseMetros(anchoInput ? anchoInput.value : '');
            m2El.textContent = (alto && ancho)
                ? '= ' + fmtMetros(alto * ancho) + ' m² por pieza'
                : '';
        }

        [altoInput, anchoInput].forEach(function (input) {
            if (!input) return;
            input.addEventListener('input', function () {
                input.classList.remove('is-invalid');
                mostrarError('');
                actualizarM2();
            });
        });

        // ── Stepper de unidades ──
        var cantInput = document.getElementById('ps-cantidad');
        var btnMenos  = document.getElementById('ps-cant-menos');
        var btnMas    = document.getElementById('ps-cant-mas');

        function getCantidad() {
            var n = parseInt(cantInput ? cantInput.value : '1', 10);
            if (isNaN(n) || n < 1) n = 1;
            if (n > MAX_UNIDADES) n = MAX_UNIDADES;
            return n;
        }

        function setCantidad(n) {
            if (n < 1) n = 1;
            if (n > MAX_UNIDADES) n = MAX_UNIDADES;
            if (cantInput) cantInput.value = n;
            if (btnMenos) btnMenos.disabled = n <= 1;
            if (btnMas)   btnMas.disabled   = n >= MAX_UNIDADES;
        }

        if (btnMenos) btnMenos.addEventListener('click', function () { setCantidad(getCantidad() - 1); });
        if (btnMas)   btnMas.addEventListener('click', function () { setCantidad(getCantidad() + 1); });
        setCantidad(1);

        function setLoading(loading) {
            btn.disabled = loading;
            if (icon)    icon.classList.toggle('d-none', loading);
            if (spinner) spinner.classList.toggle('d-none', !loading);
        }

        btn.addEventListener('click', function () {
            var productoId = parseInt(btn.dataset.productoId, 10);
            var cantidad   = getCantidad();
            var valorIds   = [];

            document.querySelectorAll('.ps-opcion.active').forEach(function (opcion) {
                var id = parseInt(opcion.dataset.valorId, 10);
                if (id) valorIds.push(id);
            });

            // Las medidas son obligatorias cuando la unidad del producto las pide.
            var medidas = {};
            if (medidasEl) {
                var errores = [];
                if (medidasEl.dataset.requiereAlto === '1') {
                    medidas.alto = leerMedida(altoInput, 'alto', errores);
                }
                if (medidasEl.dataset.requiereAncho === '1') {
                    medidas.ancho = leerMedida(anchoInput, 'ancho', errores);
                }
                if (errores.length) {
                    mostrarError(errores[0]);
                    var primerInvalido = medidasEl.querySelector('.is-invalid');
                    if (primerInvalido) primerInvalido.focus();
                    return;
                }
                mostrarError('');
            }

            setLoading(true);
            if (textEl) textEl.textContent = 'Agregando...';

            window.Carrito.agregar(productoId, valorIds, cantidad, medidas)
                .then(function (data) {
                    setLoading(false);
                    if (!data.ok) {
                        if (textEl) textEl.textContent = 'Agregar al carrito';
                        mostrarError(data.message || 'No se pudo agregar el producto.');
                        return;
                    }
                    if (textEl) textEl.textContent = '¡Agregado!';
                    setCantidad(1);
                    window.Carrito.abrirPanel();
                    setTimeout(function () {
                        if (textEl) textEl.textContent = 'Agregar al carrito';
                        btn.disabled = false;
                    }, 1800);
                })
                .catch(function () {
                    setLoading(false);
                    if (textEl) textEl.textContent = 'Agregar al carrito';
                });
        });
    }
})();
