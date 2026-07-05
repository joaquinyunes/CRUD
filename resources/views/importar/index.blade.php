@extends('layouts.app')

@section('title', 'Importar CSV — ' . config('app.name'))
@section('page_title', 'Importar Datos (CSV)')

@section('content')

@if(session('success'))
    <div class="r-flash-success r-mb-6">{{ session('success') }}</div>
@endif

@if(session('errores') && count(session('errores')) > 0)
    <div class="r-flash-error r-mb-6">
        <div>
            <p style="font-weight:600; margin-bottom:4px;">Errores encontrados:</p>
            <ul style="margin:0; padding-left:16px;">
                @foreach(session('errores') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="r-card-flat" style="max-width:640px;" data-reveal="fade-up" data-reveal-delay="0.1">
    <h3 class="r-display-m" style="font-size:1.125rem; margin-bottom:var(--space-4);">Subir archivo CSV</h3>
    <form method="POST" action="{{ route('importar.importar') }}" enctype="multipart/form-data">
        @csrf
        <div class="r-mb-4">
            <label class="r-label">Tipo de datos a importar</label>
            <select name="tipo" class="r-select" style="width:100%;">
                <option value="productos">Productos (columnas: codigo, nombre, descripcion, marca, precio_compra, precio_venta, stock, stock_minimo, categoria)</option>
                <option value="clientes">Clientes (columnas: nombre, apellido, documento, email, telefono, direccion)</option>
                <option value="proveedores">Proveedores (columnas: nombre, cuit, telefono, email, direccion)</option>
            </select>
        </div>
        <div class="r-mb-4">
            <label class="r-label">Archivo CSV</label>
            <input type="file" name="archivo" accept=".csv,.txt" required class="r-input" style="padding:8px;">
            <p class="r-mono" style="font-size:0.6875rem; color:var(--color-ink-soft); margin-top:4px;">Máximo 5 MB. Formato: CSV con encabezados en la primera fila.</p>
        </div>
        @error('archivo')
            <p style="font-size:0.75rem; color:#dc2626; margin-bottom:12px;">{{ $message }}</p>
        @enderror
        <button type="submit" class="r-btn r-btn-accent">Importar</button>
    </form>
</div>

@endsection
