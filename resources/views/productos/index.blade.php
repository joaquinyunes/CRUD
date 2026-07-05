@extends('layouts.app')

@section('page_title', 'Productos')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Productos</h2>
    <div class="r-flex r-gap-3">
        @if (auth()->user()->role?->tienePermiso('productos.exportar'))
            <a href="{{ route('exportar.productos', ['formato' => 'xlsx']) }}" class="r-btn r-btn-ghost r-btn-sm">Exportar Excel</a>
        @endif
        @if (auth()->user()->role?->tienePermiso('productos.crear'))
            <a href="{{ route('productos.create') }}" class="r-btn r-btn-primary r-btn-sm">Nuevo producto</a>
        @endif
    </div>
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('productos.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, código o marca…" class="r-input">
        </div>
        <div>
            <label class="r-label">Categoría</label>
            <select name="categoria_id" class="r-select">
                <option value="">Todas</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="r-label">Estado</label>
            <select name="estado" class="r-select">
                <option value="">Todos</option>
                <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if(request()->hasAny(['buscar','categoria_id','estado']))
            <a href="{{ route('productos.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th style="width:48px;"></th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Marca</th>
                    <th style="text-align:right;">P. Compra</th>
                    <th style="text-align:right;">P. Venta</th>
                    <th style="text-align:center;">Stock</th>
                    <th style="text-align:center;">Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $producto)
                    <tr>
                        <td>
                            @if($producto->imagen)
                                <img src="{{ asset('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}" style="width:40px;height:40px;object-fit:cover;border-radius:8px;">
                            @else
                                <div style="width:40px;height:40px;background:var(--color-moss-pale);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                    <svg width="16" height="16" fill="none" stroke="var(--color-moss)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                                </div>
                            @endif
                        </td>
                        <td><span class="r-mono" style="font-size:0.8125rem; color:var(--color-ink-soft);">{{ $producto->codigo }}</span></td>
                        <td style="font-weight:500;">{{ $producto->nombre }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $producto->categoria->nombre ?? '—' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $producto->marca ?? '—' }}</td>
                        <td style="text-align:right;">${{ number_format($producto->precio_compra, 2, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:500;">${{ number_format($producto->precio_venta, 2, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($producto->estaEnStockCritico())
                                <span class="r-tag r-tag-danger">{{ $producto->stock }}</span>
                            @else
                                <span class="r-tag r-tag-success">{{ $producto->stock }}</span>
                            @endif
                            <span class="r-mono" style="font-size:0.6875rem; color:var(--color-ink-soft); margin-left:4px;">mín: {{ $producto->stock_minimo }}</span>
                        </td>
                        <td style="text-align:center;">
                            @if($producto->estado === 'activo')
                                <span class="r-tag r-tag-success">Activo</span>
                            @else
                                <span class="r-tag">Inactivo</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                @if (auth()->user()->role?->tienePermiso('productos.editar'))
                                    <a href="{{ route('productos.edit', $producto) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar</a>
                                @endif
                                @if (auth()->user()->role?->tienePermiso('productos.crear'))
                                    <form method="POST" action="{{ route('productos.duplicar', $producto) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:var(--color-ink-soft);" onclick="return confirm('¿Duplicar este producto?')">Duplicar</button>
                                    </form>
                                @endif
                                @if (auth()->user()->role?->tienePermiso('productos.eliminar'))
                                    <form method="POST" action="{{ route('productos.destroy', $producto) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;" onclick="return confirm('¿Eliminar este producto?')">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No se encontraron productos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($productos->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }} de {{ $productos->total() }} productos</span>
        {{ $productos->links() }}
    </div>
@endif

@endsection
