@extends('layouts.app')

@section('page_title', 'Proveedores')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Proveedores</h2>
    @if (auth()->user()->role?->tienePermiso('proveedores.crear'))
        <a href="{{ route('proveedores.create') }}" class="r-btn r-btn-primary r-btn-sm">Nuevo proveedor</a>
    @endif
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('proveedores.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, CUIT o email…" class="r-input">
        </div>
        @if(request()->boolean('archivados'))
            <input type="hidden" name="archivados" value="1">
        @endif
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if(request()->boolean('archivados'))
            <a href="{{ route('proveedores.index') }}" class="r-btn r-btn-ghost r-btn-sm">Ver activos</a>
        @else
            <a href="{{ route('proveedores.index', ['archivados' => 1]) }}" class="r-btn r-btn-ghost r-btn-sm">Ver archivados</a>
        @endif
        @if(request()->has('buscar'))
            <a href="{{ route('proveedores.index', request()->only('archivados')) }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <x-th campo="nombre">Nombre</x-th>
                    <x-th campo="cuit">CUIT</x-th>
                    <x-th campo="telefono">Teléfono</x-th>
                    <x-th campo="email">Correo</x-th>
                    <x-th campo="direccion">Dirección</x-th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proveedores as $proveedor)
                    <tr>
                        <td style="font-weight:500;">{{ $proveedor->nombre }}</td>
                        <td><span class="r-mono" style="font-size:0.8125rem; color:var(--color-ink-soft);">{{ $proveedor->cuit ?? '—' }}</span></td>
                        <td style="color:var(--color-ink-soft);">{{ $proveedor->telefono ?? '—' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $proveedor->email ?? '—' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $proveedor->direccion ?? '—' }}</td>
                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                @if (auth()->user()->role?->tienePermiso('proveedores.editar'))
                                    <a href="{{ route('proveedores.edit', $proveedor) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar</a>
                                @endif
                                @if ($proveedor->estado === 'archivado')
                                    @if (auth()->user()->role?->tienePermiso('proveedores.editar'))
                                        <form method="POST" action="{{ route('proveedores.restaurar', $proveedor) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Restaurar</button>
                                        </form>
                                    @endif
                                @elseif (auth()->user()->role?->tienePermiso('proveedores.eliminar'))
                                    {{-- Si tiene compras se archiva y se conservan los comprobantes. --}}
                                    <form method="POST" action="{{ route('proveedores.destroy', $proveedor) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;" onclick="return confirm('Si el proveedor tiene compras registradas se archiva en lugar de borrarse, para no perder el historial. ¿Continuar?')">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">
                            {{ request()->boolean('archivados') ? 'No hay proveedores archivados.' : 'No se encontraron proveedores.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($proveedores->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $proveedores->firstItem() }}–{{ $proveedores->lastItem() }} de {{ $proveedores->total() }} proveedores</span>
        {{ $proveedores->links() }}
    </div>
@endif

@endsection
