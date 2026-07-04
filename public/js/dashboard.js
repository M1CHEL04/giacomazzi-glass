/**
 * dashboard.js — tooltip del gráfico "Cotizaciones por mes".
 * Muestra un popup con la cantidad del mes al pasar el mouse (desktop) o
 * al tocar la barra (touch). Se posiciona con position:fixed sobre la barra,
 * así respeta el scroll horizontal del gráfico.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var chart = document.querySelector('.dash-chart');
        if (!chart) return;

        var scroll = chart.querySelector('.dash-chart-scroll');
        if (!scroll) return;

        var tip = document.createElement('div');
        tip.className = 'dash-chart-tip';
        tip.setAttribute('role', 'status');
        document.body.appendChild(tip);

        var activeCol = null;

        function render(col) {
            var total = parseInt(col.dataset.total, 10) || 0;
            var mes   = col.dataset.mes || '';
            var anio  = col.dataset.anio || '';
            var label = total === 1 ? 'cotización' : 'cotizaciones';
            tip.innerHTML =
                '<span class="dash-chart-tip-num">' + total + '</span>' +
                '<span class="dash-chart-tip-label">' + label + '</span>' +
                '<span class="dash-chart-tip-mes">' + mes + ' ' + anio + '</span>';
        }

        function position(col) {
            var bar = col.querySelector('.dash-chart-bar') || col;
            var r = bar.getBoundingClientRect();
            tip.style.left = (r.left + r.width / 2) + 'px';
            tip.style.top  = r.top + 'px';
        }

        function show(col) {
            activeCol = col;
            render(col);
            position(col);
            tip.classList.add('is-visible');
        }

        function hide() {
            activeCol = null;
            tip.classList.remove('is-visible');
        }

        // Hover (desktop)
        scroll.addEventListener('mouseover', function (e) {
            var col = e.target.closest('.dash-chart-col');
            if (col) show(col);
        });
        scroll.addEventListener('mouseout', function (e) {
            var col = e.target.closest('.dash-chart-col');
            var to  = e.relatedTarget && e.relatedTarget.closest
                ? e.relatedTarget.closest('.dash-chart-col')
                : null;
            if (col && to !== col) hide();
        });

        // Tap / click (touch y desktop): alterna
        scroll.addEventListener('click', function (e) {
            var col = e.target.closest('.dash-chart-col');
            if (!col) return;
            if (activeCol === col) hide();
            else show(col);
        });

        // Cerrar al tocar fuera, al scrollear o al redimensionar
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.dash-chart-col')) hide();
        });
        scroll.addEventListener('scroll', hide, { passive: true });
        window.addEventListener('resize', hide);
    });
})();
