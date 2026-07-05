<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Catálogo de Productos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #4f46e5; padding-bottom: 15px; }
        .header h1 { font-size: 22px; color: #4f46e5; margin-bottom: 3px; }
        .header p { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4f46e5; color: white; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-critico { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Catálogo de Productos</h1>
        <p>{{ config('app.name') }} — Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Marca</th>
                <th class="text-right">Precio Venta</th>
                <th class="text-right">Stock</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $producto)
            <tr>
                <td>{{ $producto->codigo }}</td>
                <td>{{ $producto->nombre }}</td>
                <td>{{ $producto->categoria->nombre ?? '—' }}</td>
                <td>{{ $producto->marca ?? '—' }}</td>
                <td class="text-right">${{ number_format($producto->precio_venta, 2, ',', '.') }}</td>
                <td class="text-right">{{ $producto->stock }}</td>
                <td>
                    @if($producto->stock <= 0)
                        <span class="badge badge-critico">Agotado</span>
                    @elseif($producto->stock <= $producto->stock_minimo)
                        <span class="badge badge-warn">Crítico</span>
                    @else
                        <span class="badge badge-ok">OK</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:#999;">No hay productos registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Total: {{ $productos->count() }} productos</p>
    </div>
</body>
</html>
