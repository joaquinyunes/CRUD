<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\FacturaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacturacionController extends Controller
{
    public function __construct(private FacturaService $facturas) {}

    public function facturar(Request $request, Venta $venta): RedirectResponse
    {
        $data = $request->validate([
            'tipo_comprobante' => ['nullable', 'in:1,6,11'],
            'doc_tipo' => ['nullable', 'in:80,96,99'],
            'doc_nro' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $comprobante = $this->facturas->facturar(
                $venta,
                isset($data['tipo_comprobante']) ? (int) $data['tipo_comprobante'] : null,
                isset($data['doc_tipo']) ? (int) $data['doc_tipo'] : null,
                $data['doc_nro'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['factura' => 'No se pudo facturar: '.$e->getMessage()]);
        }

        $msg = $comprobante->autorizado()
            ? "Comprobante {$comprobante->numeroFormateado()} · CAE {$comprobante->cae}".($comprobante->resultado === 'simulado' ? ' (simulado)' : '')
            : 'AFIP rechazó el comprobante. Revisá las observaciones.';

        return back()->with($comprobante->autorizado() ? 'success' : 'error', $msg);
    }

    public function libroIva(Request $request): View
    {
        $desde = $request->get('fecha_desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('fecha_hasta', now()->toDateString());

        $comprobantes = $this->facturas->libroIva($desde, $hasta);

        $totales = [
            'neto' => $comprobantes->sum('importe_neto'),
            'iva' => $comprobantes->sum('importe_iva'),
            'total' => $comprobantes->sum('importe_total'),
        ];

        return view('facturacion.libro-iva', compact('comprobantes', 'totales', 'desde', 'hasta'));
    }
}
