<?php

namespace App\Services;

use App\Models\Proveedor;
use App\Support\Mongo\Conexion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection as ColeccionMongo;

/**
 * Los números de los reportes y del dashboard, calculados con agregaciones de
 * MongoDB (decisión D3). Reemplaza al SQL crudo (JOIN + GROUP BY) de
 * ReporteController y DashboardController.
 *
 * Los importes se suman en la base como Decimal128 (D6), sin float de por medio;
 * recién al devolver la fila se pasan a número con dos decimales.
 *
 * Cada método devuelve filas (stdClass) con los mismos campos que antes (`$fila->total`,
 * `$fila->nombre`...), así que las vistas no cambian.
 */
class ReportesService
{
    // ---- Ventas y compras por período ----

    /**
     * @param  'ventas'|'compras'  $coleccion
     * @param  'diario'|'semanal'|'mensual'  $periodo
     * @return Collection<int,mixed>
     */
    public function porPeriodo(string $coleccion, string $periodo, Carbon $hoy): Collection
    {
        $desde = match ($periodo) {
            'semanal' => $hoy->copy()->subWeeks(12),
            'mensual' => $hoy->copy()->subMonths(12),
            default   => $hoy->copy()->subDays(30),
        };

        $formato = match ($periodo) {
            'semanal' => '%G-%V',   // semana ISO: año-semana
            'mensual' => '%Y-%m',
            default   => '%Y-%m-%d',
        };

        $filas = $this->agregar($coleccion, [
            ['$match' => ['estado' => 'completada', 'fecha' => ['$gte' => $this->fecha($desde)]]],
            ['$group' => [
                '_id'      => ['$dateToString' => ['format' => $formato, 'date' => '$fecha', 'timezone' => config('app.timezone')]],
                'cantidad' => ['$sum' => 1],
                'total'    => ['$sum' => '$total'],
                'primera'  => ['$min' => '$fecha'],
            ]],
            ['$sort' => ['_id' => -1]],
        ]);

        return collect($filas)->map(fn ($f): \stdClass => $periodo === 'semanal'
            ? (object) [
                'semana'   => $f['_id'],
                'periodo'  => $this->aFecha($f['primera'])->toDateString(),
                'cantidad' => (int) $f['cantidad'],
                'total'    => $this->dinero($f['total']),
            ]
            : (object) [
                'periodo'  => $f['_id'],
                'cantidad' => (int) $f['cantidad'],
                'total'    => $this->dinero($f['total']),
            ])->values();
    }

    /**
     * Ventas completadas por día entre dos fechas (gráfico del dashboard).
     *
     * @return Collection<int,mixed>
     */
    public function ventasDiarias(string $desde, string $hasta): Collection
    {
        $filas = $this->agregar('ventas', [
            ['$match' => ['estado' => 'completada', 'fecha' => $this->entre($desde, $hasta)]],
            ['$group' => [
                '_id'   => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$fecha', 'timezone' => config('app.timezone')]],
                'total' => ['$sum' => '$total'],
            ]],
            ['$sort' => ['_id' => 1]],
        ]);

        return collect($filas)->map(fn ($f): \stdClass => (object) ['fecha' => $f['_id'], 'total' => $this->dinero($f['total'])])->values();
    }

    // ---- Productos, clientes y proveedores ----

    /** @return Collection<int,mixed> */
    public function productosMasVendidos(string $desde, string $hasta, int $limite): Collection
    {
        $filas = $this->agregar('ventas_detalle', [
            ...$this->detallesDeVentasCompletadas($this->entre($desde, $hasta)),
            ['$group' => [
                '_id'             => '$producto_id',
                'total_vendido'   => ['$sum' => '$cantidad'],
                'total_facturado' => ['$sum' => '$subtotal'],
            ]],
            ...$this->conProducto(),
            ['$sort'  => ['total_vendido' => -1]],
            ['$limit' => $limite],
        ]);

        return collect($filas)->map(fn ($f): \stdClass => (object) [
            'nombre'          => $f['producto']['nombre'],
            'codigo'          => $f['producto']['codigo'] ?? null,
            'total_vendido'   => (int) $f['total_vendido'],
            'total_facturado' => $this->dinero($f['total_facturado']),
        ])->values();
    }

    /** @return Collection<int,mixed> */
    public function mejoresClientes(string $desde, string $hasta, int $limite): Collection
    {
        $filas = $this->agregar('ventas', [
            ['$match' => ['estado' => 'completada', 'fecha' => $this->entre($desde, $hasta)]],
            ['$group'  => ['_id' => '$cliente_id', 'total_compras' => ['$sum' => 1], 'total_gastado' => ['$sum' => '$total']]],
            ['$lookup' => ['from' => 'clientes', 'localField' => '_id', 'foreignField' => '_id', 'as' => 'cliente']],
            ['$unwind' => '$cliente'],
            ['$sort'   => ['total_gastado' => -1]],
            ['$limit'  => $limite],
        ]);

        return collect($filas)->map(fn ($f): \stdClass => (object) [
            'nombre'        => $f['cliente']['nombre'],
            'apellido'      => $f['cliente']['apellido'] ?? null,
            'email'         => $f['cliente']['email'] ?? null,
            'total_compras' => (int) $f['total_compras'],
            'total_gastado' => $this->dinero($f['total_gastado']),
        ])->values();
    }

