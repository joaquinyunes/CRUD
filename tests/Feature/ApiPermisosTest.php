<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * La API es la misma aplicación por otra puerta: un token no puede hacer nada
 * que su dueño no pueda hacer desde el navegador.
 */
class ApiPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->rolAdministrador();
        Role::create(['nombre' => Role::SUPERVISOR]);
        Role::create(['nombre' => Role::EMPLEADO]);
        Role::create(['nombre' => Role::CLIENTE]);
        $this->seed(PermissionSeeder::class);
    }

    /** Un rol que solo puede consultar, como el "Solo Lectura" de la demo. */
    private function soloLectura(): User
    {
        $rol = Role::create(['nombre' => Role::SOLO_LECTURA]);
        $rol->permissions()->sync(
            Permission::where('clave', 'like', '%.ver')->pluck('id')
        );

        return User::factory()->create(['role_id' => $rol->id]);
    }

    private function producto(): Producto
    {
        return Producto::factory()->create(['nombre' => 'Martillo', 'stock' => 10]);
    }

    public function test_sin_token_la_api_responde_401(): void
    {
        $this->seedRoles();

        $this->getJson('/api/productos')->assertStatus(401);
    }

    public function test_solo_lectura_puede_listar(): void
    {
        $this->seedRoles();
        $this->producto();

        Sanctum::actingAs($this->soloLectura());

        $this->getJson('/api/productos')->assertOk();
    }

    public function test_solo_lectura_no_puede_crear_productos(): void
    {
        $this->seedRoles();
        $categoria = Categoria::create(['nombre' => 'General', 'estado' => true]);

        Sanctum::actingAs($this->soloLectura());

        $this->postJson('/api/productos', [
            'codigo'        => 'COL-1',
            'nombre'        => 'Colado',
            'categoria_id'  => $categoria->id,
            'precio_compra' => 1,
            'precio_venta'  => 2,
            'stock'         => 1,
            'stock_minimo'  => 0,
            'estado'        => 'activo',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('productos', ['nombre' => 'Colado']);
    }

    public function test_solo_lectura_no_puede_borrar_productos(): void
    {
        $this->seedRoles();
        $producto = $this->producto();

        Sanctum::actingAs($this->soloLectura());

        $this->deleteJson("/api/productos/{$producto->id}")->assertStatus(403);
    }

    public function test_solo_lectura_no_puede_crear_ventas(): void
    {
        $this->seedRoles();
        $producto = $this->producto();
        $cliente = Cliente::factory()->create();

        Sanctum::actingAs($this->soloLectura());

        $this->postJson('/api/ventas', [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 180]],
        ])->assertStatus(403);

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_el_administrador_si_puede_escribir_por_api(): void
    {
        $this->seedRoles();
        $categoria = Categoria::create(['nombre' => 'General', 'estado' => true]);

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', Role::ADMINISTRADOR)->value('id'),
        ]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/productos', [
            'codigo'        => 'PIN-1',
            'nombre'        => 'Pinza',
            'categoria_id'  => $categoria->id,
            'precio_compra' => 100,
            'precio_venta'  => 190,
            'stock'         => 5,
            'stock_minimo'  => 1,
            'estado'        => 'activo',
        ])->assertCreated();

        $this->assertDatabaseHas('productos', ['nombre' => 'Pinza']);
    }

    public function test_el_login_de_api_esta_limitado_por_intentos(): void
    {
        $this->seedRoles();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'nadie@demo.com', 'password' => 'mal']);
        }

        $this->postJson('/api/login', ['email' => 'nadie@demo.com', 'password' => 'mal'])
            ->assertStatus(429);
    }
}
