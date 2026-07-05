<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use App\Models\UnidadMedida;
use Illuminate\Database\Seeder;

class UniversalSeeder extends Seeder
{
    public function run(): void
    {
        // Unidades de Medida
        $unidades = [
            ['nombre' => 'Pieza',       'abreviacion' => 'pza', 'estado' => true],
            ['nombre' => 'Kilogramo',   'abreviacion' => 'kg',  'estado' => true],
            ['nombre' => 'Gramo',       'abreviacion' => 'g',   'estado' => true],
            ['nombre' => 'Litro',       'abreviacion' => 'L',   'estado' => true],
            ['nombre' => 'Mililitro',   'abreviacion' => 'mL',  'estado' => true],
            ['nombre' => 'Metro',       'abreviacion' => 'm',   'estado' => true],
            ['nombre' => 'Metro cuadrado', 'abreviacion' => 'm²', 'estado' => true],
            ['nombre' => 'Par',         'abreviacion' => 'par', 'estado' => true],
            ['nombre' => 'Docena',      'abreviacion' => 'doc', 'estado' => true],
            ['nombre' => 'Caja',        'abreviacion' => 'caja','estado' => true],
            ['nombre' => 'Paquete',     'abreviacion' => 'pqt', 'estado' => true],
            ['nombre' => 'Unidad',      'abreviacion' => 'un',  'estado' => true],
            ['nombre' => 'Hora',        'abreviacion' => 'hr',  'estado' => true],
            ['nombre' => 'Servicio',    'abreviacion' => 'srv', 'estado' => true],
            ['nombre' => 'Metro lineal', 'abreviacion' => 'ml', 'estado' => true],
        ];

        foreach ($unidades as $u) {
            UnidadMedida::updateOrCreate(
                ['abreviacion' => $u['abreviacion']],
                $u
            );
        }

        // Métodos de Pago
        $metodos = [
            ['nombre' => 'Efectivo',         'codigo' => 'efectivo',        'activo' => true, 'permite_vuelto' => true,  'orden' => 1],
            ['nombre' => 'Tarjeta de Débito', 'codigo' => 'debito',         'activo' => true, 'permite_vuelto' => false, 'orden' => 2],
            ['nombre' => 'Tarjeta de Crédito', 'codigo' => 'credito',       'activo' => true, 'permite_vuelto' => false, 'orden' => 3],
            ['nombre' => 'Transferencia',     'codigo' => 'transferencia',  'activo' => true, 'permite_vuelto' => false, 'orden' => 4],
            ['nombre' => 'QR / Digital',      'codigo' => 'qr',             'activo' => true, 'permite_vuelto' => false, 'orden' => 5],
            ['nombre' => 'Cuenta Corriente',  'codigo' => 'cuenta_corriente','activo' => true, 'permite_vuelto' => false, 'orden' => 6],
            ['nombre' => 'Cheque',            'codigo' => 'cheque',         'activo' => true, 'permite_vuelto' => false, 'orden' => 7],
            ['nombre' => 'Cripto',            'codigo' => 'cripto',         'activo' => true, 'permite_vuelto' => false, 'orden' => 8],
        ];

        foreach ($metodos as $m) {
            MetodoPago::updateOrCreate(
                ['codigo' => $m['codigo']],
                $m
            );
        }

        // Default settings for universal system
        $defaults = [
            'negocio_tipo' => 'tienda',
            'sistema_moneda' => 'ARS',
            'sistema_simbolo_moneda' => '$',
            'sistema_iva' => '21',
            'sistema_impuesto_habilitado' => '1',
            'ventas_prefijo_numero' => 'VTA',
            'ventas_cantidad_digitos' => '5',
            'ventas_permite_descuento' => '1',
            'ventas_limite_descuento' => '10',
            'ventas_numero_comprobante' => 'automatico',
        ];

        foreach ($defaults as $clave => $valor) {
            \App\Models\Setting::establecer($clave, $valor, 'general');
        }
    }
}