    /** @return Collection<int,mixed> */
    public function proveedoresRanking(int $limite): Collection
    {
        $filas = $this->agregar('compras', [
            ['$match' => ['estado' => 'completada']],
            ['$group'  => ['_id' => '$proveedor_id', 'total_compras' => ['$sum' => 1], 'total_gastado' => ['$sum' => '$total']]],
            ['$lookup' => ['from' => 'proveedores', 'localField' => '_id', 'foreignField' => '_id', 'as' => 'proveedor']],
            ['$unwind' => '$proveedor'],
            ['$sort'   => ['total_gastado' => -1]],
            ['$limit'  => $limite],
        ]);

        return collect($filas)->map(fn ($f): \stdClass => (object) [
            'nombre'        => $f['proveedor']['nombre'],
            'email'         => $f['proveedor']['email'] ?? null,
            'telefono'      => $f['proveedor']['telefono'] ?? null,
            'total_compras' => (int) $f['total_compras'],
            'total_gastado' => $this->dinero($f['total_gastado']),
        ])->values();
    }

    /** Proveedores a los que se les compró algo (compras completadas). */
    public function proveedoresConCompras(): int
    {
        $ids = $this->coleccion('compras')->distinct('proveedor_id', ['estado' => 'completada']);

        return Proveedor::whereIn('id', $ids)->count();
    }

    // ---- Rentabilidad ----

    /**
     * Ganancia por producto en un rango: facturado menos costo de lo vendido
     * (cantidad x precio de compra actual del producto).
     *
     * @return Collection<int,mixed>
     */
    public function rentabilidadPorProducto(string $desde, string $hasta): Collection
    {
        $filas = $this->agregar('ventas_detalle', [
            ...$this->detallesDeVentasCompletadas($this->entre($desde, $hasta)),
            ['$group' => [
                '_id'               => '$producto_id',
                'unidades_vendidas' => ['$sum' => '$cantidad'],
                'total_facturado'   => ['$sum' => '$subtotal'],
            ]],
            ...$this->conProducto(),
            ['$lookup' => ['from' => 'categorias', 'localField' => 'producto.categoria_id', 'foreignField' => '_id', 'as' => 'categoria']],
            ['$addFields' => [
                'costo_total' => ['$multiply' => ['$unidades_vendidas', '$producto.precio_compra']],
            ]],
            ['$addFields' => ['ganancia_total' => ['$subtract' => ['$total_facturado', '$costo_total']]]],
            ['$sort'      => ['ganancia_total' => -1]],
        ]);

        return collect($filas)->map(function ($f): \stdClass {
            $facturado = $this->dinero($f['total_facturado']);
            $ganancia = $this->dinero($f['ganancia_total']);

            return (object) [
                'codigo'            => $f['producto']['codigo'] ?? null,
                'nombre'            => $f['producto']['nombre'],
                'categoria'         => isset($f['categoria'][0]) ? $f['categoria'][0]['nombre'] : null,
                'precio_compra'     => $this->dinero($f['producto']['precio_compra'] ?? 0),
                'precio_venta'      => $this->dinero($f['producto']['precio_venta'] ?? 0),
                'unidades_vendidas' => (int) $f['unidades_vendidas'],
                'total_facturado'   => $facturado,
                'costo_total'       => $this->dinero($f['costo_total']),
                'ganancia_total'    => $ganancia,
                'margen_porcentaje' => $facturado > 0 ? ($ganancia / $facturado) * 100 : 0,
            ];
        })->values();
    }

    /**
     * Margen bruto de un mes: facturado menos costo de la mercadería vendida.
     * El panel y el reporte de ganancias comparten esta fórmula.
     */
    public function gananciaBruta(int $anio, int $mes): float
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
        $fin = $inicio->copy()->addMonth();

        $fila = $this->agregar('ventas_detalle', [
            ...$this->detallesDeVentasCompletadas(['$gte' => $this->fecha($inicio), '$lt' => $this->fecha($fin)]),
            ...$this->conProducto('producto_id'),
            ['$group' => [
                '_id'       => null,
                'facturado' => ['$sum' => '$subtotal'],
                'costo'     => ['$sum' => ['$multiply' => ['$cantidad', '$producto.precio_compra']]],
            ]],
        ])[0] ?? null;

