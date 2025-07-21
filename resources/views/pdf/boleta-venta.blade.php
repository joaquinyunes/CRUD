<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Boleta {{ $venta->numero }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }

        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #059669; padding-bottom: 12px; }
        .header h1 { font-size: 18px; color: #059669; margin-bottom: 2px; }
        .header .empresa { font-size: 10px; color: #666; }
        .header .tipo { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #999; margin-top: 6px; }
        .header .numero { font-size: 16px; font-weight: bold; color: #059669; margin-top: 2px; }
        .header .fecha { font-size: 10px; color: #666; margin-top: 2px; }

        .info-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px; padding: 2px 0; border-bottom: 1px dotted #e5e7eb; }
        .info-row .label { color: #666; }
        .info-row .value { font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #059669; color: white; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .text-right { text-align: right; }

        .total-box { text-align: right; margin-top: 10px; padding: 8px 12px; background: #f0fdf4; border-radius: 6px; }
        .total-box .total-label { font-size: 11px; color: #666; }
        .total-box .total-value { font-size: 18px; font-weight: bold; color: #059669; }

        .payment-info { margin-top: 12px; font-size: 10px; color: #666; }
        .payment-info strong { color: #333; }

        .footer { margin-top: 25px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa['empresa_nombre'] ?? config('app.name', 'SistemaAdmin') }}</h1>
        @if(!empty($empresa['empresa_ruc']))
            <div class="empresa">RUC/NIT: {{ $empresa['empresa_ruc'] }}</div>
        @endif
        @if(!empty($empresa['empresa_direccion']))
            <div class="empresa">{{ $empresa['empresa_direccion'] }}</div>
        @endif
        <div class="tipo">Boleta de Venta</div>
        <div class="numero">{{ $venta->numero }}</div>
        <div class="fecha">{{ $venta->fecha->format('d/m/Y H:i') }}</div>
    </div>

    <div style="margin-bottom: 15px;">
        <div class="info-row">
            <span class="label">Cliente:</span>
            <span class="value">{{ $venta->cliente->nombre }} {{ $venta->cliente->apellido }}</span>
        </div>
        @if(!empty($venta->cliente->documento))
        <div class="info-row">
            <span class="label">Documento:</span>
            <span class="value">{{ $venta->cliente->documento }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="label">Vendedor:</span>
            <span class="value">{{ $venta->user->name ?? '—' }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalles as $detalle)
            <tr>
                <td>{{ $detalle->producto->nombre }}</td>
                <td class="text-right">{{ $detalle->cantidad }}</td>
                <td class="text-right">{{ $moneda }}{{ number_format($detalle->precio, 2, ',', '.') }}</td>
                <td class="text-right">{{ $moneda }}{{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <div class="total-label">TOTAL A PAGAR</div>
        <div class="total-value">{{ $moneda }}{{ number_format($venta->total_final ?? $venta->total, 2, ',', '.') }}</div>
    </div>

    @if($venta->pagos && $venta->pagos->count())
    <div class="payment-info">
        <strong>Medios de pago:</strong>
        @foreach($venta->pagos as $pago)
            {{ $pago->metodoPago->nombre ?? '—' }}: {{ $moneda }}{{ number_format($pago->monto, 2, ',', '.') }}@if(!$loop->last), @endif
        @endforeach
    </div>
    @endif

    <div class="footer">
        {{ $empresa['empresa_nombre'] ?? config('app.name') }} — Boleta generada el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
