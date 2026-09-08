<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket {{ $venta->numero }}</title>
<style>
    @page { size: 80mm auto; margin: 0; }
    * { box-sizing: border-box; }
    body { width: 80mm; margin: 0; padding: 4mm; font-family: 'Courier New', monospace; font-size: 12px; color: #000; }
    h1 { font-size: 14px; text-align: center; margin: 0 0 2mm; }
    .c { text-align: center; }
    .r { text-align: right; }
    hr { border: 0; border-top: 1px dashed #000; margin: 2mm 0; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 0.5mm 0; }
    .tot { font-size: 14px; font-weight: bold; }
    @media screen { body { margin: 1rem auto; box-shadow: 0 0 0 1px #ccc; } }
</style>
</head>
<body onload="window.print()">
    <h1>{{ $negocio['nombre'] }}</h1>
    @if($negocio['direccion'])<div class="c">{{ $negocio['direccion'] }}</div>@endif
    @if($negocio['cuit'])<div class="c">CUIT {{ $negocio['cuit'] }}</div>@endif
    <hr>
    <div>Comprobante: {{ $venta->numero }}</div>
    <div>Fecha: {{ $venta->created_at->format('d/m/Y H:i') }}</div>
    <div>Cajero: {{ $venta->user?->name }}</div>
    <div>Cliente: {{ $venta->cliente ? trim($venta->cliente->nombre.' '.$venta->cliente->apellido) : 'Consumidor final' }}</div>
    <hr>
    <table>
        @foreach($venta->detalles as $d)
        <tr>
            <td colspan="2">{{ $d->producto?->nombre ?? 'Producto' }}</td>
        </tr>
        <tr>
            <td>{{ rtrim(rtrim(number_format($d->cantidad, 3, ',', ''), '0'), ',') }} x {{ $negocio['simbolo'] }}{{ number_format($d->precio, 2) }}</td>
            <td class="r">{{ $negocio['simbolo'] }}{{ number_format($d->subtotal, 2) }}</td>
        </tr>
        @if($d->descuento_promo > 0)
        <tr><td colspan="2" style="font-size:11px;">  promo -{{ $negocio['simbolo'] }}{{ number_format($d->descuento_promo, 2) }}</td></tr>
        @endif
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Subtotal</td><td class="r">{{ $negocio['simbolo'] }}{{ number_format($venta->subtotal, 2) }}</td></tr>
        @if($venta->descuento > 0)<tr><td>Descuento</td><td class="r">-{{ $negocio['simbolo'] }}{{ number_format($venta->descuento, 2) }}</td></tr>@endif
        @if($venta->impuesto > 0)<tr><td>IVA</td><td class="r">{{ $negocio['simbolo'] }}{{ number_format($venta->impuesto, 2) }}</td></tr>@endif
        <tr class="tot"><td>TOTAL</td><td class="r">{{ $negocio['simbolo'] }}{{ number_format($venta->total_final, 2) }}</td></tr>
    </table>
    <hr>
    <table>
        @foreach($venta->pagos as $p)
        <tr><td>{{ $p->metodoPago?->nombre ?? 'Pago' }}</td><td class="r">{{ $negocio['simbolo'] }}{{ number_format($p->monto, 2) }}</td></tr>
        @endforeach
        @if($venta->vuelto > 0)
        <tr><td>Recibido</td><td class="r">{{ $negocio['simbolo'] }}{{ number_format($venta->recibido, 2) }}</td></tr>
        <tr><td>Vuelto</td><td class="r">{{ $negocio['simbolo'] }}{{ number_format($venta->vuelto, 2) }}</td></tr>
        @endif
    </table>
    <hr>
    <div class="c">¡Gracias por su compra!</div>
</body>
</html>
