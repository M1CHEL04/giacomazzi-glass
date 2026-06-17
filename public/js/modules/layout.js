(function () {
    'use strict';

    // ── Desktop dropdown hover ────────────────────────────────────────────────
    var dropdownEl     = document.querySelector('.dropdown');
    var dropdownToggle = document.querySelector('.dropdown-toggle');
    var dropdownMenu   = document.querySelector('.dropdown-menu');
    var hideTimeout;

    if (dropdownEl && window.innerWidth >= 992) {
        dropdownEl.addEventListener('mouseenter', function () {
            clearTimeout(hideTimeout);
            dropdownToggle.classList.add('show');
            dropdownMenu.classList.add('show');
        });
        dropdownEl.addEventListener('mouseleave', function () {
            hideTimeout = setTimeout(function () {
                dropdownToggle.classList.remove('show');
                dropdownMenu.classList.remove('show');
            }, 150);
        });
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth < 992) {
            clearTimeout(hideTimeout);
            if (dropdownToggle) dropdownToggle.classList.remove('show');
            if (dropdownMenu)   dropdownMenu.classList.remove('show');
        }
    });

    // ── Mobile drawer ─────────────────────────────────────────────────────────
    var menuBtn  = document.getElementById('mobile-menu-btn');
    var drawer   = document.getElementById('mobile-nav-drawer');
    var closeBtn = document.getElementById('mobile-drawer-close');
    var backdrop = document.getElementById('mobile-drawer-backdrop');

    function openDrawer() {
        drawer.classList.add('open');
        menuBtn.classList.add('open');
        menuBtn.setAttribute('aria-expanded', 'true');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        menuBtn.classList.remove('open');
        menuBtn.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (menuBtn)  menuBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDrawer();
    });

    // ── Acordeón de Productos en el drawer ────────────────────────────────────
    var productsToggle  = document.getElementById('mobile-products-toggle');
    var productsSection = document.getElementById('mobile-products-section');

    if (productsToggle && productsSection) {
        productsToggle.addEventListener('click', function () {
            var isOpen = productsSection.classList.toggle('open');
            productsToggle.setAttribute('aria-expanded', String(isOpen));
        });
    }
})();
