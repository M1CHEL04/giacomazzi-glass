/**
 * Cajas con scroll propio.
 *
 * Una caja de alto fijo que scrollea por dentro no se ve: en teléfono la
 * barra recién aparece cuando ya estás arrastrando, así que el contenido
 * cortado se lee como contenido que no está. Esto marca la caja con dos
 * clases y el CSS decide qué mostrar con ellas.
 *
 * La medición es la única forma honesta de hacerlo: si el contenido entra
 * entero no hay clase, y entonces no hay velo ni renglón prometiendo algo
 * que no existe. Con cuatro categorías la caja no dice nada; con treinta,
 * avisa.
 *
 * Hooks:  [data-scroller] en el elemento que scrollea.
 * Clases: .is-scrollable  sobra contenido (hay adónde ir)
 *         .is-end         llegó al final (ya no hay más abajo)
 */
(function () {
    'use strict';

    var cajas = document.querySelectorAll('[data-scroller]');
    if (!cajas.length) return;

    cajas.forEach(function (caja) {
        var pendiente = false;

        // El margen de 4px es contra los redondeos del navegador: sin él
        // una caja que entra justo se marcaba como scrolleable, y el final
        // no llegaba nunca por medio píxel.
        function sincronizar() {
            var sobra = caja.scrollHeight - caja.clientHeight;
            caja.classList.toggle('is-scrollable', sobra > 4);
            caja.classList.toggle('is-end', caja.scrollTop >= sobra - 4);
            pendiente = false;
        }

        function agendar() {
            if (pendiente) return;
            pendiente = true;
            window.requestAnimationFrame(sincronizar);
        }

        caja.addEventListener('scroll', agendar, { passive: true });
        window.addEventListener('resize', agendar);

        // El alto de la caja cambia sin que haya scroll ni resize de
        // ventana: las fuentes que terminan de cargar reacomodan los
        // renglones y lo que entraba justo deja de entrar.
        if ('ResizeObserver' in window) {
            new ResizeObserver(agendar).observe(caja);
        }

        sincronizar();
    });
}());
