@php
    $waNumero  = preg_replace('/\D/', '', config('app.whatsapp_number', ''));
    $waMensaje = '¡Hola! Te escribo desde la web de Giacomazzi Glass, quería hacer una consulta.';
@endphp
@if($waNumero)
<a href="https://wa.me/{{ $waNumero }}?text={{ rawurlencode($waMensaje) }}"
    class="whatsapp-float" target="_blank" rel="noopener"
    aria-label="Contactar por WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>
@endif
