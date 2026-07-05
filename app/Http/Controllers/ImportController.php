<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ImportController extends Controller
{
    public function index()
    {
        return view('importar.index');
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'tipo'    => ['required', 'in:productos,clientes,proveedores'],
        ]);

        $file = $request->file('archivo');
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle, 0, ',');

        $headers = array_map('strtolower', array_map('trim', $headers));

        $importados = 0;
        $errores = 0;
        $errorMessages = [];

        $rowNumber = 1;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;
            $data = array_combine($headers, $row);

            try {
                switch ($request->tipo) {
                    case 'productos':
                        $this->importarProducto($data);
                        break;
                    case 'clientes':
                        $this->importarCliente($data);
                        break;
                    case 'proveedores':
                        $this->importarProveedor($data);
                        break;
                }
                $importados++;
            } catch (\Exception $e) {
                $errores++;
                $errorMessages[] = "Fila {$rowNumber}: {$e->getMessage()}";
            }
        }

        fclose($handle);

        $mensaje = "Importación completada: {$importados} registros importados.";
        if ($errores > 0) {
            $mensaje .= " {$errores} errores encontrados.";
        }

        return Redirect::route('importar.index')
            ->with('success', $mensaje)
            ->with('errores', $errorMessages);
    }

    private function importarProducto(array $data): void
    {
        $nombre = $data['nombre'] ?? null;
        if (!$nombre) {
            throw new \Exception('El nombre es obligatorio.');
        }

        Producto::updateOrCreate(
            ['codigo' => $data['codigo'] ?? uniq_id()],
            [
                'nombre'        => $nombre,
                'descripcion'   => $data['descripcion'] ?? null,
                'marca'         => $data['marca'] ?? null,
                'precio_compra' => (float) ($data['precio_compra'] ?? 0),
                'precio_venta'  => (float) ($data['precio_venta'] ?? 0),
                'stock'         => (int) ($data['stock'] ?? 0),
                'stock_minimo'  => (int) ($data['stock_minimo'] ?? 0),
                'categoria_id'  => $this->resolveCategoria($data['categoria'] ?? null),
                'estado'        => 'activo',
            ]
        );
    }

    private function importarCliente(array $data): void
    {
        $nombre = $data['nombre'] ?? null;
        if (!$nombre) {
            throw new \Exception('El nombre es obligatorio.');
        }

        Cliente::create([
            'nombre'    => $nombre,
            'apellido'  => $data['apellido'] ?? '',
            'documento' => $data['documento'] ?? null,
            'email'     => $data['email'] ?? null,
            'telefono'  => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'estado'    => 'activo',
        ]);
    }

    private function importarProveedor(array $data): void
    {
        $nombre = $data['nombre'] ?? null;
        if (!$nombre) {
            throw new \Exception('El nombre es obligatorio.');
        }

        Proveedor::create([
            'nombre'    => $nombre,
            'cuit'      => $data['cuit'] ?? null,
            'telefono'  => $data['telefono'] ?? null,
            'email'     => $data['email'] ?? null,
            'direccion' => $data['direccion'] ?? null,
        ]);
    }

    private function resolveCategoria(?string $nombre): ?int
    {
        if (!$nombre) return null;
        $cat = \App\Models\Categoria::where('nombre', $nombre)->first();
        return $cat?->id;
    }
}
