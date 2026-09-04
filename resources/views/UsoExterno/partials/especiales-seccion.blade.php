<section class="especiales-franja">
    <div class="container">

        <header class="especiales-franja-header">
            <p class="g-eyebrow">Línea adapta</p>
            <h2 class="g-title especiales-franja-titulo">
                También fabricamos {{ Str::lower($categoria->nombre) }} a medida
            </h2>
            <p class="especiales-franja-bajada">
                Piezas que no son de catálogo: se diseñan para tu obra y se adaptan en
                medidas, colores y terminaciones. Consultanos y las producimos.
            </p>
        </header>

        <div class="row g-4">
            @foreach($especiales as $especial)
            <div class="col-12 col-md-6 col-lg-4">
                <article class="especial-card" style="--card-delay: {{ $loop->index * 0.055 }}s">
                    <div class="especial-card-imagen">
                        <span class="producto-badge-especial">Adapta</span>
                        @php $imagenPrincipal = $especial->imagenes->first(); @endphp
                        @if($imagenPrincipal && $imagenPrincipal->ruta)
                        <img src="{{ $imagenPrincipal->ruta_miniatura }}"
                            alt="{{ $especial->nombre }}"
                            class="especial-img"
                            width="800" height="533"
                            loading="lazy" decoding="async">
                        @else
                        <div class="producto-img-placeholder">
                            <i class="bi bi-image"></i>
                        </div>
                        @endif
                    </div>
                    <div class="especial-card-body">
                        <h3 class="especial-nombre">{{ $especial->nombre }}</h3>
                        <p class="especial-descripcion">{{ Str::limit($especial->descripcion, 120) }}</p>
                        <span class="especial-cta">
                            Consultar <i class="bi bi-arrow-right"></i>
                        </span>
                    </div>
                    <a href="{{ route('productos.especial.show', $especial->id) }}"
                        class="stretched-link" aria-label="{{ $especial->nombre }}"></a>
                </article>
            </div>
            @endforeach
        </div>

        <div class="especiales-franja-pie">
            <a href="{{ route('productos.especiales') }}" class="g-link">
                Ver todos los productos de la línea adapta <i class="bi bi-arrow-right"></i>
            </a>
        </div>

    </div>
</section>