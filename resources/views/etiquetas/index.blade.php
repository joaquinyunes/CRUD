@extends('layouts.app')

@section('page_title', 'Etiquetas de góndola')

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-6">
    <h1 class="r-display-l">Etiquetas de góndola</h1>
</div>

<form method="GET" class="r-card-flat r-mb-4 r-flex r-gap-3" style="align-items:flex-end;flex-wrap:wrap;">
    <div style="flex:1;min-width:180px;"><label class="r-label">Buscar</label><input type="text" name="buscar" value="{{ request('buscar') }}" class="r-input"></div>
    <div><label class="r-label">Categoría</label>
        <select name="categoria_id" class="r-select">
            <option value="">Todas</option>
            @foreach($categorias as $c)<option value="{{ $c->id }}" @selected(request('categoria_id') == $c->id)>{{ $c->nombre }}</option>@endforeach
        </select>
    </div>
    <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
</form>

<form method="POST" action="{{ route('etiquetas.imprimir') }}" target="_blank">
    @csrf
    <div class="r-card-flat">
        <div class="r-flex r-gap-3 r-mb-3" style="align-items:flex-end;">
            <div style="width:8rem;"><label class="r-label">Columnas</label>
                <select name="columnas" class="r-select"><option>3</option><option>2</option><option>4</option><option>5</option></select>
            </div>
            <button class="r-btn r-btn-primary r-btn-sm">Generar hoja para imprimir</button>
        </div>
        <div style="overflow-x:auto;max-height:60vh;">
            <table class="r-table">
                <thead><tr><th style="width:3rem;"></th><th>Producto</th><th>Código</th><th style="text-align:right;">Precio</th><th style="width:6rem;text-align:right;">Copias</th></tr></thead>
                <tbody>
                @foreach($productos as $i => $p)
                    <tr>
                        <td><input type="checkbox" name="items[{{ $i }}][incluir]" onchange="this.closest('tr').querySelector('input[type=number]').disabled=!this.checked"></td>
                        <td>{{ $p->nombre }}<input type="hidden" name="items[{{ $i }}][id]" value="{{ $p->id }}"></td>
                        <td class="r-caption">{{ $p->codigo_barra ?: $p->codigo }}</td>
                        <td style="text-align:right;">${{ number_format($p->precio_venta, 2) }}</td>
                        <td style="text-align:right;"><input type="number" name="items[{{ $i }}][copias]" value="1" min="1" max="200" class="r-input r-input-sm" style="width:4.5rem;text-align:right;" disabled></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>

<p class="r-caption r-mt-3" style="text-transform:none;letter-spacing:0;">
    Tildá los productos, ajustá las copias y generá la hoja. Se abre lista para imprimir (Code 39).
</p>

<script>
// Sólo enviar las filas tildadas: al submit, quitamos name a las no incluidas.
document.querySelector('form[action$="imprimir"]').addEventListener('submit', function () {
    this.querySelectorAll('tbody tr').forEach(tr => {
        const chk = tr.querySelector('input[type=checkbox]');
        if (!chk.checked) tr.querySelectorAll('input').forEach(inp => inp.removeAttribute('name'));
    });
});
</script>
@endsection
