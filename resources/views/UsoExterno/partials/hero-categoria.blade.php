{{-- Hero de una categoría concreta.
     Usa .g-hero (externo.css), el mismo componente que el catálogo
     completo y Nosotros: así los tres miden y arrancan igual. Antes
     tenía caja propia con margin-top: calc(-1.5rem - 1px), heredado de
     cuando .external-main usaba 1.5rem en todos los anchos; en teléfono
     eso lo metía 9px debajo del navbar. --}}
<section class="g-hero">
    @if($categoria->imagen_hero)
    <img src="{{ asset($categoria->imagen_hero) }}" alt="" class="g-hero-bg" aria-hidden="true">
    @endif
    <span class="g-hero-scrim"></span>
    <div class="container g-hero-inner">
        <p class="g-hero-eyebrow">Aberturas Giacomazzi</p>
        <h1 class="g-hero-title">{{ $categoria->nombre }}</h1>
    </div>
</section>
