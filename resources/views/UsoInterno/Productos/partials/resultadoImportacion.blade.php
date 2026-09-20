{{-- Resultado de la última carga masiva. Se abre solo al volver del import:
     en rojo si hubo filas que no entraron, en verde si entraron todas. Lista
     las dos cosas: primero lo que falló (con el motivo), después lo que se cargó. --}}
@if (session('importacion'))
@php
    $errores    = session('importacion')['errores'];
    $creados    = session('importacion')['creados'];
    $conErrores = count($errores) > 0;
@endphp
<div class="modal fade" id="modalResultadoImportacion" tabindex="-1" aria-labelledby="modalResultadoImportacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:540px;">
        <div class="modal-content border-0 rounded-3 internal-import-result {{ $conErrores ? 'is-error' : 'is-ok' }}">
            <div class="modal-header border-0 align-items-start gap-3 pb-2">
                <span class="internal-import-result-icon" aria-hidden="true">
                    @if ($conErrores)
                    <x-fluentui-error-circle-20-o />
                    @else
                    <x-fluentui-checkmark-circle-20-o />
                    @endif
                </span>
                <div class="flex-grow-1">
                    <h2 class="internal-import-result-title m-0" id="modalResultadoImportacionLabel">
                        @if ($conErrores)
                        {{ count($errores) === 1 ? '1 fila no se cargó' : count($errores) . ' filas no se cargaron' }}
                        @else
                        {{ count($creados) === 1 ? 'Se cargó correctamente 1 producto' : 'Se cargaron correctamente ' . count($creados) . ' productos' }}
                        @endif
                    </h2>
                    <div class="internal-import-result-sub">
                        @if ($conErrores)
                        {{ count($creados) === 1 ? 'Se cargó 1 producto.' : 'Se cargaron ' . count($creados) . ' productos.' }}
                        Corregí las filas con error y subí sólo esas de nuevo.
                        @else
                        Todas las filas del archivo entraron.
                        @endif
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body pt-1 d-flex flex-column gap-3">
                @if ($conErrores)
                <section aria-labelledby="importNoCargados">
                    <h3 class="internal-import-section is-error" id="importNoCargados">
                        No se cargaron <span class="internal-import-count">{{ count($errores) }}</span>
                    </h3>
                    <ul class="internal-import-errors list-unstyled m-0">
                        @foreach ($errores as $error)
                        <li class="internal-import-error">
                            <div class="internal-import-error-meta">
                                <span class="internal-import-error-row">{{ $error['fila'] ? 'Fila ' . $error['fila'] : 'Archivo' }}</span>
                                @if ($error['codigo'] !== '')
                                <code class="internal-import-error-code">{{ $error['codigo'] }}</code>
                                @endif
                            </div>
                            <div class="internal-import-error-reason">{{ $error['motivo'] }}</div>
                        </li>
                        @endforeach
                    </ul>
                </section>
                @endif

                @if (count($creados))
                <section aria-labelledby="importCargados">
                    <h3 class="internal-import-section is-ok" id="importCargados">
                        Se cargaron <span class="internal-import-count">{{ count($creados) }}</span>
                    </h3>
                    <ul class="internal-import-created list-unstyled m-0">
                        @foreach ($creados as $creado)
                        <li class="internal-import-created-item">
                            <span class="internal-import-created-row">Fila {{ $creado['fila'] }}</span>
                            <code class="internal-import-error-code">{{ $creado['codigo'] }}</code>
                            <span class="internal-import-created-name" title="{{ $creado['nombre'] }}">{{ $creado['nombre'] }}</span>
                        </li>
                        @endforeach
                    </ul>
                </section>
                @endif
            </div>

            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-sm px-3 rounded-2 {{ $conErrores ? 'btn-outline-secondary' : 'btn-success' }}" data-bs-dismiss="modal" style="font-size:13px;">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Bootstrap se carga al final del layout: se espera al load para abrirlo.
    window.addEventListener('load', function () {
        var modal = document.getElementById('modalResultadoImportacion');
        if (modal && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });
</script>
@endif
