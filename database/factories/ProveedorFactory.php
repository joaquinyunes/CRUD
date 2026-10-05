<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proveedor>
 */
class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        return [
            'nombre'    => fake()->company(),
            'cuit'      => fake()->numerify('30-########-#'),
            'telefono'  => fake()->phoneNumber(),
            'email'     => fake()->unique()->companyEmail(),
            'direccion' => fake()->streetAddress(),
            'estado'    => 'activo',
        ];
    }

    public function archivado(): static
    {
        return $this->state(fn () => ['estado' => 'archivado']);
    }
}
