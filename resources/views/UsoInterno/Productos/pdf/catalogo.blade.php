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
    */
    @page {
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
        padding-top: 220px;
    }

    .portada-logo {
        width: 260px;
    }

    .portada-titulo {
        font-size: 30pt;
        font-weight: bold;
        color: #23262a;
        margin-top: 50px;
    }

    .portada-subtitulo {
        font-size: 13pt;
        color: #287452;
        margin-top: 10px;
    }

    .portada-fecha {
        font-size: 9pt;
        color: #b3b2b2;
        margin-top: 60px;
    }

    .portada-franja {
        width: 100%;
        height: 18px;
        background-color: #287452;
        margin-top: 100px;
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

    .producto-precio {
        margin-top: 12px;
        border: 1px solid #8ab9a5;
        background-color: #f6f8f9;
        padding: 6px 10px;
        font-size: 9pt;
        color: #8ab9a5;
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
        <div class="portada-subtitulo">Aberturas Giacomazzi</div>
        <div class="portada-fecha">Generado el {{ $fecha }}</div>
        <div class="portada-franja"></div>
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

                            <div class="producto-precio">Precio: a definir</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    @endforeach

</body>
</html>
