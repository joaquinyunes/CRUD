@props(['value'])

<label {{ $attributes->merge(['class' => 'r-label']) }}>
    {{ $value ?? $slot }}
</label>
