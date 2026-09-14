@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'r-error']) }} style="list-style:none;padding:0;margin-top:var(--space-1);">
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
