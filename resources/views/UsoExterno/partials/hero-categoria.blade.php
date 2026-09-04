<section class="g-hero">
    @if($categoria->imagen_hero)
    <img src="{{ imagen_src($categoria->imagen_hero, 1400) }}"
        srcset="{{ imagen_srcset($categoria->imagen_hero) }}"
        sizes="100vw"
        alt="" class="g-hero-bg" aria-hidden="true"
        width="2400" height="745"
        fetchpriority="high" decoding="async">
    @endif
    <span class="g-hero-scrim"></span>
    <div class="container g-hero-inner">
        <p class="g-hero-eyebrow">{{ ($esLineaSingular ?? false) ? 'Línea adapta' : 'Línea estándar' }}</p>
        <h1 class="g-hero-title">{{ $categoria->nombre }}</h1>
    </div>
</section>