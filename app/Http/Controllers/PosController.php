<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private PosService $pos,
        private CajaService $caja,
    ) {}

    public function index(): View
    {
        $sesion = $this->caja->sesionAbierta(auth()->id());
        $metodosPago = MetodoPago::activos()->get();
        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellido', 'limite_credito']);
        $simbolo = Setting::obtener('sistema_simbolo_moneda', '$');

        return view('pos.index', compact('sesion', 'metodosPago', 'clientes', 'simbolo'));
    }

    public function buscar(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'max:100']]);

        return response()->json($this->pos->buscar($request->string('q')->toString()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio' => ['required', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'descuento_tipo' => ['nullable', 'in:fijo,porcentaje'],
            'pagos' => ['required', 'array', 'min:1'],
            'pagos.*.metodo_pago_id' => ['required', 'exists:metodos_pago,id'],
            'pagos.*.monto' => ['required', 'numeric', 'gt:0'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:120'],
            'recibido' => ['nullable', 'numeric', 'min:0'],
            'pin_supervisor' => ['nullable', 'string'],
        ]);

        if ($this->requiereSupervisor($data) && ! $this->pinValido($request->input('pin_supervisor'))) {
            return response()->json(['message' => 'Descuento alto: requiere PIN de un supervisor.'], 422);
        }

        try {
            $venta = $this->pos->registrarVenta($data);
        } catch (StockInsuficienteException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'id' => $venta->id,
            'numero' => $venta->numero,
            'total' => (float) $venta->total_final,
            'vuelto' => (float) $venta->vuelto,
            'ticket_url' => route('pos.ticket', $venta),
        ]);
    }

    public function ticket(Venta $venta): View
    {
        abort_if($venta->estado === 'anulada', 404);
        $venta->load('detalles.producto', 'pagos.metodoPago', 'cliente', 'user');
        $negocio = [
            'nombre' => Setting::obtener('negocio_nombre', config('app.name')),
            'direccion' => Setting::obtener('negocio_direccion', ''),
            'cuit' => Setting::obtener('negocio_cuit', ''),
            'simbolo' => Setting::obtener('sistema_simbolo_moneda', '$'),
        ];

        return view('pos.ticket', compact('venta', 'negocio'));
    }

    /** @param array<string,mixed> $data */
    private function requiereSupervisor(array $data): bool
    {
        $limite = (float) Setting::obtener('ventas_limite_descuento', '10');
        if (($data['descuento_tipo'] ?? null) === 'porcentaje') {
            return (float) ($data['descuento'] ?? 0) > $limite;
        }

        return false;
    }

    private function pinValido(?string $pin): bool
    {
        if (! $pin) {
            return false;
        }

        return User::whereNotNull('pin_supervisor')->get()
            ->contains(fn ($u) => $u->tienePermiso('pos.supervisar') && Hash::check($pin, $u->pin_supervisor));
    }
}
