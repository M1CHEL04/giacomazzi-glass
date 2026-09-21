<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Catálogo de productos - Aberturas Giacomazzi</title>
    <style>
        /*
        Vista exclusiva para DomPDF: no extiende layouts.app-interno porque
        DomPDF no soporta flexbox/grid ni clamp() (usados en esos layouts y
        en externo.css). Paleta y tamaños quedan fijos acá, tomados de
        externo.css pero reescritos en pt para que impriman igual siempre.

        La portada usa su propio margen (@page :first): no necesita el hueco
        superior/inferior reservado para el header/footer del resto de hojas.
    */
        @page {
            margin: 145px 50px 90px 50px;
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

        h1,
        h2,
        h3,
        p {
            margin: 0;
            padding: 0;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .cat-tag {
            color: #287452;
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ── Portada ─────────────────────────────────────────────────────── */
        .portada {
            page-break-after: always;
            text-align: center;
            /*
            DomPDF no distribuye el height de una tabla de una sola fila
            (ignora el alto explícito para el cálculo de vertical-align), así
            que no hay forma de centrar con flex/tabla: el padding-top de acá
            está calculado a mano para este contenido puntual, no es un valor
            arbitrario. Si se agrega o saca contenido de la portada hay que
            volver a medir y ajustar este número.
        */
            padding-top: 350px;
        }

        .portada-logo {
            width: 440px;
        }

        .portada-titulo {
            font-size: 32pt;
            font-weight: bold;
            color: #23262a;
            margin-top: 50px;
        }

        .portada-acento {
            width: 70px;
            height: 4px;
            background-color: #287452;
            margin: 14px auto 0;
        }

        .portada-subtitulo {
            font-size: 14pt;
            color: #287452;
            margin-top: 14px;
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
            border-bottom: 2px solid #287452;
            padding-bottom: 8px;
        }

        .indice-categoria {
            font-size: 12pt;
            font-weight: bold;
            color: #1f5c3e;
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
            background-color: #287452;
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
            color: #287452;
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
        @if($logoPath)
        <img class="portada-logo" src="{{ $logoPath }}" alt="Aberturas Giacomazzi">
        @else
        <div class="portada-titulo" style="margin-top:0;">GIACOMAZZI</div>
        @endif
        <div class="portada-titulo">Catálogo de productos</div>
        <div class="portada-acento"></div>
        <div class="portada-subtitulo">Aberturas Giacomazzi</div>
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
                    <table class="producto-foto-vacia">
                        <tr>
                            <td>Sin foto</td>
                        </tr>
                    </table>
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

    <script type="text/php">
        if (isset($pdf)) {
        $logoPath = {!! json_encode($logoPath) !!};
        $contacto = {!! var_export($contacto, true) !!};

        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($logoPath, $contacto) {
            if ($pageNumber === 1) {
                return;
            }

            $gris  = [0.35, 0.35, 0.35];
            $verde = [0.157, 0.455, 0.322];

            // Logo del membrete: bastante más grande que un simple sello de
            // página, para que cada hoja se sienta tan de marca como la tapa.
            if ($logoPath) {
                $canvas->image($logoPath, 50, 28, 140, 23.77);
            }

            // Datos de contacto alineados a la derecha, a la altura del logo.
            $fontContacto = $fontMetrics->getFont('Helvetica', 'bold');
            $font = $fontMetrics->getFont('Helvetica');
            $lineas = array_values(array_filter([
                $contacto['telefono'] ? 'WhatsApp ' . $contacto['telefono'] : null,
                $contacto['email'] ?? null,
                $contacto['direccion'] ?? null,
            ]));

            $y = 29;
            foreach ($lineas as $i => $linea) {
                $f = $i === 0 ? $fontContacto : $font;
                $w = $fontMetrics->getTextWidth($linea, $f, 8);
                $canvas->text(545.28 - $w, $y, $linea, $f, 8, $gris);
                $y += 11;
            }

            // Regla que separa el membrete del contenido de la hoja.
            $canvas->line(50, 64, 545.28, 64, $verde, 1);

            // Pie de página.
            $texto = "Página $pageNumber de $pageCount";
            $wTexto = $fontMetrics->getTextWidth($texto, $font, 8);
            $canvas->text(545.28 - $wTexto, 794, $texto, $font, 8, [0.70, 0.70, 0.70]);
            $canvas->line(50, 786, 545.28, 786, [0.90, 0.90, 0.90], 0.75);
        });
    }
    </script>

</body>

</html>