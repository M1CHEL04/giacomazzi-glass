(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var btnSeleccionar = document.getElementById('btn-seleccionar-hero');
        var fileInput      = document.getElementById('imagen_hero');
        var newPreview     = document.getElementById('hero-new-preview');
        var newImg         = document.getElementById('hero-new-img');
        var btnQuitarNew   = document.getElementById('btn-quitar-hero-new');
        var btnQuitarExist = document.getElementById('btn-quitar-hero');
        var previewExist   = document.getElementById('hero-preview-existente');
        var deleteInput    = document.getElementById('eliminar-imagen-hero-input');

        if (!fileInput) return;

        // Abrir selector al hacer click en el botón
        if (btnSeleccionar) {
            btnSeleccionar.addEventListener('click', function () {
                fileInput.click();
            });
        }

        // Selección de archivo → mostrar preview
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0) {
                mostrarPreview(fileInput.files[0]);
            }
        });

        // Quitar nueva imagen seleccionada
        if (btnQuitarNew) {
            btnQuitarNew.addEventListener('click', function () {
                fileInput.value = '';
                newPreview.style.display = 'none';
                if (newImg) newImg.src = '';
            });
        }

        // Quitar imagen existente (marcar para eliminar en backend)
        if (btnQuitarExist && previewExist && deleteInput) {
            btnQuitarExist.addEventListener('click', function () {
                previewExist.style.display = 'none';
                deleteInput.value = '1';
            });
        }

        function mostrarPreview(file) {
            if (!file || !file.type.startsWith('image/')) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                if (newImg) newImg.src = e.target.result;
                if (newPreview) newPreview.style.display = '';
            };
            reader.readAsDataURL(file);
        }
    });
})();