        return $fila === null ? 0.0 : round($this->dinero($fila['facturado']) - $this->dinero($fila['costo']), 2);
    }

    /**
     * Los productos que más ganancia dejaron en un mes.
     *
     * @return Collection<int,mixed>
     */
    public function topRentables(int $anio, int $mes, int $limite): Collection
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();

        $filas = $this->agregar('ventas_detalle', [
            ...$this->detallesDeVentasCompletadas(['$gte' => $this->fecha($inicio), '$lt' => $this->fecha($inicio->copy()->addMonth())]),
            ['$group' => [
                '_id'       => '$producto_id',
                'vendidos'  => ['$sum' => '$cantidad'],
                'facturado' => ['$sum' => '$subtotal'],
            ]],
            ...$this->conProducto(),
            ['$addFields' => ['ganancia' => ['$subtract' => ['$facturado', ['$multiply' => ['$vendidos', '$producto.precio_compra']]]]]],
            ['$sort'      => ['ganancia' => -1]],
            ['$limit'     => $limite],
        ]);

        return collect($filas)->map(fn ($f): \stdClass => (object) [
            'nombre'   => $f['producto']['nombre'],
            'vendidos' => (int) $f['vendidos'],
            'ganancia' => $this->dinero($f['ganancia']),
        ])->values();
    }

    /**
     * Unidades vendidas por nombre de producto en un rango (gráfico del dashboard).
     *
     * @return Collection<int,mixed>
     */
    public function topProductosPorCantidad(string $desde, string $hasta, int $limite): Collection
    {
        $filas = $this->agregar('ventas_detalle', [
            ...$this->detallesDeVentasCompletadas($this->entre($desde, $hasta)),
            ['$group' => ['_id' => '$producto_id', 'cantidad' => ['$sum' => '$cantidad']]],
            ...$this->conProducto(),
            // El SQL agrupaba por nombre: dos productos con el mismo nombre se suman.
            ['$group' => ['_id' => '$producto.nombre', 'cantidad' => ['$sum' => '$cantidad']]],
            ['$sort'  => ['cantidad' => -1]],
            ['$limit' => $limite],
        ]);

        return collect($filas)->map(fn ($f): \stdClass => (object) ['nombre' => $f['_id'], 'cantidad' => (int) $f['cantidad']])->values();
    }

    /**
     * Cantidad de movimientos de stock por tipo en un mes.
     *
     * @return Collection<int,mixed>
     */
    public function movimientosDelMes(int $anio, int $mes): Collection
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();

        $filas = $this->agregar('movimientos_stock', [
            ['$match' => ['created_at' => ['$gte' => $this->fecha($inicio), '$lt' => $this->fecha($inicio->copy()->addMonth())]]],
            ['$group' => ['_id' => '$tipo', 'cantidad' => ['$sum' => 1]]],
            ['$sort'  => ['_id' => 1]],
        ]);

        return collect($filas)->map(fn ($f): \stdClass => (object) ['tipo' => $f['_id'], 'cantidad' => (int) $f['cantidad']])->values();
    }

    // ---- Piezas de pipeline ----

    /**
     * Etapas que dejan los renglones de ventas completadas dentro de un rango de fechas
     * (el JOIN de ventas_detalle con ventas).
     *
     * @param  array<string,mixed>  $rangoFecha  condición sobre `venta.fecha`
     * @return list<array<string,mixed>>
     */
    private function detallesDeVentasCompletadas(array $rangoFecha): array
    {
        return [
            ['$lookup' => ['from' => 'ventas', 'localField' => 'venta_id', 'foreignField' => '_id', 'as' => 'venta']],
            ['$unwind' => '$venta'],
            ['$match'  => ['venta.estado' => 'completada', 'venta.fecha' => $rangoFecha]],
        ];
    }

    /**
     * Agrega el producto de cada fila. Por defecto el grupo tiene `_id` = producto_id; sin agrupar
     * se pasa el campo (`producto_id`). Si el producto ya no existe la fila se descarta, igual que
     * con el INNER JOIN.
     *
     * @return list<array<string,mixed>>
     */
    private function conProducto(string $campo = '_id'): array
    {
        return [
            ['$lookup' => ['from' => 'productos', 'localField' => $campo, 'foreignField' => '_id', 'as' => 'producto']],
            ['$unwind' => '$producto'],
        ];
    }

    // ---- Utilidades ----

    /** @return array<string,UTCDateTime> */
    private function entre(string $desde, string $hasta): array
    {
        return ['$gte' => $this->fecha(Carbon::parse($desde)->startOfDay()), '$lte' => $this->fecha(Carbon::parse($hasta)->startOfDay())];
    }

    private function fecha(Carbon $fecha): UTCDateTime
    {
        return new UTCDateTime($fecha);
    }

    private function aFecha(UTCDateTime $fecha): Carbon
    {
        return Carbon::instance($fecha->toDateTime())->setTimezone(config('app.timezone'));
    }

    /** Decimal128 (o número) a float con dos decimales, solo al final. */
    private function dinero(mixed $valor): float
    {
        return round((float) (string) $valor, 2);
    }

    private function coleccion(string $nombre): ColeccionMongo
    {
        return Conexion::mongo()->getDatabase()->selectCollection($nombre);
    }

    /**
     * @param  list<array<string,mixed>>  $pipeline
     * @return list<array<string,mixed>>
     */
    private function agregar(string $coleccion, array $pipeline): array
    {
        $filas = [];

        foreach ($this->coleccion($coleccion)->aggregate($pipeline) as $fila) {
            $filas[] = $fila instanceof \ArrayObject ? $fila->getArrayCopy() : (array) $fila;
        }

        return $filas;
    }
}
