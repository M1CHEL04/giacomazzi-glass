(function () {
    'use strict';

    var URLS = {
        agregar:  '/carrito/agregar',
        eliminar: '/carrito/eliminar',
        cantidad: '/carrito/cantidad',
        vaciar:   '/carrito/vaciar',
        cotizar:  '/carrito/cotizar',
    };

    var MAX_UNIDADES = 10;

    /** Formatea metros con coma decimal y sin ceros de relleno (2,5 / 12,756). */
    function fmt(n) {
        if (n == null || isNaN(n)) return '';
        return (Math.round(n * 1000) / 1000).toString().replace('.', ',');
    }

    /**
     * Magnitud de una línea, según su unidad. Los carritos que quedaron en
     * sesión antes de existir las unidades no traen `unidad`: se asumen piezas.
     *
     * Devuelve { detalle, total } en texto: `detalle` es la medida de una pieza
     * y `total` el acumulado cuando hay más de una.
     */
    function magnitud(item) {
        var unidad = item.unidad || 'unidades';
        var cant   = item.cantidad != null ? item.cantidad : 1;

        if (unidad === 'alto_ancho') {
            var m2 = item.m2 != null ? item.m2 : (item.alto * item.ancho);
            return {
                detalle: fmt(item.alto) + ' m × ' + fmt(item.ancho) + ' m = ' + fmt(m2) + ' m²',
                total:   cant > 1 ? fmt(m2 * cant) + ' m² totales' : '',
            };
        }
        if (unidad === 'alto' || unidad === 'ancho') {
            var medida = unidad === 'alto' ? item.alto : item.ancho;
            var label  = unidad === 'alto' ? 'Alto' : 'Ancho';
            return {
                detalle: label + ': ' + fmt(medida) + ' m',
                total:   cant > 1 ? fmt(medida * cant) + ' m totales' : '',
            };
        }
        return {
            detalle: cant + (cant === 1 ? ' unidad' : ' unidades'),
            total:   '',
        };
    }

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
            var cant  = item.cantidad != null ? item.cantidad : 1;
            var keyAt = escHtml(item.key);

            // Medidas de la línea (sólo productos que no se cotizan por pieza).
            var medidas = '';
            if (item.unidad && item.unidad !== 'unidades') {
                var mag = magnitud(item);
                medidas = '<div class="carrito-item-medidas">' + escHtml(mag.detalle) +
                    (mag.total ? ' <span class="carrito-item-total">· ' + escHtml(mag.total) + '</span>' : '') +
                    '</div>';
            }

            html +=
                '<li class="carrito-item">' +
                    '<div class="carrito-item-info">' +
                        '<span class="carrito-item-nombre">' + escHtml(item.nombre) + '</span>' +
                        sels +
                        medidas +
                    '</div>' +
                    '<div class="carrito-item-controls">' +
                        '<button class="carrito-item-remove" data-key="' + keyAt + '" aria-label="Eliminar del carrito">' +
                            '<i class="bi bi-trash3"></i>' +
                        '</button>' +
                        '<div class="carrito-cant" role="group" aria-label="Cantidad">' +
                            '<button type="button" class="carrito-cant-btn" data-key="' + keyAt + '" data-accion="menos" data-cantidad="' + cant + '"' + (cant <= 1 ? ' disabled' : '') + ' aria-label="Quitar una unidad"><i class="bi bi-dash"></i></button>' +
                            '<span class="carrito-cant-num">' + cant + '</span>' +
                            '<button type="button" class="carrito-cant-btn" data-key="' + keyAt + '" data-accion="mas" data-cantidad="' + cant + '"' + (cant >= MAX_UNIDADES ? ' disabled' : '') + ' aria-label="Agregar una unidad"><i class="bi bi-plus"></i></button>' +
                        '</div>' +
                    '</div>' +
                '</li>';
        });
        html += '</ul>';
        body.innerHTML = html;

        body.querySelectorAll('.carrito-item-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                eliminarItem(this.dataset.key, this);
            });
        });

        body.querySelectorAll('.carrito-cant-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cur  = parseInt(this.dataset.cantidad, 10) || 1;
                var next = this.dataset.accion === 'mas' ? cur + 1 : cur - 1;
                if (next < 1) next = 1;
                if (next > MAX_UNIDADES) next = MAX_UNIDADES;
                if (next === cur) return;
                actualizarCantidad(this.dataset.key, next);
            });
        });
    }

    function actualizarCantidad(key, cantidad) {
        return post(URLS.cantidad, { key: key, cantidad: cantidad }).then(function (data) {
            if (data.ok) {
                updateBadges(data.cantidad);
                renderCarrito(data.carrito);
            }
            return data;
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
            var cant  = item.cantidad != null ? item.cantidad : 1;
            var linea = '- ';
            if (item.codigo) linea += '[' + item.codigo + '] ';
            linea += item.nombre;
            if (item.selecciones && item.selecciones.length) {
                linea += ' (' + item.selecciones.map(function (s) {
                    return s.variante + ': ' + s.valor;
                }).join(', ') + ')';
            }

            // Siempre se informa la magnitud; el multiplicador de piezas y el
            // total sólo cuando se pidió más de una.
            var mag    = magnitud(item);
            var porPza = item.unidad && item.unidad !== 'unidades';
            linea += ' — ' + mag.detalle;
            if (porPza && cant > 1) {
                linea += ' ×' + cant + ' pzas (' + mag.total + ')';
            }

            msg += linea + '\n';
        });

        var btn    = document.getElementById('carrito-cotizar-btn');
        var numero = btn && btn.dataset.whatsapp ? btn.dataset.whatsapp.replace(/\D/g, '') : '';
        var base   = numero ? 'https://wa.me/' + numero : 'https://wa.me/';
        window.open(base + '?text=' + encodeURIComponent(msg), '_blank');

        // Registra la solicitud de cotización (con snapshot del carrito) y lo vacía.
        // Si falla, no rompe la UX: WhatsApp ya se abrió.
        post(URLS.cotizar, {}).then(function (data) {
            if (data && data.ok) {
                updateBadges(0);
                renderCarrito([]);
            }
        });
    }

    // ── API pública ────────────────────────────────────────────────────────────
    window.Carrito = {
        agregar: function (productoId, valorIds, cantidad, medidas) {
            var payload = { producto_id: productoId, valor_ids: valorIds, cantidad: cantidad || 1 };
            if (medidas && medidas.alto != null)  payload.alto  = medidas.alto;
            if (medidas && medidas.ancho != null) payload.ancho = medidas.ancho;

            return post(URLS.agregar, payload)
                .then(function (data) {
                    if (data.ok) {
                        updateBadges(data.cantidad);
                        renderCarrito(data.carrito);
                    }
                    return data;
                });
        },
        actualizarCantidad: actualizarCantidad,
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
