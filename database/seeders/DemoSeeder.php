<?php

namespace Database\Seeders;

use App\Models\CajaMovimiento;
use App\Models\CajaSesion;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->limpiarTablas();

        $admin = User::where('email', 'admin@admin.com')->first()
            ?? User::first();

        // El seed de demostracion necesita las unidades de medida, los metodos
        // de pago y la configuracion base. Si no estan, se cargan primero.
        if (UnidadMedida::where('estado', true)->doesntExist()) {
            $this->call(UniversalSeeder::class);
        }

        $unidades = UnidadMedida::where('estado', true)->get();
        $uni = fn (string $abr) => $unidades->firstWhere('abreviacion', $abr)?->id
            ?? $unidades->first()->id;

        // ---------- Categorías ----------
        $cats = [
            'Bebidas'           => 'Gaseosas, aguas, jugos y energizantes',
            'Lácteos'           => 'Leches, yogures y quesos',
            'Panadería'         => 'Pan, facturas y masas',
            'Frutas y Verduras' => 'Productos frescos de estación',
            'Limpieza'          => 'Detergentes y artículos del hogar',
            'Snacks'            => 'Galletitas, golosinas y snacks',
            'Carnicería'        => 'Carnes frescas y embutidos',
            'Tecnología'        => 'Accesorios y electrónica',
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
            ['Manzana Roja x kg',       'Frutas y Verduras', 'Granja', 50, 90, 35, 15, 'kg', 'activo'],
            ['Banana x kg',             'Frutas y Verduras', 'Granja', 45, 85, 6, 10, 'kg', 'activo'],
            ['Tomate x kg',             'Frutas y Verduras', 'Granja', 40, 80, 20, 10, 'kg', 'activo'],
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
            $codigo = 'PROD-'.str_pad((count($productos) + 1), 4, '0', STR_PAD_LEFT);
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
                'nombre'    => $nombre,
                'cuit'      => $cuit,
                'telefono'  => $tel,
                'email'     => $email,
                'direccion' => $dir,
            ]);
        }

        $now = now();

        // ---------- Ventas (completadas, últimos 30 días) ----------
        for ($i = 0; $i < 40; $i++) {
            // Las primeras cinco son de hoy: el panel muestra "ventas de hoy" y
            // con fechas al azar la demostracion puede aparecer en cero.
            $diasAtras = $i < 5 ? 0 : rand(1, 29);
            $fecha = $now->copy()->subDays($diasAtras)->setTime(rand(8, 20), rand(0, 59));
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
                'numero'         => 'VTA-'.str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'cliente_id'     => $cliente->id,
                'fecha'          => $fecha,
                'subtotal'       => $subtotal,
                'descuento'      => $descuento,
                'descuento_tipo' => 'monto',
                'impuesto'       => $impuesto,
                // La app guarda el total ya con descuento e impuesto en ambas
                // columnas; el seed tiene que hacer lo mismo o los importes del
                // panel no coinciden con los del comprobante.
                'total'       => $totalFinal,
                'total_final' => $totalFinal,
                'estado'      => 'completada',
                'user_id'     => $admin?->id,
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
                'numero'         => 'COM-'.str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'proveedor_id'   => $proveedor->id,
                'fecha'          => $fecha,
                'subtotal'       => $subtotal,
                'descuento'      => 0,
                'descuento_tipo' => 'monto',
                'impuesto'       => $impuesto,
                // La app guarda el total ya con descuento e impuesto en ambas
                // columnas; el seed tiene que hacer lo mismo o los importes del
                // panel no coinciden con los del comprobante.
                'total'       => $totalFinal,
                'total_final' => $totalFinal,
                'estado'      => 'completada',
                'user_id'     => $admin?->id,
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

        $this->sucursales($productos);
        $this->cajaDelDia($admin);
        $this->cuentaCorriente();

        $this->command?->info('DemoSeeder: datos de demostración creados.');
    }

    /**
     * Vacia las tablas de negocio antes de recargar la demostracion.
     *
     * Se hace con la API de Schema en vez de PRAGMA/sqlite_sequence para que
     * funcione igual en SQLite, MySQL y PostgreSQL: el seed de demostracion
     * tambien corre en el deploy.
     */
    private function limpiarTablas(): void
    {
        $tablas = [
            'movimientos_stock', 'ventas_detalle', 'ventas', 'compras_detalle', 'compras',
            'clientes', 'proveedores', 'productos', 'categorias',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tablas as $tabla) {
            DB::table($tabla)->delete();
            $this->reiniciarNumeracion($tabla);
        }

        Schema::enableForeignKeyConstraints();
    }

    /** Vuelve el autoincremento a 1, con la sintaxis que entiende cada motor. */
    private function reiniciarNumeracion(string $tabla): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite'           => DB::statement('DELETE FROM sqlite_sequence WHERE name = ?', [$tabla]),
            'pgsql'            => DB::statement("SELECT setval(pg_get_serial_sequence('$tabla', 'id'), 1, false)"),
            'mysql', 'mariadb' => DB::statement("ALTER TABLE $tabla AUTO_INCREMENT = 1"),
            default            => null,
        };
    }

    /**
     * Segundo deposito con parte del stock, para que el modulo multi-deposito
     * tenga algo que mostrar en la demostracion.
     */
    private function sucursales(array $productos): void
    {
        $principal = Deposito::where('es_principal', true)->first()
            ?? Deposito::orderBy('id')->first();

        $sucursal = Deposito::firstOrCreate(
            ['nombre' => 'Sucursal Centro'],
            ['direccion' => 'Av. Belgrano 1450', 'es_principal' => false, 'activo' => true]
        );

        $deposito = Deposito::firstOrCreate(
            ['nombre' => 'Depósito Externo'],
            ['direccion' => 'Parque Industrial, Galpón 7', 'es_principal' => false, 'activo' => true]
        );

        foreach ($productos as $indice => $producto) {
            $enSucursal = (int) floor($producto->stock * 0.3);
            $enExterno = (int) floor($producto->stock * 0.15);

            if ($enSucursal < 1) {
                continue;
            }

            $this->asignarStock($principal->id, $producto->id, max($producto->stock - $enSucursal - $enExterno, 0));
            $this->asignarStock($sucursal->id, $producto->id, $enSucursal);

            if ($enExterno >= 1 && $indice % 2 === 0) {
                $this->asignarStock($deposito->id, $producto->id, $enExterno);
            }
        }
    }

    private function asignarStock(int $depositoId, int $productoId, int $cantidad): void
    {
        DB::table('stock_deposito')->updateOrInsert(
            ['producto_id' => $productoId, 'deposito_id' => $depositoId],
            ['cantidad' => $cantidad]
        );
    }

    /**
     * Una caja abierta hoy, con los movimientos tipicos de una jornada.
     */
    private function cajaDelDia(?User $admin): void
    {
        if (! $admin) {
            return;
        }

        CajaMovimiento::query()->delete();
        CajaSesion::query()->delete();

        $ayer = CajaSesion::create([
            'user_id'               => $admin->id,
            'monto_inicial'         => 15000,
            'monto_final_declarado' => 48200,
            'monto_final_sistema'   => 48500,
            'diferencia'            => -300,
            'estado'                => 'cerrada',
            'observaciones'         => 'Faltante por vuelto mal dado.',
            'abierta_en'            => now()->subDay()->setTime(9, 0),
            'cerrada_en'            => now()->subDay()->setTime(20, 15),
        ]);

        $hoy = CajaSesion::create([
            'user_id'       => $admin->id,
            'monto_inicial' => 20000,
            'estado'        => 'abierta',
            'abierta_en'    => now()->setTime(9, 0),
        ]);

        $movimientos = [
            [$ayer->id, 'ingreso', 'Cobro de ventas del día', 36500, 'venta'],
            [$ayer->id, 'egreso',  'Pago a proveedor de bebidas', 3000, 'compra'],
            [$hoy->id,  'ingreso', 'Cobro venta VTA-00031', 8450, 'venta'],
            [$hoy->id,  'ingreso', 'Cobro venta VTA-00032', 12300, 'venta'],
            [$hoy->id,  'egreso',  'Flete de mercadería', 4500, 'manual'],
            [$hoy->id,  'ingreso', 'Cobro cuenta corriente Pérez', 6000, 'manual'],
            [$hoy->id,  'egreso',  'Compra de insumos de limpieza', 1800, 'manual'],
        ];

        foreach ($movimientos as [$sesionId, $tipo, $concepto, $monto, $referencia]) {
            CajaMovimiento::create([
                'caja_sesion_id'  => $sesionId,
                'tipo'            => $tipo,
                'concepto'        => $concepto,
                'monto'           => $monto,
                'referencia_tipo' => $referencia,
                'user_id'         => $admin->id,
            ]);
        }
    }

    /**
     * Deja algunas ventas impagas y otras parciales para que la cuenta
     * corriente de clientes tenga saldos reales.
     */
    private function cuentaCorriente(): void
    {
        Cliente::query()->update(['limite_credito' => 150000]);

        $ventas = Venta::where('estado', 'completada')->orderByDesc('fecha')->take(12)->get();

        foreach ($ventas as $indice => $venta) {
            $total = (float) $venta->total_final;

            if ($indice % 3 === 0) {
                continue;                      // queda impaga
            }

            $pagado = $indice % 3 === 1
                ? round($total * 0.4, 2)       // pago parcial
                : $total;                      // saldada

            DB::table('ventas_pago')->insert([
                'venta_id'       => $venta->id,
                'metodo_pago_id' => MetodoPago::orderBy('id')->value('id'),
                'monto'          => $pagado,
                'created_at'     => $venta->fecha,
                'updated_at'     => $venta->fecha,
            ]);
        }
    }
}
