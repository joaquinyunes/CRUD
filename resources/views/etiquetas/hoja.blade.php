<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Etiquetas</title>
<style>
    @page { size: A4; margin: 8mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; }
    .grid { display: grid; grid-template-columns: repeat({{ $columnas }}, 1fr); gap: 3mm; }
    .lbl { border: 1px dashed #bbb; padding: 3mm; text-align: center; page-break-inside: avoid; }
    .nom { font-size: 11px; font-weight: 700; line-height: 1.15; min-height: 26px; }
    .precio { font-size: 22px; font-weight: 800; margin: 1mm 0; }
    .bc { height: 42px; }
    .cod { font-family: 'Courier New', monospace; font-size: 10px; letter-spacing: 1px; }
    .neg { font-size: 8px; color: #666; }
    @media screen { body { background: #eee; padding: 1rem; } .grid { background: #fff; padding: 8mm; max-width: 210mm; margin: 0 auto; } }
</style>
</head>
<body onload="window.print()">
    <div class="grid">
        @foreach($etiquetas as $p)
            @php $codigo = $p->codigo_barra ?: $p->codigo; @endphp
            <div class="lbl">
                <div class="neg">{{ $negocio }}</div>
                <div class="nom">{{ $p->nombre }}</div>
                <div class="precio">{{ $simbolo }}{{ number_format($p->precio_venta, 2) }}</div>
                <div class="bc">{!! \App\Support\Codebar::code39Svg($codigo) !!}</div>
                <div class="cod">{{ $codigo }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
