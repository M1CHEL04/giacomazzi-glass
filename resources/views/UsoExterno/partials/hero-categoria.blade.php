<section class="categoria-hero">
    @if($categoria->imagen_hero)
    <img src="{{ asset('storage/' . $categoria->imagen_hero) }}"
        alt="{{ $categoria->nombre }}"
        class="categoria-hero-bg">
    @endif
    <div class="container categoria-hero-content">
        <div class="row">
            <div class="col-lg-8 col-xl-7">
                <p class="categoria-hero-eyebrow">Catálogo de productos</p>
                <h1 class="categoria-hero-title">{{ $categoria->nombre }}</h1>
                <div class="categoria-hero-meta">
                    <span class="categoria-hero-badge">
                        <i class="bi bi-box-seam"></i>
                        {{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}
                    </span>
                    @if($variantes->count() > 0)
                    <span class="categoria-hero-badge">
                        <i class="bi bi-sliders"></i>
                        {{ $variantes->count() }} {{ $variantes->count() === 1 ? 'variante' : 'variantes' }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>