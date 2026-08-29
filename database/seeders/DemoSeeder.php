<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->limpiarTablas();

        $admin = User::where('email', 'admin@admin.com')->first()
            ?? User::first();

        $unidades = UnidadMedida::where('estado', true)->get();
        $uni = fn (string $abr) => $unidades->firstWhere('abreviacion', $abr)?->id
            ?? $unidades->first()->id;

        // ---------- Categorías ----------
        $cats = [
            'Bebidas'            => 'Gaseosas, aguas, jugos y energizantes',
            'Lácteos'            => 'Leches, yogures y quesos',
            'Panadería'          => 'Pan, facturas y masas',
            'Frutas y Verduras'  => 'Productos frescos de estación',
            'Limpieza'           => 'Detergentes y artículos del hogar',
            'Snacks'             => 'Galletitas, golosinas y snacks',
            'Carnicería'         => 'Carnes frescas y embutidos',
            'Tecnología'         => 'Accesorios y electrónica',
        ];

        $categorias = [];
        foreach ($cats as $nombre => $desc) {
            $categorias[$nombre] = Categoria::create([
                'nombre'      => $nombre,
                'descripcion' => $desc,
                'estado'      => true,
            ]);
        }

        // ---------- Productos ----------
        $productosData = [
            // [nombre, cat, marca, compra, venta, stock, min, uni, estado]
            ['Coca Cola 2L',            'Bebidas',           'Coca-Cola', 120, 180, 40, 15, 'L',   'activo'],
            ['Agua Mineral 1.5L',       'Bebidas',           'Villavicencio', 60, 100, 8, 20, 'L',  'activo'],
            ['Jugo Naranja 1L',         'Bebidas',           'Del Valle', 90, 140, 25, 10, 'L',  'activo'],
            ['Energizante Red 500ml',   'Bebidas',           'Speed', 70, 120, 12, 10, 'mL', 'activo'],
            ['Leche Entera 1L',         'Lácteos',           'La Serenísima', 80, 130, 30, 12, 'L', 'activo'],
            ['Yogur Natural 500g',      'Lácteos',           'Ser', 60, 95, 5, 15, 'g',  'activo'],
            ['Queso Crema 200g',        'Lácteos',           'Casanto', 150, 220, 18, 8, 'g',  'activo'],
            ['Pan Francés 500g',        'Panadería',         'Panadería Propia', 50, 90, 22, 10, 'kg', 'activo'],
            ['Medialunas (6u)',         'Panadería',         'Panadería Propia', 40, 80, 3, 10, 'doc', 'activo'],
            ['Facturas Surtidas',       'Panadería',         'Panadería Propia', 35, 70, 14, 8, 'doc', 'activo'],
            ['Manzana Roja x kg',       'Frutas y Verduras','Granja', 50, 90, 35, 15, 'kg', 'activo'],
            ['Banana x kg',             'Frutas y Verduras','Granja', 45, 85, 6, 10, 'kg', 'activo'],
            ['Tomate x kg',             'Frutas y Verduras','Granja', 40, 80, 20, 10, 'kg', 'activo'],
            ['Detergente 1L',           'Limpieza',          'Ala', 110, 170, 28, 10, 'L',  'activo'],
            ['Lavandina 1L',            'Limpieza',          'Ayudín', 50, 90, 4, 12, 'L',  'activo'],
            ['Papel Higiénico (12u)',   'Limpieza',          'Scott', 200, 320, 16, 6, 'caja', 'activo'],
            ['Galletitas Oreo 140g',    'Snacks',            'Oreo', 55, 95, 50, 20, 'g',  'activo'],
            ['Chocolate Barra 80g',     'Snacks',            'Cofler', 45, 80, 11, 15, 'g',  'activo'],
            ['Chicles (pack 10)',       'Snacks',            'Beldent', 30, 60, 9, 20, 'pqt', 'activo'],
            ['Carne Molida 500g',       'Carnicería',        'Frigorífico', 350, 520, 12, 5, 'kg', 'activo'],
            ['Pollo Entero x kg',       'Carnicería',        'Granja', 200, 320, 7, 8, 'kg', 'activo'],
            ['Salame 250g',             'Carnicería',        'Paladini', 180, 280, 18, 6, 'g', 'activo'],
            ['Auriculares Bluetooth',   'Tecnología',        'Genius', 800, 1400, 2, 5, 'un', 'activo'],
            ['Cable USB-C 1m',          'Tecnología',        'Genius', 150, 300, 25, 10, 'un', 'inactivo'],
        ];

        $productos = [];
        foreach ($productosData as [$nombre, $cat, $marca, $compra, $venta, $stock, $min, $abr, $estado]) {
            $codigo = 'PROD-' . str_pad((count($productos) + 1), 4, '0', STR_PAD_LEFT);
            $productos[] = Producto::create([
                'codigo'           => $codigo,
                'nombre'           => $nombre,
                'descripcion'      => "$nombre de la marca $marca.",
                'categoria_id'     => $categorias[$cat]->id,
                'marca'            => $marca,
                'precio_compra'    => $compra,
                'precio_venta'     => $venta,
                'stock'            => $stock,
                'stock_minimo'     => $min,
                'unidad_medida_id' => $uni($abr),
                'imagen'           => null,
                'estado'           => $estado,
            ]);
        }

        // ---------- Clientes ----------
        $clientesData = [
            ['Juan', 'Pérez', '30123456', 'juan.perez@mail.com', '11-1234-5678', 'Av. Rivadavia 123'],
            ['María', 'González', '30456789', 'maria.g@mail.com', '11-2345-6789', 'Calle Falsa 456'],
            ['Carlos', 'López', '30987654', 'carlos.l@mail.com', '11-3456-7890', 'Belgrano 789'],
            ['Ana', 'Martínez', '30112233', 'ana.m@mail.com', '11-4567-8901', 'Cabildo 321'],
            ['Lucía', 'Fernández', '30554433', 'lucia.f@mail.com', '11-5678-9012', 'Santa Fe 654'],
            ['Pedro', 'Sánchez', '30778899', 'pedro.s@mail.com', '11-6789-0123', 'Corrientes 987'],
            ['Sofía', 'Romero', '30223344', 'sofia.r@mail.com', '11-7890-1234', 'Córdoba 147'],
            ['Diego', 'Torres', '30889900', 'diego.t@mail.com', '11-8901-2345', 'La Plata 258'],
        ];

        $clientes = [];
        foreach ($clientesData as [$nombre, $apellido, $doc, $email, $tel, $dir]) {
            $clientes[] = Cliente::create([
                'nombre'        => $nombre,
                'apellido'      => $apellido,
                'documento'     => $doc,
                'email'         => $email,
                'telefono'      => $tel,
                'direccion'     => $dir,
                'observaciones' => 'Cliente de demo.',
                'estado'        => 'activo',
            ]);
        }

        // ---------- Proveedores ----------
        $proveedoresData = [
            ['Distribuidora Norte', '30-12345678-9', '11-4000-1111', 'compras@dnorte.com', 'Av. Norte 100'],
            ['Mayorista Sur', '30-98765432-1', '11-4000-2222', 'ventas@msur.com', 'Av. Sur 200'],
            ['Importadora Este', '30-11223344-5', '11-4000-3333', 'info@ieste.com', 'Calle Este 300'],
        ];

        $proveedores = [];
        foreach ($proveedoresData as [$nombre, $cuit, $tel, $email, $dir]) {
            $proveedores[] = Proveedor::create([
                'nombre'   => $nombre,
                'cuit'     => $cuit,
                'telefono' => $tel,
                'email'    => $email,
                'direccion' => $dir,
            ]);
        }

        $now = now();

        // ---------- Ventas (completadas, últimos 30 días) ----------
        for ($i = 0; $i < 40; $i++) {
            $fecha = $now->copy()->subDays(rand(0, 29))->setTime(rand(8, 20), rand(0, 59));
            $cliente = $clientes[array_rand($clientes)];
            $nDet = rand(1, 4);
            $detalles = [];
            $subtotal = 0;

            for ($j = 0; $j < $nDet; $j++) {
                $prod = $productos[array_rand($productos)];
                if ($prod->estado !== 'activo') {
                    continue;
                }
                $cantidad = rand(1, 5);
                $precio = $prod->precio_venta;
                $sub = $cantidad * $precio;
                $subtotal += $sub;
                $detalles[] = ['producto' => $prod, 'cantidad' => $cantidad, 'precio' => $precio, 'subtotal' => $sub];
            }

            if (empty($detalles)) {
                continue;
            }

            $descuento = rand(0, 2) === 0 ? round($subtotal * 0.1, 2) : 0;
            $impuesto = round(($subtotal - $descuento) * 0.21, 2);
            $totalFinal = round($subtotal - $descuento + $impuesto, 2);

            $venta = Venta::create([
                'numero'          => 'VTA-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'cliente_id'      => $cliente->id,
                'fecha'           => $fecha,
                'subtotal'        => $subtotal,
                'descuento'       => $descuento,
                'descuento_tipo'  => 'monto',
                'impuesto'        => $impuesto,
                'total'           => $subtotal,
                'total_final'     => $totalFinal,
                'estado'          => 'completada',
                'user_id'         => $admin?->id,
            ]);

            foreach ($detalles as $d) {
                VentaDetalle::create([
                    'venta_id'    => $venta->id,
                    'producto_id' => $d['producto']->id,
                    'cantidad'    => $d['cantidad'],
                    'precio'      => $d['precio'],
                    'subtotal'    => $d['subtotal'],
                ]);
            }

            // Movimientos de salida asociados a la venta
            foreach ($detalles as $d) {
                MovimientoStock::create([
                    'producto_id'     => $d['producto']->id,
                    'tipo'            => 'salida',
                    'cantidad'        => $d['cantidad'],
                    'user_id'         => $admin?->id,
                    'referencia_tipo' => 'venta',
                    'referencia_id'   => $venta->id,
                ]);
            }
        }

        // ---------- Compras (completadas) ----------
        for ($i = 0; $i < 15; $i++) {
            $fecha = $now->copy()->subDays(rand(0, 29))->setTime(rand(8, 18), rand(0, 59));
            $proveedor = $proveedores[array_rand($proveedores)];
            $nDet = rand(1, 3);
            $detalles = [];
            $subtotal = 0;

            for ($j = 0; $j < $nDet; $j++) {
                $prod = $productos[array_rand($productos)];
                $cantidad = rand(5, 30);
                $precio = $prod->precio_compra;
                $sub = $cantidad * $precio;
                $subtotal += $sub;
                $detalles[] = ['producto' => $prod, 'cantidad' => $cantidad, 'precio' => $precio, 'subtotal' => $sub];
            }

            $impuesto = round($subtotal * 0.21, 2);
            $totalFinal = round($subtotal + $impuesto, 2);

            $compra = Compra::create([
                'numero'          => 'COM-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'proveedor_id'    => $proveedor->id,
                'fecha'           => $fecha,
                'subtotal'        => $subtotal,
                'descuento'       => 0,
                'descuento_tipo'  => 'monto',
                'impuesto'        => $impuesto,
                'total'           => $subtotal,
                'total_final'     => $totalFinal,
                'estado'          => 'completada',
                'user_id'         => $admin?->id,
            ]);

            foreach ($detalles as $d) {
                CompraDetalle::create([
                    'compra_id'   => $compra->id,
                    'producto_id' => $d['producto']->id,
                    'cantidad'    => $d['cantidad'],
                    'precio'      => $d['precio'],
                    'subtotal'    => $d['subtotal'],
                ]);

                MovimientoStock::create([
                    'producto_id'     => $d['producto']->id,
                    'tipo'            => 'entrada',
                    'cantidad'        => $d['cantidad'],
                    'user_id'         => $admin?->id,
                    'referencia_tipo' => 'compra',
                    'referencia_id'   => $compra->id,
                ]);
            }
        }

        $this->command?->info('DemoSeeder: datos de demostración creados.');
    }

    private function limpiarTablas(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF;');
        foreach ([
            'movimientos_stock', 'ventas_detalle', 'ventas', 'compras_detalle', 'compras',
            'clientes', 'proveedores', 'productos', 'categorias',
        ] as $tabla) {
            DB::table($tabla)->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name = '$tabla'");
        }
        DB::statement('PRAGMA foreign_keys = ON;');
    }
}
