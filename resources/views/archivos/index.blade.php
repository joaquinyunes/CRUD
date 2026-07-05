@extends('layouts.app')

@section('page_title', 'Archivos')

@section('content')

<h2 class="r-display-l r-mb-8" data-reveal="fade-up">Archivos</h2>

<div class="r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <x-file-upload />
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.15">
    <form method="GET" action="{{ route('archivos.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre del archivo…" class="r-input">
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if(request()->has('buscar'))
            <a href="{{ route('archivos.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th style="width:48px;"></th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th style="text-align:right;">Tamaño</th>
                    <th>Relación</th>
                    <th>Subido por</th>
                    <th>Fecha</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($archivos as $archivo)
                    <tr>
                        <td>
                            @if($archivo->esImagen)
                                <img src="{{ asset('storage/' . $archivo->ruta) }}" alt="{{ $archivo->nombre }}" style="width:40px;height:40px;object-fit:cover;border-radius:8px;">
                            @else
                                <div style="width:40px;height:40px;background:rgba(220,38,38,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                    <span style="font-size:0.625rem; font-weight:700; color:#dc2626;">PDF</span>
                                </div>
                            @endif
                        </td>
                        <td style="font-weight:500; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $archivo->nombre }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $archivo->tipo }}</td>
                        <td style="text-align:right; color:var(--color-ink-soft);">{{ $archivo->tamanoFormateado }}</td>
                        <td style="color:var(--color-ink-soft);">
                            @if($archivo->relacionado_tipo)
                                {{ ucfirst($archivo->relacionado_tipo) }} #{{ $archivo->relacionado_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="color:var(--color-ink-soft);">{{ $archivo->user->name ?? '—' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $archivo->created_at->format('d/m/Y H:i') }}</td>
                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                <a href="{{ route('archivos.download', $archivo) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Descargar</a>
                                <form method="POST" action="{{ route('archivos.destroy', $archivo) }}" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;" onclick="return confirm('¿Eliminar este archivo?')">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No se encontraron archivos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($archivos->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $archivos->firstItem() }}–{{ $archivos->lastItem() }} de {{ $archivos->total() }} archivos</span>
        {{ $archivos->links() }}
    </div>
@endif

@endsection
