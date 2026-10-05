@extends('layouts.app')

@section('title', 'Inicio — ' . config('app.name'))
@section('page_title', 'Inicio')

@section('content')
<div class="r-card-flat" style="max-width:520px;">
    <h3 class="r-label r-mb-4">Hola, {{ auth()->user()->name }}</h3>
    <p style="color:var(--color-ink-soft);">
        Tu rol no incluye el panel de indicadores. Usá el menú lateral para entrar a los módulos que tenés habilitados.
    </p>
</div>
@endsection
