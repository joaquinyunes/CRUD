<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Support\NumeradorDocumentos;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * La numeración de comprobantes es el requisito fiscal del sistema: consecutiva,
 * sin huecos y sin repetidos, pase lo que pase.
 */
class NumeracionComprobantesTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('nombre', Role::ADMINISTRADOR)->value('id'),
        ]);
    }

    public function test_los_numeros_salen_consecutivos_y_sin_huecos(): void
    {
        $numeros = [];

        for ($i = 0; $i < 3; $i++) {
            $numeros[] = DB::transaction(fn () => NumeradorDocumentos::proximo('ventas', 'VTA'));
        }

        $this->assertSame(['VTA-00001', 'VTA-00002', 'VTA-00003'], $numeros);
    }

    public function test_cada_serie_lleva_su_propia_cuenta(): void
    {
        $venta = DB::transaction(fn () => NumeradorDocumentos::proximo('ventas', 'VTA'));
        $compra = DB::transaction(fn () => NumeradorDocumentos::proximo('compras', 'COM'));

        $this->assertSame('VTA-00001', $venta);
        $this->assertSame('COM-00001', $compra);
    }

    public function test_una_transaccion_revertida_no_deja_hueco(): void
    {
        $primero = DB::transaction(fn () => NumeradorDocumentos::proximo('ventas', 'VTA'));

        try {
            DB::transaction(function () {
                NumeradorDocumentos::proximo('ventas', 'VTA');
                throw new RuntimeException('algo falló después de tomar el número');
            });
        } catch (RuntimeException) {
            // esperado
        }

        $siguiente = DB::transaction(fn () => NumeradorDocumentos::proximo('ventas', 'VTA'));

        $this->assertSame('VTA-00001', $primero);
        $this->assertSame('VTA-00002', $siguiente, 'El número reservado por una transacción revertida se reutiliza.');
    }

    public function test_arranca_desde_los_comprobantes_que_ya_existian(): void
    {
        // Una base que viene de la versión anterior: hay ventas, no hay contador.
        $this->seedRoles();
        $usuario = $this->admin();
        $cliente = Cliente::factory()->create();

        Venta::create([
            'numero'      => 'VTA-00042',
            'cliente_id'  => $cliente->id,
            'fecha'       => now()->toDateString(),
            'total'       => 0,
            'total_final' => 0,
            'estado'      => 'pendiente',
            'user_id'     => $usuario->id,
        ]);

        $this->assertSame(
            'VTA-00043',
            DB::transaction(fn () => NumeradorDocumentos::proximo('ventas', 'VTA'))
        );
    }

    public function test_respeta_el_prefijo_y_la_cantidad_de_digitos_configurados(): void
    {
        $this->assertSame(
            'FAC-0000001',
            DB::transaction(fn () => NumeradorDocumentos::proximo('ventas', 'FAC', 7))
        );
    }

    public function test_dos_ventas_seguidas_no_repiten_numero(): void
    {
        $this->seedRoles();
        $usuario = $this->admin();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 100]);

        foreach ([1, 2] as $_) {
            $this->actingAs($usuario)->post(route('ventas.store'), [
                'cliente_id' => $cliente->id,
                'fecha'      => now()->toDateString(),
                'estado'     => 'completada',
                'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            ]);
        }

        $numeros = Venta::orderBy('id')->pluck('numero')->all();

        $this->assertCount(2, $numeros);
        $this->assertSame($numeros, array_unique($numeros));
        $this->assertSame(['VTA-00001', 'VTA-00002'], $numeros);
    }

    /** Anular conserva el comprobante: su número queda consumido para siempre. */
    public function test_anular_una_venta_no_libera_su_numero(): void
    {
        $this->seedRoles();
        $usuario = $this->admin();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 100]);

        $venta = [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
        ];

        $this->actingAs($usuario)->post(route('ventas.store'), $venta);
        $primera = Venta::latest('id')->first();

        $this->actingAs($usuario)->delete(route('ventas.destroy', $primera));
        $this->actingAs($usuario)->post(route('ventas.store'), $venta);

        $this->assertSame('VTA-00002', Venta::latest('id')->first()->numero);
    }
}
