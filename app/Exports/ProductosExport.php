<?php

namespace App\Exports;

use App\Models\Producto;
use App\Support\CeldaSegura;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    /** El costo (precio de compra) solo sale para quien puede verlo. */
    public function __construct(private bool $conCosto = false) {}

    public function collection()
    {
        return Producto::with('categoria')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();
    }

    public function headings(): array
    {
        return array_values(array_filter([
            'Código',
            'Nombre',
            'Descripción',
            'Categoría',
            'Marca',
            $this->conCosto ? 'Precio Compra' : null,
            'Precio Venta',
            'Stock',
            'Stock Mínimo',
            'Estado',
        ], fn ($titulo) => $titulo !== null));
    }

    public function map($producto): array
    {
        $fila = [
            CeldaSegura::limpiar($producto->codigo),
            CeldaSegura::limpiar($producto->nombre),
            CeldaSegura::limpiar($producto->descripcion ?? ''),
            CeldaSegura::limpiar($producto->categoria->nombre ?? ''),
            CeldaSegura::limpiar($producto->marca ?? ''),
            $this->conCosto ? $producto->precio_compra : null,
            $producto->precio_venta,
            $producto->stock,
            $producto->stock_minimo,
            $producto->estado,
        ];

        return $this->conCosto ? $fila : array_values(array_filter($fila, fn ($v, $i) => $i !== 5, ARRAY_FILTER_USE_BOTH));
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
