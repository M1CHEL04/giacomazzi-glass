(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initCarousel();
        initVariantes();
        initLightbox();
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
})();
