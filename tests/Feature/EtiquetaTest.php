<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Support\Codebar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtiquetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_code39_svg_valido(): void
    {
        $svg = Codebar::code39Svg('PROD-0001');
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<rect', $svg);
    }

    public function test_hoja_de_etiquetas_se_genera(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
        $p = Producto::factory()->create(['nombre' => 'Yerba 1kg', 'precio_venta' => 1500, 'codigo_barra' => '7791234567890']);

        $resp = $this->actingAs($admin)->post(route('etiquetas.imprimir'), [
            'items' => [['id' => $p->id, 'copias' => 3]],
            'columnas' => 3,
        ]);

        $resp->assertOk()->assertSee('Yerba 1kg')->assertSee('7791234567890');
        $this->assertSame(3, substr_count($resp->getContent(), 'class="lbl"'));
    }

    public function test_pantalla_etiquetas_carga(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
        Producto::factory()->create();
        $this->actingAs($admin)->get(route('etiquetas.index'))->assertOk();
    }
}
