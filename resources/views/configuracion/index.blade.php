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
        <h3 class="r-caption r-mb-4">Tipo de Negocio</h3>
        <div class="r-card-flat">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:var(--space-4); align-items:end;">
                <div>
                    <label class="r-label">Tipo de Negocio</label>
                    <select name="negocio_tipo" class="r-select" style="width:100%;">
                        <option value="tienda" {{ ($configuracion['negocio']['negocio_tipo'] ?? '') === 'tienda' ? 'selected' : '' }}>Tienda</option>
                        <option value="kiosco" {{ ($configuracion['negocio']['negocio_tipo'] ?? '') === 'kiosco' ? 'selected' : '' }}>Kiosco</option>
                        <option value="restaurante" {{ ($configuracion['negocio']['negocio_tipo'] ?? '') === 'restaurante' ? 'selected' : '' }}>Restaurante</option>
                        <option value="servicios" {{ ($configuracion['negocio']['negocio_tipo'] ?? '') === 'servicios' ? 'selected' : '' }}>Servicios</option>
                        <option value="empresa" {{ ($configuracion['negocio']['negocio_tipo'] ?? '') === 'empresa' ? 'selected' : '' }}>Empresa</option>
                        <option value="otro" {{ ($configuracion['negocio']['negocio_tipo'] ?? '') === 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.05">
        <h3 class="r-caption r-mb-4">Unidades de Medida</h3>
        <div class="r-card-flat">
            <div class="r-flex r-items-center r-gap-4 r-justify-between" style="flex-wrap:wrap;">
                <span class="r-caption" style="color:var(--color-ink-soft); text-transform:none; letter-spacing:0;">Administra las unidades de medida utilizadas en el sistema</span>
                <a href="{{ route('unidades-medida.index') }}" class="r-btn r-btn-accent r-btn-sm">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    Gestionar Unidades
                </a>
            </div>
        </div>
    </div>

    <div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.1">
        <h3 class="r-caption r-mb-4">Métodos de Pago</h3>
        <div class="r-card-flat">
            <div class="r-flex r-items-center r-gap-4 r-justify-between" style="flex-wrap:wrap;">
                <span class="r-caption" style="color:var(--color-ink-soft); text-transform:none; letter-spacing:0;">Configura los métodos de pago aceptados en el negocio</span>
                <a href="{{ route('metodos-pago.index') }}" class="r-btn r-btn-accent r-btn-sm">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    Gestionar Métodos
                </a>
            </div>
        </div>
    </div>

    <div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.15">
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

    <div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.2">
        <h3 class="r-caption r-mb-4">Sistema</h3>
        <div class="r-card-flat">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-4); align-items:end;">
                <div>
                    <label class="r-label">Moneda</label>
                    <select name="sistema_moneda" class="r-select" style="width:100%;">
                        <option value="ARS" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'ARS' ? 'selected' : '' }}>Peso Argentino (ARS)</option>
                        <option value="USD" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'USD' ? 'selected' : '' }}>Dólar (USD)</option>
                        <option value="EUR" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                        <option value="MXN" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'MXN' ? 'selected' : '' }}>Peso Mexicano (MXN)</option>
                        <option value="COP" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'COP' ? 'selected' : '' }}>Peso Colombiano (COP)</option>
                        <option value="PEN" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'PEN' ? 'selected' : '' }}>Sol (PEN)</option>
                        <option value="BRL" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'BRL' ? 'selected' : '' }}>Real (BRL)</option>
                        <option value="CLP" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'CLP' ? 'selected' : '' }}>Peso Chileno (CLP)</option>
                        <option value="UYU" {{ ($configuracion['sistema']['sistema_moneda'] ?? '') === 'UYU' ? 'selected' : '' }}>Peso Uruguayo (UYU)</option>
                    </select>
                </div>
                <div><label class="r-label">Símbolo Moneda</label><input type="text" name="sistema_simbolo_moneda" value="{{ $configuracion['sistema']['sistema_simbolo_moneda'] ?? '$' }}" class="r-input"></div>
                <div><label class="r-label">IVA (%)</label><input type="number" name="sistema_iva" value="{{ $configuracion['sistema']['sistema_iva'] ?? '21' }}" class="r-input" min="0" max="100" step="0.1"></div>
                <div>
                    <label class="r-label">Impuesto habilitado</label>
                    <label class="r-flex r-items-center r-gap-2" style="margin-top:6px; cursor:pointer;">
                        <input type="checkbox" name="sistema_impuesto_habilitado" value="1" {{ ($configuracion['sistema']['sistema_impuesto_habilitado'] ?? '1') == '1' ? 'checked' : '' }}>
                        <span class="r-caption" style="text-transform:none; letter-spacing:0;">Aplicar impuestos en ventas</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.25">
        <h3 class="r-caption r-mb-4">Ventas</h3>
        <div class="r-card-flat">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:var(--space-4); align-items:end;">
                <div><label class="r-label">Prefijo Número (ej: VTA-)</label><input type="text" name="ventas_prefijo_numero" value="{{ $configuracion['ventas']['ventas_prefijo_numero'] ?? 'VTA' }}" class="r-input"></div>
                <div><label class="r-label">Cantidad Dígitos</label><input type="number" name="ventas_cantidad_digitos" value="{{ $configuracion['ventas']['ventas_cantidad_digitos'] ?? '5' }}" class="r-input" min="3" max="10"></div>
                <div>
                    <label class="r-label">Permite descuento</label>
                    <label class="r-flex r-items-center r-gap-2" style="margin-top:6px; cursor:pointer;">
                        <input type="checkbox" name="ventas_permite_descuento" value="1" {{ ($configuracion['ventas']['ventas_permite_descuento'] ?? '1') == '1' ? 'checked' : '' }}>
                        <span class="r-caption" style="text-transform:none; letter-spacing:0;">Habilitar descuentos en ventas</span>
                    </label>
                </div>
                <div><label class="r-label">Límite descuento sin autorización (%)</label><input type="number" name="ventas_limite_descuento" value="{{ $configuracion['ventas']['ventas_limite_descuento'] ?? '10' }}" class="r-input" min="0" max="100" step="1"></div>
                <div>
                    <label class="r-label">Número de comprobante</label>
                    <select name="ventas_numero_comprobante" class="r-select" style="width:100%;">
                        <option value="manual" {{ ($configuracion['ventas']['ventas_numero_comprobante'] ?? '') === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="automatico" {{ ($configuracion['ventas']['ventas_numero_comprobante'] ?? '') === 'automatico' ? 'selected' : '' }}>Automático</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="r-text-right" data-reveal="fade-up" data-reveal-delay="0.3">
        <button type="submit" class="r-btn r-btn-accent">Guardar Configuración</button>
    </div>
</form>

@endsection
