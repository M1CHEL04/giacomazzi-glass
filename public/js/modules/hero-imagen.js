/**
 * Editor de encuadre del hero de categoría.
 *
 * El hero no tiene una forma fija: mide 30svh en teléfono y 42svh desde 768px,
 * así que el recuadro va de ≈1.9:1 a ≈4.8:1 según el dispositivo. Recortar el
 * archivo no alcanza — además se reutiliza como miniatura en el Inicio —, así
 * que en vez de recortar se guardan tres números por dispositivo: qué punto de
 * la foto tiene que quedar a la vista (x, y) y cuánto ampliarla (zoom).
 *
 * El sitio público los aplica con CSS solo (object-position + transform-origin
 * + scale, ver .g-hero-bg en externo.css). Acá se muestra la foto entera con un
 * recuadro encima: adentro queda lo que se publica y afuera va un velo, porque
 * la decisión que se toma en esta pantalla no es qué mirar sino qué se pierde.
 *
 * La geometría del recuadro es la misma cuenta que hace el navegador, escrita
 * al revés. Con la foto de nw × nh y un recuadro de relación R:
 *
 *   ventana al zoom 1 = el rectángulo R más grande que entra en la foto
 *   vw = ancho base / zoom        left = (x / 100) * (nw - vw)
 *   vh = alto  base / zoom        top  = (y / 100) * (nh - vh)
 *
 * De ahí salen las dos propiedades que hacen que esto se sienta bien: x e y
 * recorren de un borde al otro justo entre 0 y 100, y el arrastre sigue al
 * puntero exactamente, sin importar a qué tamaño se esté dibujando la foto.
 */
