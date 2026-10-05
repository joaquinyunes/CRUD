@props([
    'name',
    'url',
    'valor' => null,
    'etiqueta' => null,
    'placeholder' => 'Escribí para buscar…',
    'requerido' => false,
    'minimo' => 1,
    'alElegir' => null,
])

{{--
    Campo de autocompletado.

    Viaja al servidor como el campo oculto con el id elegido; el input visible es
    solo para buscar. Reemplaza a los <select> que traían el catálogo completo
    embebido en el HTML.
--}}
<div class="r-buscador"
     data-buscador
     data-url="{{ $url }}"
     data-minimo="{{ $minimo }}"
     @if($alElegir) data-al-elegir="{{ $alElegir }}" @endif>

    <input type="hidden" name="{{ $name }}" value="{{ $valor }}" data-buscador-valor>

    <input type="text"
           class="r-input"
           value="{{ $etiqueta }}"
           placeholder="{{ $placeholder }}"
           autocomplete="off"
           role="combobox"
           aria-expanded="false"
           aria-autocomplete="list"
           @if($requerido && ! $valor) required @endif
           data-buscador-texto>

    <ul class="r-buscador-lista" role="listbox" hidden data-buscador-lista></ul>
</div>
