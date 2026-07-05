<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compra {{ $compra->numero }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #f59e0b; padding-bottom: 15px; }
        .header-left h1 { font-size: 24px; color: #f59e0b; margin-bottom: 5px; }
        .header-right { text-align: right; }
        .header-right .numero { font-size: 18px; font-weight: bold; color: #f59e0b; }
        .header-right .fecha { color: #666; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 8px; font-weight: bold; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
        .info-grid p { font-size: 11px; }
        .info-grid strong { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f59e0b; color: white; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; display: flex; justify-content: flex-end; }
        .totals-box { width: 250px; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; }
        .totals-row.total { border-top: 2px solid #f59e0b; padding-top: 8px; margin-top: 4px; font-weight: bold; font-size: 14px; color: #f59e0b; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>{{ config('app.name', 'SistemaAdmin') }}</h1>
            <p style="font-size: 11px; color: #666;">Sistema Administrativo</p>
        </div>
        <div class="header-right">
            <div class="numero">COMPRA</div>
            <div style="font-size: 16px; font-weight: bold; margin: 5px 0;">{{ $compra->numero }}</div>
            <div class="fecha">Fecha: {{ $compra->fecha }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Proveedor</div>
        <div class="info-grid">
            <p><strong>Nombre:</strong> {{ $compra->proveedor->nombre }}</p>
            <p><strong>CUIT:</strong> {{ $compra->proveedor->cuit ?? '—' }}</p>
            <p><strong>Email:</strong> {{ $compra->proveedor->email ?? '—' }}</p>
            <p><strong>Teléfono:</strong> {{ $compra->proveedor->telefono ?? '—' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($compra->detalles as $i => $detalle)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $detalle->producto->nombre }}</td>
                <td class="text-right">${{ number_format($detalle->precio, 2, ',', '.') }}</td>
                <td class="text-right">{{ $detalle->cantidad }}</td>
                <td class="text-right">${{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-box">
            <div class="totals-row total">
                <span>TOTAL</span>
                <span>${{ number_format($compra->total, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>{{ config('app.name') }} — Compra generada el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
