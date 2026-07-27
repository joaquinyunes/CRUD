<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura {{ $venta->numero }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 3px solid #4f46e5; padding-bottom: 15px; }
        .header-left { flex: 1; }
        .header-left h1 { font-size: 22px; color: #4f46e5; margin-bottom: 3px; }
        .header-left .empresa-info { font-size: 10px; color: #666; line-height: 1.6; }
        .header-right { text-align: right; }
        .header-right .tipo-comprobante { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #999; margin-bottom: 2px; }
        .header-right .numero { font-size: 20px; font-weight: bold; color: #4f46e5; }
        .header-right .fecha { font-size: 11px; color: #666; margin-top: 4px; }
        .header-right .estado { display: inline-block; margin-top: 6px; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .estado-completada { background: #d1fae5; color: #065f46; }
        .estado-pendiente { background: #fef3c7; color: #92400e; }
        .estado-cancelada { background: #fee2e2; color: #991b1b; }

        .section { margin-bottom: 18px; }
        .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #4f46e5; margin-bottom: 6px; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3px 15px; }
        .info-grid p { font-size: 11px; line-height: 1.6; }
        .info-grid strong { color: #333; }

        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #4f46e5; color: white; padding: 7px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals-section { margin-top: 15px; display: flex; justify-content: space-between; align-items: flex-start; }
        .payment-box { width: 48%; }
        .totals-box { width: 48%; }
        .totals-box .box-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #4f46e5; font-weight: bold; margin-bottom: 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .totals-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 11px; }
        .totals-row.sub { color: #666; }
        .totals-row.discount { color: #dc2626; }
        .totals-row.tax { color: #666; }
        .totals-row.total { border-top: 2px solid #4f46e5; padding-top: 6px; margin-top: 4px; font-weight: bold; font-size: 14px; color: #4f46e5; }

        .payment-table { width: 100%; border-collapse: collapse; }
        .payment-table th { background: #6366f1; padding: 5px 8px; font-size: 9px; }
        .payment-table td { padding: 5px 8px; font-size: 10px; border-bottom: 1px solid #e5e7eb; }

        .vendedor-section { margin-top: 12px; }
        .vendedor-section .section-title { margin-bottom: 4px; }
        .vendedor-section p { font-size: 11px; color: #666; }

        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #e5e7eb; padding-top: 10px; line-height: 1.6; }

        .no-pagos { font-size: 10px; color: #999; font-style: italic; padding: 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>{{ $empresa['empresa_nombre'] ?? config('app.name', 'SistemaAdmin') }}</h1>
            <div class="empresa-info">
                @if(!empty($empresa['empresa_ruc']))<p><strong>RUC/NIT:</strong> {{ $empresa['empresa_ruc'] }}</p>@endif
                @if(!empty($empresa['empresa_direccion']))<p><strong>Dirección:</strong> {{ $empresa['empresa_direccion'] }}</p>@endif
                @if(!empty($empresa['empresa_telefono']))<p><strong>Teléfono:</strong> {{ $empresa['empresa_telefono'] }}</p>@endif
                @if(!empty($empresa['empresa_email']))<p><strong>Email:</strong> {{ $empresa['empresa_email'] }}</p>@endif
            </div>
        </div>
        <div class="header-right">
            <div class="tipo-comprobante">Factura de Venta</div>
            <div class="numero">{{ $venta->numero }}</div>
            <div class="fecha">Fecha: {{ $venta->fecha->format('d/m/Y') }}</div>
            <div class="estado estado-{{ $venta->estado }}">{{ $venta->estado }}</div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; gap: 20px;">
        <div class="section" style="flex: 1;">
            <div class="section-title">Datos del Cliente</div>
            <div class="info-grid">
                <p><strong>Nombre:</strong> {{ $venta->cliente->nombre }} {{ $venta->cliente->apellido }}</p>
                @if(!empty($venta->cliente->documento))<p><strong>Documento:</strong> {{ $venta->cliente->documento }}</p>@endif
                @if(!empty($venta->cliente->email))<p><strong>Email:</strong> {{ $venta->cliente->email }}</p>@endif
                @if(!empty($venta->cliente->telefono))<p><strong>Teléfono:</strong> {{ $venta->cliente->telefono }}</p>@endif
                @if(!empty($venta->cliente->direccion))<p><strong>Dirección:</strong> {{ $venta->cliente->direccion }}</p>@endif
            </div>
        </div>
        <div class="section" style="width: 200px;">
            <div class="vendedor-section">
                <div class="section-title">Vendedor</div>
                <p>{{ $venta->user->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalle de Productos</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:30px;">#</th>
                    <th>Producto</th>
                    <th class="text-right" style="width:80px;">Precio Unit.</th>
                    <th class="text-right" style="width:60px;">Cantidad</th>
                    <th class="text-right" style="width:90px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $i => $detalle)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $detalle->producto->nombre }}</td>
                    <td class="text-right">{{ $moneda }}{{ number_format($detalle->precio, 2, ',', '.') }}</td>
                    <td class="text-right">{{ $detalle->cantidad }}</td>
                    <td class="text-right">{{ $moneda }}{{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals-section">
        @if($venta->pagos && $venta->pagos->count())
        <div class="payment-box">
            <div class="box-title" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #4f46e5; font-weight: bold; margin-bottom: 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">Medios de Pago</div>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Método</th>
                        <th class="text-right">Monto</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->pagos as $pago)
                    <tr>
                        <td>{{ $pago->metodoPago->nombre ?? '—' }}</td>
                        <td class="text-right">{{ $moneda }}{{ number_format($pago->monto, 2, ',', '.') }}</td>
                        <td>{{ $pago->referencia ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="payment-box">
            <div class="box-title" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #4f46e5; font-weight: bold; margin-bottom: 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">Medios de Pago</div>
            <p class="no-pagos">Sin medios de pago registrados</p>
        </div>
        @endif

        <div class="totals-box">
            <div class="box-title">Resumen</div>
            <div class="totals-row sub">
                <span>Subtotal</span>
                <span>{{ $moneda }}{{ number_format($venta->subtotal ?? $venta->total, 2, ',', '.') }}</span>
            </div>
            @if(($venta->descuento ?? 0) > 0)
            <div class="totals-row discount">
                <span>Descuento</span>
                <span>-{{ $moneda }}{{ number_format($venta->descuento, 2, ',', '.') }}</span>
            </div>
            @endif
            @if(($venta->impuesto ?? 0) > 0)
            <div class="totals-row tax">
                <span>Impuesto (IVA)</span>
                <span>{{ $moneda }}{{ number_format($venta->impuesto, 2, ',', '.') }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>TOTAL</span>
                <span>{{ $moneda }}{{ number_format($venta->total_final ?? $venta->total, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>{{ $empresa['empresa_nombre'] ?? config('app.name') }}</strong></p>
        @if(!empty($empresa['empresa_ruc']))<p>RUC/NIT: {{ $empresa['empresa_ruc'] }} —</p> @endif
        <p>Factura generada el {{ now()->format('d/m/Y H:i') }} — Sistema Administrativo</p>
    </div>
</body>
</html>
