<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Proveedor;
use App\Services\PagoDocumentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CuentaCorrienteController extends Controller
{
    public function __construct(private PagoDocumentoService $pagos) {}

    public function clientes(Request $request): View
    {
        $clientes = Cliente::where('estado', 'activo')
            ->when($request->filled('buscar'), fn ($q) => $q->buscar($request->buscar))
            ->orderBy('nombre')->get()
            ->map(function (Cliente $c) {
                $c->saldo_actual = $c->saldo();

                return $c;
            })
            ->when($request->input('con_saldo'), fn ($col) => $col->filter(fn ($c) => $c->saldo_actual > 0)->values());

        return view('cuentas.clientes', compact('clientes'));
    }

    public function cliente(Cliente $cliente): View
    {
        $ventas = $cliente->ventas()
            ->with('pagos.metodoPago', 'devoluciones')
            ->whereNotIn('estado', ['anulada', 'cancelada'])
            ->orderByDesc('fecha')->orderByDesc('id')->get();

        $metodosPago = MetodoPago::activos()->get();
        $saldo = $cliente->saldo();

        return view('cuentas.cliente', compact('cliente', 'ventas', 'metodosPago', 'saldo'));
    }

    public function cobrarCliente(Request $request, Cliente $cliente): RedirectResponse
    {
        $data = $request->validate([
            'venta_id'       => ['nullable', 'exists:ventas,id'],
            'metodo_pago_id' => ['required', 'exists:metodos_pago,id'],
            'monto'          => ['required', 'numeric', 'min:0.01'],
            'referencia'     => ['nullable', 'string', 'max:255'],
        ]);

        $data['venta_id'] ??= null;
        $data['referencia'] ??= null;

        try {
            if ($data['venta_id']) {
                $venta = $cliente->ventas()->findOrFail($data['venta_id']);
                $this->pagos->pagarVenta($venta, $data['metodo_pago_id'], $data['monto'], $data['referencia']);
            } else {
                $this->distribuirFifo($cliente->ventas(), $data);
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['monto' => $e->getMessage()]);
        }

        return back()->with('success', 'Cobro registrado.');
    }

    public function proveedores(Request $request): View
    {
        $proveedores = Proveedor::when($request->filled('buscar'), fn ($q) => $q->buscar($request->buscar))
            ->orderBy('nombre')->get()
            ->map(function (Proveedor $p) {
                $p->saldo_actual = $p->saldo();

                return $p;
            });

        return view('cuentas.proveedores', compact('proveedores'));
    }

    public function proveedor(Proveedor $proveedor): View
    {
        $compras = $proveedor->compras()
            ->with('pagos.metodoPago', 'devoluciones')
            ->whereNotIn('estado', ['anulada', 'cancelada'])
            ->orderByDesc('fecha')->orderByDesc('id')->get();

        $metodosPago = MetodoPago::activos()->get();
        $saldo = $proveedor->saldo();

        return view('cuentas.proveedor', compact('proveedor', 'compras', 'metodosPago', 'saldo'));
    }

    public function pagarProveedor(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $data = $request->validate([
            'compra_id'      => ['nullable', 'exists:compras,id'],
            'metodo_pago_id' => ['required', 'exists:metodos_pago,id'],
            'monto'          => ['required', 'numeric', 'min:0.01'],
            'referencia'     => ['nullable', 'string', 'max:255'],
        ]);

        $data['compra_id'] ??= null;
        $data['referencia'] ??= null;

        try {
            if ($data['compra_id']) {
                $compra = $proveedor->compras()->findOrFail($data['compra_id']);
                $this->pagos->pagarCompra($compra, $data['metodo_pago_id'], $data['monto'], $data['referencia']);
            } else {
                $this->distribuirFifo($proveedor->compras(), $data, 'compra');
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['monto' => $e->getMessage()]);
        }

        return back()->with('success', 'Pago registrado.');
    }

    private function distribuirFifo($relacion, array $data, string $tipo = 'venta'): void
    {
        $restante = round((float) $data['monto'], 2);

        $docs = $relacion->whereIn('estado', ['completada', 'pendiente'])
            ->where('estado_pago', '!=', 'pagado')
            ->orderBy('fecha')->orderBy('id')->get()
            ->filter(fn ($d) => $d->saldoPendiente() > 0);

        if ($docs->isEmpty()) {
            throw new \RuntimeException('No hay documentos con saldo pendiente.');
        }

        $deudaTotal = round($docs->sum(fn ($d) => $d->saldoPendiente()), 2);
        if ($restante > $deudaTotal + 0.01) {
            throw new \RuntimeException('El monto ($'.number_format($restante, 2).') supera la deuda total ($'.number_format($deudaTotal, 2).').');
        }

        foreach ($docs as $doc) {
            if ($restante <= 0) {
                break;
            }
            $aplicar = min($restante, $doc->saldoPendiente());
            if ($tipo === 'venta') {
                $this->pagos->pagarVenta($doc, $data['metodo_pago_id'], $aplicar, $data['referencia'] ?? null);
            } else {
                $this->pagos->pagarCompra($doc, $data['metodo_pago_id'], $aplicar, $data['referencia'] ?? null);
            }
            $restante = round($restante - $aplicar, 2);
        }
    }
}
