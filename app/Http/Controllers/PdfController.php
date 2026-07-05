<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function facturaVenta($id)
    {
        $venta = Venta::with(['detalles.producto', 'cliente', 'user'])->findOrFail($id);
        $pdf = Pdf::loadView('pdf.factura-venta', compact('venta'));
        return $pdf->stream("factura-{$venta->numero}.pdf");
    }

    public function facturaCompra($id)
    {
        $compra = Compra::with(['detalles.producto', 'proveedor', 'user'])->findOrFail($id);
        $pdf = Pdf::loadView('pdf.factura-compra', compact('compra'));
        return $pdf->stream("compra-{$compra->numero}.pdf");
    }

    public function catalogoProductos()
    {
        $productos = Producto::with('categoria')->where('estado', 'activo')->orderBy('nombre')->get();
        $pdf = Pdf::loadView('pdf.catalogo-productos', compact('productos'));
        return $pdf->stream("catalogo-productos.pdf");
    }

    public function reporteStock()
    {
        $productos = Producto::where('estado', 'activo')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->get();
        $pdf = Pdf::loadView('pdf.reporte-stock', compact('productos'));
        return $pdf->stream("reporte-stock.pdf");
    }
}
