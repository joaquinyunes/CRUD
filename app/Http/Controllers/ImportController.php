<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportarRequest;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Support\LectorCsv;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function index()
    {
        return view('importar.index');
    }

    public function importar(ImportarRequest $request)
    {
        $lector = new LectorCsv($request->file('archivo')->getPathname());

        $importados = 0;
        $errorMessages = [];

        foreach ($lector->filas() as $numeroFila => $data) {
            try {
                match ($request->tipo) {
                    'productos'   => $this->importarProducto($data),
                    'clientes'    => $this->importarCliente($data),
                    'proveedores' => $this->importarProveedor($data),
                };
                $importados++;
            } catch (\Throwable $e) {
                $errorMessages[] = "Fila {$numeroFila}: {$e->getMessage()}";
            }
        }

        $errores = count($errorMessages);
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
        if (! $nombre) {
            throw new \Exception('El nombre es obligatorio.');
        }

        Producto::updateOrCreate(
            ['codigo' => $this->codigoDeProducto($data, $nombre)],
            [
                'nombre'        => $nombre,
                'descripcion'   => $data['descripcion'] ?? null,
                'marca'         => $data['marca'] ?? null,
                'precio_compra' => LectorCsv::numero($data['precio_compra'] ?? null),
                'precio_venta'  => LectorCsv::numero($data['precio_venta'] ?? null),
                'stock'         => (int) LectorCsv::numero($data['stock'] ?? null),
                'stock_minimo'  => (int) LectorCsv::numero($data['stock_minimo'] ?? null),
                'categoria_id'  => $this->resolveCategoria($data['categoria'] ?? null),
                'estado'        => 'activo',
            ]
        );
    }

    /**
     * El código con el que se identifica el producto al importar.
     *
     * Si el archivo no trae uno, se deriva del nombre en lugar de inventar un
     * valor al azar: así reimportar la misma planilla actualiza los productos en
     * vez de duplicarlos, que es lo que updateOrCreate promete.
     *
     * @param  array<string,mixed>  $data
     */
    private function codigoDeProducto(array $data, string $nombre): string
    {
        $codigo = trim((string) ($data['codigo'] ?? ''));

        if ($codigo !== '') {
            return $codigo;
        }

        return 'IMP-'.strtoupper(substr(md5(Str::lower(trim($nombre))), 0, 10));
    }

    private function importarCliente(array $data): void
    {
        $nombre = $data['nombre'] ?? null;
        if (! $nombre) {
            throw new \Exception('El nombre es obligatorio.');
        }

        $this->verificarEmail($data['email'] ?? null);

        // Con documento, reimportar la planilla actualiza al cliente en vez de duplicarlo.
        $documento = ($data['documento'] ?? '') !== '' ? $data['documento'] : null;

        Cliente::updateOrCreate(
            $documento ? ['documento' => $documento] : ['nombre' => $nombre, 'apellido' => $data['apellido'] ?? '', 'email' => $data['email'] ?? null],
            [
                'nombre'    => $nombre,
                'apellido'  => $data['apellido'] ?? '',
                'documento' => $documento,
                'email'     => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                'telefono'  => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'estado'    => 'activo',
            ]
        );
    }

    private function importarProveedor(array $data): void
    {
        $nombre = $data['nombre'] ?? null;
        if (! $nombre) {
            throw new \Exception('El nombre es obligatorio.');
        }

        $this->verificarEmail($data['email'] ?? null);

        $cuit = ($data['cuit'] ?? '') !== '' ? $data['cuit'] : null;

        Proveedor::updateOrCreate(
            $cuit ? ['cuit' => $cuit] : ['nombre' => $nombre],
            [
                'nombre'    => $nombre,
                'cuit'      => $cuit,
                'telefono'  => $data['telefono'] ?? null,
                'email'     => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                'direccion' => $data['direccion'] ?? null,
            ]
        );
    }

    private function verificarEmail(?string $email): void
    {
        if ($email !== null && $email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("El correo «{$email}» no es válido.");
        }
    }

    /**
     * La categoría del producto es obligatoria en la base, y una planilla de
     * productos casi nunca la trae completa: la que no existe se crea, y los
     * productos sin categoría van a "Sin categoría" en vez de fallar fila por fila.
     */
    private function resolveCategoria(?string $nombre): int
    {
        $nombre = trim((string) $nombre) !== '' ? trim((string) $nombre) : 'Sin categoría';

        return Categoria::firstOrCreate(['nombre' => $nombre], ['estado' => true])->id;
    }
}
