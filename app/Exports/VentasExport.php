<?php

namespace App\Exports;

use App\Models\Venta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VentasExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected ?string $fechaDesde;
    protected ?string $fechaHasta;
    protected ?string $estado;

    public function __construct(?string $fechaDesde = null, ?string $fechaHasta = null, ?string $estado = null)
    {
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
        $this->estado = $estado;
    }

    public function collection()
    {
        $query = Venta::with('cliente', 'user');

        if ($this->fechaDesde) {
            $query->where('fecha', '>=', $this->fechaDesde);
        }
        if ($this->fechaHasta) {
            $query->where('fecha', '<=', $this->fechaHasta);
        }
        if ($this->estado) {
            $query->where('estado', $this->estado);
        }

        return $query->orderBy('fecha', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Número',
            'Cliente',
            'Fecha',
            'Total',
            'Estado',
            'Vendedor',
        ];
    }

    public function map($venta): array
    {
        return [
            $venta->numero,
            ($venta->cliente->nombre ?? '') . ' ' . ($venta->cliente->apellido ?? ''),
            $venta->fecha->format('d/m/Y'),
            $venta->total,
            ucfirst($venta->estado),
            $venta->user->name ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