(function () {
    'use strict';

    var ZOOM_MIN = 1;
    var ZOOM_MAX = 3;
    var ZOOM_PASO = 0.1;
    var ZOOM_PASO_RUEDA = 0.08;

    /** Debajo de este sobrante (px de foto) el recuadro ya no se puede correr. */
    var SOBRANTE_MINIMO = 1;

    /** Medidas dibujadas por debajo de las cuales la cota tapa más de lo que informa. */
    var COTA_ANCHO_MINIMO = 132;
    var COTA_ALTO_MINIMO = 34;

    var NUDGE = 2;
    var NUDGE_FINO = 0.5;

    document.addEventListener('DOMContentLoaded', function () {
        var fileInput = document.getElementById('imagen_hero');
        if (!fileInput) return;

        var editor = document.getElementById('hero-encuadre');
        if (!editor) return;

        var btnElegir   = document.getElementById('btn-seleccionar-hero');
        var btnQuitar   = document.getElementById('btn-quitar-hero');
        var inputBorrar = document.getElementById('eliminar-imagen-hero-input');
        var nota        = document.getElementById('hero-resolucion');
        var campoNombre = document.getElementById('nombre');

        // Medidas de la copia más grande que el hero puede servir. El editor
        // muestra una variante liviana, así que sin esto la cota informaría los
        // píxeles de la miniatura y no los que el visitante va a recibir.
        // Al elegir un archivo nuevo pasan a ser los de ese archivo.
        var origen = medidasOrigen(editor);

        var urlTemporal = null;

        // Construir y dibujar van separados a propósito: dibujar un marco mira
        // a los demás para el aviso de píxeles, así que no puede pasar mientras
        // la lista todavía se está armando.
        var marcos = [].map.call(editor.querySelectorAll('[data-hero-frame]'), crearMarco);
        marcos.forEach(dibujar);

        function medidasOrigen(el) {
            var ancho = parseFloat(el.dataset.heroOrigenAncho);
            var alto = parseFloat(el.dataset.heroOrigenAlto);

            return (ancho > 0 && alto > 0) ? { w: ancho, h: alto } : null;
        }

        if (btnElegir) {
            btnElegir.addEventListener('click', function () {
                fileInput.click();
            });
        }

        fileInput.addEventListener('change', function () {
            if (!fileInput.files.length) return;

            var archivo = fileInput.files[0];
            if (!archivo.type || archivo.type.indexOf('image/') !== 0) return;

            liberarUrl();
            urlTemporal = URL.createObjectURL(archivo);

            // El archivo elegido es la fuente: sus medidas naturales mandan.
            origen = null;

            // Foto nueva: el encuadre anterior apuntaba a otra imagen. Arranca
            // centrado y sin ampliar, que es el recuadro más grande posible y
            // por lo tanto el que menos recorta.
            marcos.forEach(function (marco) {
                marco.estado.x = 50;
                marco.estado.y = 50;
                marco.estado.zoom = 1;
                marco.img.src = urlTemporal;
                dibujar(marco);
            });

            editor.hidden = false;
            if (inputBorrar) inputBorrar.value = '0';
            if (btnQuitar) btnQuitar.style.display = '';
            if (btnElegir) textoBoton(btnElegir, 'Cambiar imagen');
        });

        if (btnQuitar) {
            btnQuitar.addEventListener('click', function () {
                liberarUrl();
                fileInput.value = '';
                editor.hidden = true;
                btnQuitar.style.display = 'none';
                if (inputBorrar) inputBorrar.value = '1';
                if (btnElegir) textoBoton(btnElegir, 'Seleccionar imagen');
                if (nota) nota.textContent = '';
            });
        }

        // El título de muestra sigue al campo: sirve para ver si cae sobre una
        // zona de la foto donde se lea.
        if (campoNombre) {
            campoNombre.addEventListener('input', function () {
                var texto = campoNombre.value.trim() || 'Nombre de la categoría';
                [].forEach.call(editor.querySelectorAll('.hero-ventana-titulo'), function (el) {
                    el.textContent = texto;
                });
            });
        }

        // El recuadro va en porcentajes, pero la cota se muestra u oculta según
        // su tamaño dibujado, que sí cambia con el ancho de la ventana.
        window.addEventListener('resize', function () {
            marcos.forEach(dibujar);
        });

        function liberarUrl() {
            if (urlTemporal) {
                URL.revokeObjectURL(urlTemporal);
                urlTemporal = null;
            }
        }

        function textoBoton(boton, texto) {
            // El botón lleva un <i> adelante: se reemplaza sólo el nodo de texto.
            var nodo = boton.lastChild;
            if (nodo && nodo.nodeType === 3) nodo.textContent = ' ' + texto;
        }

        function crearMarco(figura) {
            var marco = {
                figura: figura,
                ratio: numero(figura.dataset.heroRatio, 1.9),
                minimo: numero(figura.dataset.heroMinimo, 0),
                lienzo: figura.querySelector('[data-hero-lienzo]'),
                img: figura.querySelector('[data-hero-img]'),
                ventana: figura.querySelector('[data-hero-ventana]'),
                cota: figura.querySelector('[data-hero-cota]'),
                medida: figura.querySelector('[data-hero-medida]'),
                zoomInput: figura.querySelector('[data-hero-zoom]'),
                zoomValor: figura.querySelector('[data-hero-zoom-valor]'),
                inputs: {
                    x: figura.querySelector('[data-hero-input="x"]'),
                    y: figura.querySelector('[data-hero-input="y"]'),
                    zoom: figura.querySelector('[data-hero-input="zoom"]')
                }
            };

            marco.estado = {
                x: numero(marco.inputs.x.value, 50),
                y: numero(marco.inputs.y.value, 50),
                zoom: numero(marco.inputs.zoom.value, 1)
            };

            conectar(marco);

            marco.img.addEventListener('load', function () {
                dibujar(marco);
            });

            return marco;
        }

        function conectar(marco) {
            var lienzo = marco.lienzo;
            var arrastre = null;

            lienzo.addEventListener('pointerdown', function (e) {
                if (!ventana(marco)) return;

                arrastre = { id: e.pointerId, x: e.clientX, y: e.clientY };
                lienzo.setPointerCapture(e.pointerId);
                lienzo.dataset.arrastrando = '';
                e.preventDefault();
            });

            lienzo.addEventListener('pointermove', function (e) {
                if (!arrastre || e.pointerId !== arrastre.id) return;

                var v = ventana(marco);
                if (!v) return;

                var caja = lienzo.getBoundingClientRect();
                if (!caja.width) return;

                // De píxeles de pantalla a píxeles de foto: así el recuadro va
                // exactamente donde va el puntero, se dibuje al tamaño que sea.
                var escala = caja.width / v.nw;
                var dx = (e.clientX - arrastre.x) / escala;
                var dy = (e.clientY - arrastre.y) / escala;
                arrastre.x = e.clientX;
                arrastre.y = e.clientY;

                if (v.sobranteX > SOBRANTE_MINIMO) {
                    marco.estado.x = limitar(marco.estado.x + dx / v.sobranteX * 100);
                }
                if (v.sobranteY > SOBRANTE_MINIMO) {
                    marco.estado.y = limitar(marco.estado.y + dy / v.sobranteY * 100);
                }

                dibujar(marco);
                e.preventDefault();
            });

            ['pointerup', 'pointercancel'].forEach(function (evento) {
                lienzo.addEventListener(evento, function (e) {
                    if (!arrastre || e.pointerId !== arrastre.id) return;
                    arrastre = null;
                    delete lienzo.dataset.arrastrando;
                });
            });

            lienzo.addEventListener('wheel', function (e) {
                if (!ventana(marco)) return;
                e.preventDefault();
                zoomear(marco, e.deltaY < 0 ? ZOOM_PASO_RUEDA : -ZOOM_PASO_RUEDA);
            }, { passive: false });

            lienzo.addEventListener('keydown', function (e) {
                var paso = e.shiftKey ? NUDGE_FINO : NUDGE;
                var manejado = true;

                switch (e.key) {
                    case 'ArrowLeft':  marco.estado.x = limitar(marco.estado.x - paso); break;
                    case 'ArrowRight': marco.estado.x = limitar(marco.estado.x + paso); break;
                    case 'ArrowUp':    marco.estado.y = limitar(marco.estado.y - paso); break;
                    case 'ArrowDown':  marco.estado.y = limitar(marco.estado.y + paso); break;
                    case '+':
                    case '=':          zoomear(marco, ZOOM_PASO); break;
                    case '-':
                    case '_':          zoomear(marco, -ZOOM_PASO); break;
                    default:           manejado = false;
                }

                if (manejado) {
                    e.preventDefault();
                    dibujar(marco);
                }
            });

            marco.zoomInput.addEventListener('input', function () {
                marco.estado.zoom = limitarZoom(numero(marco.zoomInput.value, 1));
                dibujar(marco);
            });

            enlazar(marco.figura.querySelector('[data-hero-zoom-menos]'), function () {
                zoomear(marco, -ZOOM_PASO);
            });
            enlazar(marco.figura.querySelector('[data-hero-zoom-mas]'), function () {
                zoomear(marco, ZOOM_PASO);
            });
            enlazar(marco.figura.querySelector('[data-hero-centrar]'), function () {
                marco.estado.x = 50;
                marco.estado.y = 50;
                marco.estado.zoom = 1;
                dibujar(marco);
            });
        }

        function enlazar(boton, accion) {
            if (boton) boton.addEventListener('click', accion);
        }

        function zoomear(marco, delta) {
            marco.estado.zoom = limitarZoom(marco.estado.zoom + delta);
            dibujar(marco);
        }

        /**
         * El rectángulo de la foto que va a quedar a la vista, en píxeles de la
         * foto. Es la misma región que object-fit: cover + scale recortan en el
         * hero publicado, de ahí que el recuadro no sea una aproximación.
         */
        function ventana(marco) {
            // La forma es la misma en la variante y en la fuente, así que la
            // geometría no cambia; lo que cambia es la escala en que se informa.
            var nw = origen ? origen.w : marco.img.naturalWidth;
            var nh = origen ? origen.h : marco.img.naturalHeight;
            if (!nw || !nh) return null;

            // Al zoom 1, el rectángulo con la forma del recuadro más grande que
            // entra en la foto: el encuadre que menos recorta de todos.
            var anchoBase, altoBase;
            if (nw / nh > marco.ratio) {
                altoBase = nh;
                anchoBase = nh * marco.ratio;
            } else {
                anchoBase = nw;
                altoBase = nw / marco.ratio;
            }

            var vw = anchoBase / marco.estado.zoom;
            var vh = altoBase / marco.estado.zoom;

            return {
                nw: nw,
                nh: nh,
                vw: vw,
                vh: vh,
                sobranteX: nw - vw,
                sobranteY: nh - vh,
                left: (marco.estado.x / 100) * (nw - vw),
                top: (marco.estado.y / 100) * (nh - vh)
            };
        }

        function dibujar(marco) {
            var estado = marco.estado;

            marco.inputs.x.value = redondear(estado.x);
            marco.inputs.y.value = redondear(estado.y);
            marco.inputs.zoom.value = redondear(estado.zoom);

            if (marco.zoomInput.value !== String(estado.zoom)) {
                marco.zoomInput.value = estado.zoom;
            }
            marco.zoomInput.style.setProperty(
                '--relleno',
                ((estado.zoom - ZOOM_MIN) / (ZOOM_MAX - ZOOM_MIN) * 100) + '%'
            );
            if (marco.zoomValor) marco.zoomValor.textContent = estado.zoom.toFixed(2) + '×';

            var v = ventana(marco);
            if (!v) return;

            // Todo en porcentajes del lienzo, que calca a la foto: el recuadro
            // queda bien puesto sin depender del tamaño al que se la dibuje.
            marco.ventana.style.left = (v.left / v.nw * 100) + '%';
            marco.ventana.style.top = (v.top / v.nh * 100) + '%';
            marco.ventana.style.width = (v.vw / v.nw * 100) + '%';
            marco.ventana.style.height = (v.vh / v.nh * 100) + '%';

            if (marco.medida) {
                marco.medida.textContent = Math.round(v.vw) + ' × ' + Math.round(v.vh) + ' px';
            }

            if (marco.cota) {
                var caja = marco.ventana.getBoundingClientRect();
                marco.cota.hidden = caja.width < COTA_ANCHO_MINIMO || caja.height < COTA_ALTO_MINIMO;

                if (v.vw < marco.minimo) {
                    marco.cota.dataset.escasa = '';
                } else {
                    delete marco.cota.dataset.escasa;
                }
            }

            actualizarNota();
        }

        /** Un solo aviso abajo, aunque a los dos recuadros les falten píxeles. */
        function actualizarNota() {
            // `marcos` no existe hasta que terminan de construirse todos: un
            // dibujo temprano se saltea el aviso en vez de tirar la pantalla.
            if (!nota || !marcos) return;

            var escasos = marcos.filter(function (marco) {
                var v = ventana(marco);
                return v && v.vw < marco.minimo;
            });

            if (!escasos.length) {
                nota.textContent = '';
                return;
            }

            var nombres = escasos.map(function (marco) {
                return (marco.figura.dataset.heroNombre || '').toLowerCase();
            });

            nota.textContent = 'A esta foto le faltan píxeles para ' + unir(nombres) +
                '. Se va a ampliar para cubrir el hero y puede verse blanda: bajá el zoom' +
                ' o subí una imagen más grande.';
        }

        function unir(nombres) {
            return nombres.length === 1 ? nombres[0] : nombres.join(' y ');
        }
    });

    function limitar(valor) {
        return Math.min(100, Math.max(0, valor));
    }

    function limitarZoom(valor) {
        return Math.round(Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, valor)) * 100) / 100;
    }

    function redondear(valor) {
        return Math.round(valor * 100) / 100;
    }

    function numero(valor, porDefecto) {
        var n = parseFloat(valor);
        return isNaN(n) ? porDefecto : n;
    }
})();
