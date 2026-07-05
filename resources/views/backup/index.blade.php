@extends('layouts.app')

@section('title', 'Backup y Restore — ' . config('app.name'))
@section('page_title', 'Backup y Restore')

@section('content')

@if(session('success'))
    <div class="r-flash-success r-mb-6">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="r-flash-error r-mb-6">{{ session('error') }}</div>
@endif

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:var(--space-6);" class="r-mb-8">
    <div class="r-card" data-reveal="fade-up" data-reveal-delay="0">
        <h3 class="r-display-m" style="font-size:1.125rem; margin-bottom:var(--space-2);">Crear Backup</h3>
        <p class="r-body" style="margin-bottom:var(--space-4);">Exporta la base de datos completa a un archivo .sql</p>
        <form method="POST" action="{{ route('backup.crear') }}">
            @csrf
            <button type="submit" class="r-btn r-btn-primary">Crear Backup Ahora</button>
        </form>
    </div>
    <div class="r-card" data-reveal="fade-up" data-reveal-delay="0.1">
        <h3 class="r-display-m" style="font-size:1.125rem; margin-bottom:var(--space-2);">Restaurar Backup</h3>
        <p class="r-body" style="margin-bottom:var(--space-4);">Importa un archivo .sql para restaurar la base de datos</p>
        <form method="POST" action="{{ route('backup.restaurar') }}" enctype="multipart/form-data">
            @csrf
            <div class="r-flex r-gap-3" style="align-items:flex-end;">
                <div style="flex:1;">
                    <input type="file" name="backup_file" accept=".sql,.txt" required class="r-input" style="padding:8px;">
                </div>
                <button type="submit" class="r-btn r-btn-accent r-btn-sm" style="white-space:nowrap;">Restaurar</button>
            </div>
        </form>
    </div>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <h3 class="r-display-m" style="font-size:1.125rem; margin-bottom:var(--space-4);">Backups Disponibles</h3>
    @if(count($backups) > 0)
        <div style="overflow-x: auto;">
            <table class="r-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="text-align:right;">Tamaño</th>
                        <th>Fecha</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $backup)
                        <tr>
                            <td><span class="r-mono" style="font-size:0.8125rem;">{{ $backup['nombre'] }}</span></td>
                            <td style="text-align:right; color:var(--color-ink-soft);">{{ $backup['tamano'] }} KB</td>
                            <td style="color:var(--color-ink-soft);">{{ \Carbon\Carbon::fromTimestamp($backup['fecha'])->format('d/m/Y H:i:s') }}</td>
                            <td style="text-align:right;">
                                <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                    <a href="{{ route('backup.descargar', $backup['nombre']) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Descargar</a>
                                    <form method="POST" action="{{ route('backup.eliminar', $backup['nombre']) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar este backup?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No hay backups creados aún.</p>
    @endif
</div>

@endsection
