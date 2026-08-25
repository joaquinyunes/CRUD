<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombre'    => fake()->firstName(),
            'apellido'  => fake()->lastName(),
            'documento' => fake()->unique()->numerify('########'),
            'email'     => fake()->unique()->safeEmail(),
            'telefono'  => fake()->phoneNumber(),
            'direccion' => fake()->address(),
            'estado'    => 'activo',
        ];
    }
}
