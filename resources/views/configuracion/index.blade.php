@extends('layouts.app')

@section('title', 'Configuración — ' . config('app.name'))
@section('page_title', 'Configuración del Sistema')

@section('content')

@if(session('success'))
    <div class="r-flash-success r-mb-6">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('configuracion.update') }}">
    @csrf

    <div class="r-mb-8" data-reveal="fade-up">
        <h3 class="r-caption r-mb-4">Datos de la Empresa</h3>
        <div class="r-card-flat">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:var(--space-4);">
                <div><label class="r-label">Nombre de la Empresa</label><input type="text" name="empresa_nombre" value="{{ $configuracion['empresa']['empresa_nombre'] ?? '' }}" class="r-input"></div>
                <div><label class="r-label">RUC / NIT</label><input type="text" name="empresa_ruc" value="{{ $configuracion['empresa']['empresa_ruc'] ?? '' }}" class="r-input"></div>
                <div><label class="r-label">Dirección</label><input type="text" name="empresa_direccion" value="{{ $configuracion['empresa']['empresa_direccion'] ?? '' }}" class="r-input"></div>
                <div><label class="r-label">Teléfono</label><input type="text" name="empresa_telefono" value="{{ $configuracion['empresa']['empresa_telefono'] ?? '' }}" class="r-input"></div>
                <div><label class="r-label">Email</label><input type="email" name="empresa_email" value="{{ $configuracion['empresa']['empresa_email'] ?? '' }}" class="r-input"></div>
                <div><label class="r-label">Logo (URL)</label><input type="text" name="empresa_logo" value="{{ $configuracion['empresa']['empresa_logo'] ?? '' }}" class="r-input" placeholder="https://..."></div>
            </div>
        </div>
    </div>

    <div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.1">
        <h3 class="r-caption r-mb-4">Sistema</h3>
        <div class="r-card-flat">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-4);">
                <div>
                    <label class="r-label">Moneda</label>
                    <select name="sistema_moneda" class="r-select" style="width:100%;">
                        <option value="ARS" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'ARS' ? 'selected' : '' }}>Peso Argentino (ARS)</option>
                        <option value="USD" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'USD' ? 'selected' : '' }}>Dólar (USD)</option>
                        <option value="EUR" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                        <option value="MXN" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'MXN' ? 'selected' : '' }}>Peso Mexicano (MXN)</option>
                        <option value="COP" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'COP' ? 'selected' : '' }}>Peso Colombiano (COP)</option>
                        <option value="PEN" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'PEN' ? 'selected' : '' }}>Sol (PEN)</option>
                    </select>
                </div>
                <div><label class="r-label">Símbolo Moneda</label><input type="text" name="sistema_simbolo_moneda" value="{{ $configuracion['sistema']['sistema_simbolo_moneda'] ?? '$' }}" class="r-input"></div>
                <div><label class="r-label">IVA (%)</label><input type="number" name="sistema_iva" value="{{ $configuracion['sistema']['sistema_iva'] ?? '21' }}" class="r-input" min="0" max="100" step="0.1"></div>
            </div>
        </div>
    </div>

    <div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.2">
        <h3 class="r-caption r-mb-4">Ventas</h3>
        <div class="r-card-flat">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:var(--space-4);">
                <div><label class="r-label">Prefijo Número (ej: VTA-)</label><input type="text" name="ventas_prefijo_numero" value="{{ $configuracion['ventas']['ventas_prefijo_numero'] ?? 'VTA' }}" class="r-input"></div>
                <div><label class="r-label">Cantidad Dígitos</label><input type="number" name="ventas_cantidad_digitos" value="{{ $configuracion['ventas']['ventas_cantidad_digitos'] ?? '5' }}" class="r-input" min="3" max="10"></div>
            </div>
        </div>
    </div>

    <div class="r-text-right" data-reveal="fade-up" data-reveal-delay="0.3">
        <button type="submit" class="r-btn r-btn-accent">Guardar Configuración</button>
    </div>
</form>

@endsection
