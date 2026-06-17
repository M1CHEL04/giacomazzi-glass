(function () {
    'use strict';

    var URLS = {
        agregar:  '/carrito/agregar',
        eliminar: '/carrito/eliminar',
        vaciar:   '/carrito/vaciar',
    };

    var state = (window.__carritoInit && typeof window.__carritoInit === 'object')
        ? { cantidad: window.__carritoInit.cantidad, items: window.__carritoInit.carrito }
        : { cantidad: 0, items: [] };

    function getCsrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        }).then(function (r) { return r.json(); });
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    function updateBadges(cantidad) {
        state.cantidad = cantidad;
        document.querySelectorAll('.cart-badge').forEach(function (el) {
            el.textContent = cantidad;
            el.style.display = cantidad > 0 ? '' : 'none';
        });
    }

    function renderCarrito(items) {
        state.items = items || [];
        var body   = document.getElementById('carrito-body');
        var footer = document.getElementById('carrito-footer');
        if (!body) return;

        if (state.items.length === 0) {
            body.innerHTML = '<p class="carrito-empty">Tu carrito está vacío.</p>';
            if (footer) footer.style.display = 'none';
            return;
        }

        if (footer) footer.style.display = '';

        var html = '<ul class="carrito-lista">';
        state.items.forEach(function (item) {
            var sels = '';
            if (item.selecciones && item.selecciones.length) {
                sels = '<div class="carrito-item-sels">' +
                    item.selecciones.map(function (s) {
                        return escHtml(s.variante) + ': <strong>' + escHtml(s.valor) + '</strong>';
                    }).join(' &middot; ') +
                    '</div>';
            }
            html +=
                '<li class="carrito-item">' +
                    '<div class="carrito-item-info">' +
                        '<span class="carrito-item-nombre">' + escHtml(item.nombre) + '</span>' +
                        sels +
                    '</div>' +
                    '<button class="carrito-item-remove" data-key="' + escHtml(item.key) + '" aria-label="Eliminar del carrito">' +
                        '<i class="bi bi-trash3"></i>' +
                    '</button>' +
                '</li>';
        });
        html += '</ul>';
        body.innerHTML = html;

        body.querySelectorAll('.carrito-item-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                eliminarItem(this.dataset.key, this);
            });
        });
    }

    function eliminarItem(key, btn) {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }
        post(URLS.eliminar, { key: key }).then(function (data) {
            if (data.ok) {
                updateBadges(data.cantidad);
                renderCarrito(data.carrito);
            } else if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash3"></i>';
            }
        });
    }

    function vaciarCarrito() {
        post(URLS.vaciar, {}).then(function (data) {
            if (data.ok) {
                updateBadges(0);
                renderCarrito([]);
            }
        });
    }

    function cotizar() {
        if (!state.items.length) return;

        var msg = 'Hola, me interesaron los siguientes productos y necesitaba una cotización para mi obra:\n\n';
        state.items.forEach(function (item) {
            var linea = '- ';
            if (item.codigo) linea += '[' + item.codigo + '] ';
            linea += item.nombre;
            if (item.selecciones && item.selecciones.length) {
                linea += ' (' + item.selecciones.map(function (s) {
                    return s.variante + ': ' + s.valor;
                }).join(', ') + ')';
            }
            msg += linea + '\n';
        });

        var btn    = document.getElementById('carrito-cotizar-btn');
        var numero = btn && btn.dataset.whatsapp ? btn.dataset.whatsapp.replace(/\D/g, '') : '';
        var base   = numero ? 'https://wa.me/' + numero : 'https://wa.me/';
        window.open(base + '?text=' + encodeURIComponent(msg), '_blank');
        vaciarCarrito();
    }

    // ── API pública ────────────────────────────────────────────────────────────
    window.Carrito = {
        agregar: function (productoId, valorIds) {
            return post(URLS.agregar, { producto_id: productoId, valor_ids: valorIds })
                .then(function (data) {
                    if (data.ok) {
                        updateBadges(data.cantidad);
                        renderCarrito(data.carrito);
                    }
                    return data;
                });
        },
        abrirPanel: function () {
            var el = document.getElementById('carritoOffcanvas');
            if (el) bootstrap.Offcanvas.getOrCreateInstance(el).show();
        },
        getState: function () { return state; },
    };

    // ── Init ───────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        updateBadges(state.cantidad);
        renderCarrito(state.items);

        var vaciarBtn = document.getElementById('carrito-vaciar-btn');
        if (vaciarBtn) {
            vaciarBtn.addEventListener('click', function () {
                if (!state.items.length) return;
                var modalEl = document.getElementById('vaciarCarritoModal');
                if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
        }

        var vaciarConfirmBtn = document.getElementById('vaciar-confirm-btn');
        if (vaciarConfirmBtn) {
            vaciarConfirmBtn.addEventListener('click', function () {
                var modalEl = document.getElementById('vaciarCarritoModal');
                if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                vaciarCarrito();
            });
        }

        var cotizarBtn = document.getElementById('carrito-cotizar-btn');
        if (cotizarBtn) {
            cotizarBtn.addEventListener('click', cotizar);
        }

        // Botón del drawer mobile → cerrar drawer y abrir carrito
        var mobileCartBtn = document.querySelector('.mobile-nav-cart-btn');
        if (mobileCartBtn) {
            mobileCartBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var drawer  = document.getElementById('mobile-nav-drawer');
                var menuBtn = document.getElementById('mobile-menu-btn');
                if (drawer) {
                    drawer.classList.remove('open');
                    drawer.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }
                if (menuBtn) {
                    menuBtn.classList.remove('open');
                    menuBtn.setAttribute('aria-expanded', 'false');
                }
                var carritoEl = document.getElementById('carritoOffcanvas');
                if (carritoEl) {
                    bootstrap.Offcanvas.getOrCreateInstance(carritoEl).show();
                }
            });
        }
    });
})();
