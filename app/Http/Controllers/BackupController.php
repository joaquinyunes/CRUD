<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaDeNegocioException;
use App\Http\Requests\RestaurarBackupRequest;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    public function index(): View
    {
        return view('backup.index', ['backups' => $this->backups->listar()]);
    }

    public function crear(): RedirectResponse
    {
        try {
            $nombre = $this->backups->crear();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('backup.index')->with('error', 'No se pudo crear el backup: '.$e->getMessage());
        }

        return redirect()->route('backup.index')->with('success', "Backup creado: {$nombre}");
    }

    public function descargar(string $archivo): StreamedResponse
    {
        abort_unless($this->backups->existe($archivo), 404);

        return $this->backups->descargar($archivo);
    }

    public function restaurar(RestaurarBackupRequest $request): RedirectResponse
    {
        try {
            $this->backups->restaurar($request->file('backup_file')->getPathname());
        } catch (ReglaDeNegocioException $e) {
            return redirect()->route('backup.index')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('backup.index')
                ->with('error', 'No se pudo restaurar y la base quedó como estaba: '.$e->getMessage());
        }

        return redirect()->route('backup.index')->with('success', 'Base de datos restaurada correctamente.');
    }

    public function eliminar(string $archivo): RedirectResponse
    {
        abort_unless($this->backups->nombreValido($archivo), 404);

        $this->backups->eliminar($archivo);

        return redirect()->route('backup.index')->with('success', "Backup {$archivo} eliminado.");
    }
}
