<button {{ $attributes->merge(['type' => 'submit', 'class' => 'r-btn r-btn-danger']) }}>
    {{ $slot }}
</button>
