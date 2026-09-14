@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'r-flash-success']) }} style="margin-bottom:var(--space-4);">
        {{ $status }}
    </div>
@endif
