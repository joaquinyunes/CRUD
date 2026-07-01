<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Producto::with('categoria')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Código',
            'Nombre',
            'Descripción',
            'Categoría',
            'Marca',
            'Precio Compra',
            'Precio Venta',
            'Stock',
            'Stock Mínimo',
            'Estado',
        ];
    }

    public function map($producto): array
    {
        return [
            $producto->codigo,
            $producto->nombre,
            $producto->descripcion ?? '',
            $producto->categoria->nombre ?? '',
            $producto->marca ?? '',
            $producto->precio_compra,
            $producto->precio_venta,
            $producto->stock,
            $producto->stock_minimo,
            $producto->estado,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
