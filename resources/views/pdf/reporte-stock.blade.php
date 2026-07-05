<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Stock Crítico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #ef4444; padding-bottom: 15px; }
        .header h1 { font-size: 22px; color: #ef4444; margin-bottom: 3px; }
        .header p { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #ef4444; color: white; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .text-right { text-align: right; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Stock Crítico</h1>
        <p>{{ config('app.name') }} — Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if($productos->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="text-right">Stock Actual</th>
                <th class="text-right">Stock Mínimo</th>
                <th class="text-right">Déficit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $producto)
            <tr>
                <td>{{ $producto->codigo }}</td>
                <td>{{ $producto->nombre }}</td>
                <td>{{ $producto->categoria->nombre ?? '—' }}</td>
                <td class="text-right" style="color: #ef4444; font-weight: bold;">{{ $producto->stock }}</td>
                <td class="text-right">{{ $producto->stock_minimo }}</td>
                <td class="text-right" style="color: #ef4444;">-{{ $producto->stock_minimo - $producto->stock }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align:center; color:#22c55e; font-size: 14px; margin-top: 30px;">✓ Todos los productos tienen stock suficiente.</p>
    @endif

    <div class="footer">
        <p>Total productos con stock crítico: {{ $productos->count() }}</p>
    </div>
</body>
</html>
