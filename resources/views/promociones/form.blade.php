@extends('layouts.app')

@section('page_title', $promocion->exists ? 'Editar promoción' : 'Nueva promoción')

@section('content')
<div style="max-width:40rem;margin:0 auto;" x-data="{
        tipo: '{{ old('tipo', $promocion->tipo) }}',
        alcance: '{{ old('alcance', $promocion->alcance) }}'
    }">

    <div class="r-flex r-items-center r-justify-between r-mb-6">
        <h1 class="r-display-m">{{ $promocion->exists ? 'Editar promoción' : 'Nueva promoción' }}</h1>
        <a href="{{ route('promociones.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver</a>
    </div>

    @if($errors->any())
        <div class="r-flash-error r-mb-4"><ul style="list-style:disc;padding-left:1.2em;margin:0;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul></div>
    @endif

    <form method="POST" action="{{ $promocion->exists ? route('promociones.update', $promocion) : route('promociones.store') }}" class="r-card-flat r-stack">
        @csrf
        @if($promocion->exists) @method('PUT') @endif

        <div class="r-field" style="margin:0;">
            <label class="r-label">Nombre <span class="r-req">*</span></label>
            <input type="text" name="nombre" value="{{ old('nombre', $promocion->nombre) }}" required class="r-input">
        </div>

        <div class="r-flex r-gap-3">
            <div class="r-field" style="margin:0;flex:1;">
                <label class="r-label">Tipo</label>
                <select name="tipo" class="r-select" x-model="tipo">
                    <option value="porcentaje">% de descuento</option>
                    <option value="monto">Monto fijo por unidad</option>
                    <option value="precio_fijo">Precio fijo por unidad</option>
                    <option value="nxm">N x M (llevás N, pagás M)</option>
                </select>
            </div>
            <div class="r-field" style="margin:0;flex:1;" :style="tipo === 'nxm' ? 'display:none' : ''">
                <label class="r-label">Valor</label>
                <input type="number" step="0.01" min="0" name="valor" value="{{ old('valor', $promocion->valor) }}" class="r-input">
            </div>
        </div>

        <div class="r-flex r-gap-3" :style="tipo === 'nxm' ? '' : 'display:none'">
            <div class="r-field" style="margin:0;flex:1;">
                <label class="r-label">Llevás (N)</label>
                <input type="number" min="2" name="n" value="{{ old('n', $promocion->n) }}" class="r-input">
            </div>
            <div class="r-field" style="margin:0;flex:1;">
                <label class="r-label">Pagás (M)</label>
                <input type="number" min="1" name="m" value="{{ old('m', $promocion->m) }}" class="r-input">
            </div>
        </div>

        <div class="r-field" style="margin:0;">
            <label class="r-label">Alcance</label>
            <select name="alcance" class="r-select" x-model="alcance">
                <option value="producto">Un producto</option>
                <option value="categoria">Una categoría</option>
                <option value="todos">Todo el catálogo</option>
            </select>
        </div>

        <div class="r-field" style="margin:0;" :style="alcance === 'producto' ? '' : 'display:none'">
            <label class="r-label">Producto</label>
            <select name="producto_id" class="r-select">
                <option value="">—</option>
                @foreach($productos as $prod)
                    <option value="{{ $prod->id }}" @selected(old('producto_id', $promocion->producto_id) == $prod->id)>{{ $prod->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="r-field" style="margin:0;" :style="alcance === 'categoria' ? '' : 'display:none'">
            <label class="r-label">Categoría</label>
            <select name="categoria_id" class="r-select">
                <option value="">—</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" @selected(old('categoria_id', $promocion->categoria_id) == $cat->id)>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="r-flex r-gap-3">
            <div class="r-field" style="margin:0;flex:1;">
                <label class="r-label">Desde</label>
                <input type="date" name="desde" value="{{ old('desde', $promocion->desde?->format('Y-m-d')) }}" class="r-input">
            </div>
            <div class="r-field" style="margin:0;flex:1;">
                <label class="r-label">Hasta</label>
                <input type="date" name="hasta" value="{{ old('hasta', $promocion->hasta?->format('Y-m-d')) }}" class="r-input">
            </div>
        </div>

        <div class="r-flex r-gap-3">
            <div class="r-field" style="margin:0;flex:1;">
                <label class="r-label">Hora desde (happy hour)</label>
                <input type="time" name="hora_desde" value="{{ old('hora_desde', $promocion->hora_desde ? substr($promocion->hora_desde,0,5) : '') }}" class="r-input">
            </div>
            <div class="r-field" style="margin:0;flex:1;">
                <label class="r-label">Hora hasta</label>
                <input type="time" name="hora_hasta" value="{{ old('hora_hasta', $promocion->hora_hasta ? substr($promocion->hora_hasta,0,5) : '') }}" class="r-input">
            </div>
        </div>

        <div class="r-field" style="margin:0;">
            <label class="r-label">Días (vacío = todos)</label>
            @php $diasSel = old('dias', $promocion->dias ?? []); @endphp
            <div class="r-flex r-gap-3" style="flex-wrap:wrap;">
                @foreach(['1'=>'Lun','2'=>'Mar','3'=>'Mié','4'=>'Jue','5'=>'Vie','6'=>'Sáb','7'=>'Dom'] as $n => $lbl)
                    <label class="r-cluster" style="gap:6px;cursor:pointer;">
                        <input type="checkbox" name="dias[]" value="{{ $n }}" @checked(in_array((int)$n, array_map('intval',$diasSel), true))>
                        <span class="r-body" style="font-size:0.85rem;">{{ $lbl }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="r-flex r-gap-3">
            <div class="r-field" style="margin:0;flex:1;">
                <label class="r-label">Prioridad</label>
                <input type="number" name="prioridad" value="{{ old('prioridad', $promocion->prioridad ?? 0) }}" class="r-input">
            </div>
            <label class="r-cluster" style="gap:8px;cursor:pointer;align-self:flex-end;padding-bottom:0.5rem;">
                <input type="checkbox" name="activa" value="1" @checked(old('activa', $promocion->activa ?? true))>
                <span class="r-body" style="font-size:0.9rem;">Activa</span>
            </label>
        </div>

        <div class="r-cluster r-justify-end" style="border-top:1px solid var(--color-line);padding-top:var(--space-4);">
            <a href="{{ route('promociones.index') }}" class="r-btn r-btn-ghost">Cancelar</a>
            <button class="r-btn r-btn-primary">{{ $promocion->exists ? 'Guardar' : 'Crear' }}</button>
        </div>
    </form>
</div>
@endsection
