<button {{ $attributes->merge(['type' => 'submit', 'class' => 'r-btn r-btn-primary']) }}>
    {{ $slot }}
</button>
