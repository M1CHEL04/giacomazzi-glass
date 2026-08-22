@php
    $waMensaje = '¡Hola! Te escribo desde la web de Aberturas Giacomazzi, quería hacer una consulta.';
@endphp
@if(whatsapp_numero())
<a href="{{ whatsapp_href($waMensaje) }}"
    class="whatsapp-float" target="_blank" rel="noopener"
    aria-label="Contactar por WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>
@endif
