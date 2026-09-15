<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Catálogo de productos estándar</title>
<style>
    /*
        Copia de catalogo.blade.php sin ningún elemento de marca (logo, nombre
        "Aberturas Giacomazzi", verde corporativo): paleta neutra en gris/negro.
        Ver ese archivo para las notas sobre las limitaciones de DomPDF que
        explican por qué está todo en pt/px fijos y por qué el paginado se
        dibuja con PHP embebido en vez de position:fixed.
    */
    @page {
        margin: 90px 50px 90px 50px;
    }

    @page :first {
        margin: 50px;
    }

    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #3f3f3f;
        font-size: 10pt;
        line-height: 1.4;
    }

    h1, h2, h3, p {
        margin: 0;
        padding: 0;
    }

    a {
        color: inherit;
        text-decoration: none;
    }

    .cat-tag {
        color: #5a5a5a;
        font-size: 9pt;
        font-weight: bold;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* ── Portada ─────────────────────────────────────────────────────── */
    .portada {
        page-break-after: always;
        text-align: center;
        /* Ver la nota en catalogo.blade.php: DomPDF no centra verticalmente
           con tablas, así que este padding está calculado a mano. */
        padding-top: 441px;
    }

    .portada-titulo {
        font-size: 32pt;
        font-weight: bold;
        color: #23262a;
    }

    .portada-fecha {
        font-size: 9pt;
        color: #b3b2b2;
        margin-top: 60px;
    }

    /* ── Índice ──────────────────────────────────────────────────────── */
    .indice-titulo {
        font-size: 20pt;
        font-weight: bold;
        color: #23262a;
        margin-bottom: 24px;
        border-bottom: 2px solid #5a5a5a;
        padding-bottom: 8px;
    }

    .indice-categoria {
        font-size: 12pt;
        font-weight: bold;
        color: #23262a;
        margin-top: 16px;
    }

    .indice-producto {
        font-size: 10pt;
        color: #3f3f3f;
        padding: 3px 0 3px 16px;
    }

    /* ── Capítulos (categoría) ──────────────────────────────────────── */
    .capitulo-titulo {
        page-break-before: always;
        font-size: 20pt;
        font-weight: bold;
        color: #ffffff;
        background-color: #3f3f3f;
        padding: 14px 16px;
        margin: 0 0 24px 0;
    }

    /* ── Producto ────────────────────────────────────────────────────── */
    .producto {
        page-break-inside: avoid;
        margin-bottom: 28px;
        border-bottom: 1px solid #e5e4e4;
        padding-bottom: 20px;
    }

    .producto-tabla {
        width: 100%;
        border-collapse: collapse;
    }

    .producto-foto-celda {
        width: 190px;
        vertical-align: top;
        padding-right: 20px;
    }

    .producto-foto {
        width: 190px;
        height: 190px;
        object-fit: cover;
        border: 1px solid #e5e4e4;
    }

    .producto-foto-vacia {
        width: 188px;
        height: 188px;
        border: 1px dashed #b3b2b2;
        color: #b3b2b2;
        font-size: 8.5pt;
        text-align: center;
        vertical-align: middle;
    }

    .producto-info-celda {
        vertical-align: top;
    }

    .producto-nombre {
        font-size: 14pt;
        font-weight: bold;
        color: #23262a;
    }

    .producto-codigo {
        font-size: 8.5pt;
        color: #b3b2b2;
        margin-top: 2px;
    }

    .producto-descripcion {
        font-size: 10pt;
        color: #3f3f3f;
        margin-top: 8px;
    }

    .producto-seccion-label {
        font-size: 8pt;
        font-weight: bold;
        color: #5a5a5a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 12px;
    }

    .producto-tecnica {
        font-size: 9pt;
        color: #3f3f3f;
        margin-top: 4px;
    }

    .variante-fila {
        font-size: 9pt;
        margin-top: 4px;
    }

    .variante-nombre {
        font-weight: bold;
        color: #23262a;
    }

    .variante-opcion {
        display: inline;
        background-color: #f6f8f9;
        border: 1px solid #e5e4e4;
        border-radius: 3px;
        padding: 1px 6px;
        margin-right: 4px;
        font-size: 8.5pt;
    }
</style>
</head>
<body>

    {{-- Portada --}}
    <div class="portada">
        <div class="portada-titulo">Catálogo de productos estándar</div>
        <div class="portada-fecha">Generado el {{ $fecha }}</div>
    </div>

    {{-- Índice --}}
    <div class="indice">
        <div class="indice-titulo">Índice</div>
        @foreach($capitulos as $capitulo)
            <div class="indice-categoria">
                <a href="#cat-{{ $capitulo['categoria']->id }}">{{ $capitulo['categoria']->nombre }}</a>
            </div>
            @foreach($capitulo['productos'] as $item)
                <div class="indice-producto">
                    <a href="#prod-{{ $item['producto']->id }}">{{ $item['producto']->nombre }}</a>
                </div>
            @endforeach
        @endforeach
    </div>

    {{-- Capítulos --}}
    @foreach($capitulos as $capitulo)
        <h1 id="cat-{{ $capitulo['categoria']->id }}" class="capitulo-titulo">{{ $capitulo['categoria']->nombre }}</h1>

        @foreach($capitulo['productos'] as $item)
            @php $producto = $item['producto']; @endphp
            <div id="prod-{{ $producto->id }}" class="producto">
                <table class="producto-tabla">
                    <tr>
                        <td class="producto-foto-celda">
                            @if($item['portada'])
                                <img class="producto-foto" src="{{ $item['portada']->ruta }}" alt="{{ $producto->nombre }}">
                            @else
                                <table class="producto-foto-vacia"><tr><td>Sin foto</td></tr></table>
                            @endif
                        </td>
                        <td class="producto-info-celda">
                            <div class="cat-tag">{{ $capitulo['categoria']->nombre }}</div>
                            <div class="producto-nombre">{{ $producto->nombre }}</div>
                            @if($producto->codigo)
                                <div class="producto-codigo">Código: {{ $producto->codigo }}</div>
                            @endif

                            @if($producto->descripcion)
                                <p class="producto-descripcion">{{ $producto->descripcion }}</p>
                            @endif

                            @if($producto->descripcion_tecnica)
                                <div class="producto-seccion-label">Descripción técnica</div>
                                <p class="producto-tecnica">{{ $producto->descripcion_tecnica }}</p>
                            @endif

                            @if($item['variantes']->isNotEmpty())
                                <div class="producto-seccion-label">Variantes</div>
                                @foreach($item['variantes'] as $variante)
                                    <div class="variante-fila">
                                        <span class="variante-nombre">{{ $variante['nombre'] }}:</span>
                                        @foreach($variante['opciones'] as $opcion)
                                            <span class="variante-opcion">{{ $opcion }}</span>
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    @endforeach

    {{--
        "Página X de Y" en todas las hojas salvo la portada. Ver
        catalogo.blade.php para la explicación de por qué esto va con
        page_script() de DomPDF (PHP embebido) en vez de un contador simple:
        es lo único que conoce el total real de páginas y permite saltear la
        portada, ejecutando una única vez al final del documento.
    --}}
    <script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            if ($pageNumber === 1) {
                return;
            }
            $font = $fontMetrics->getFont('Helvetica');
            $canvas->text(470, 794, "Página $pageNumber de $pageCount", $font, 8, [0.70, 0.70, 0.70]);
            $canvas->line(50, 786, 545.28, 786, [0.90, 0.90, 0.90], 0.75);
        });
    }
    </script>

</body>
</html>
