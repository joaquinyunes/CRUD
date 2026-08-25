<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'codigo'        => fake()->unique()->numerify('PROD-####'),
            'codigo_barra'  => fake()->unique()->ean13(),
            'nombre'        => fake()->words(3, true),
            'descripcion'   => fake()->sentence(),
            'categoria_id'  => Categoria::factory(),
            'marca'         => fake()->company(),
            'precio_compra' => 100,
            'precio_venta'  => 150,
            'stock'         => 50,
            'stock_minimo'  => 5,
            'estado'        => 'activo',
        ];
    }
}
