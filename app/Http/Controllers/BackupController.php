<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BackupController extends Controller
{
    public function index()
    {
        $backups = $this->listarBackups();

        return view('backup.index', compact('backups'));
    }

    public function crear()
    {
        $db = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        $nombre = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $ruta = storage_path("app/backups/{$nombre}");

        $cmd = sprintf(
            'mysqldump -h %s -u %s %s > "%s" 2>&1',
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($db),
            $ruta
        );

        if (!empty($pass)) {
            $cmd = sprintf(
                'mysqldump -h %s -u %s -p%s %s > "%s" 2>&1',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($db),
                $ruta
            );
        }

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($ruta)) {
            return redirect()->route('backup.index')
                ->with('error', 'Error al crear backup: ' . implode("\n", $output));
        }

        $size = File::size($ruta);
        $sizeKB = round($size / 1024, 1);

        return redirect()->route('backup.index')
            ->with('success', "Backup creado: {$nombre} ({$sizeKB} KB)");
    }

    public function descargar($archivo)
    {
        $ruta = storage_path("app/backups/{$archivo}");

        if (!File::exists($ruta)) {
            abort(404);
        }

        return response()->download($ruta, $archivo, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function restaurar(Request $request)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:sql,txt', 'max:51240'],
        ]);

        $file = $request->file('backup_file');
        $db = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        $tmpFile = $file->getPathname();

        $cmd = sprintf(
            'mysql -h %s -u %s %s < "%s" 2>&1',
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($db),
            $tmpFile
        );

        if (!empty($pass)) {
            $cmd = sprintf(
                'mysql -h %s -u %s -p%s %s < "%s" 2>&1',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($db),
                $tmpFile
            );
        }

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            return redirect()->route('backup.index')
                ->with('error', 'Error al restaurar: ' . implode("\n", $output));
        }

        return redirect()->route('backup.index')
            ->with('success', 'Base de datos restaurada correctamente.');
    }

    public function eliminar($archivo)
    {
        $ruta = storage_path("app/backups/{$archivo}");

        if (File::exists($ruta)) {
            File::delete($ruta);
        }

        return redirect()->route('backup.index')
            ->with('success', "Backup {$archivo} eliminado.");
    }

    private function listarBackups(): array
    {
        $directorio = storage_path('app/backups');

        if (!File::isDirectory($directorio)) {
            return [];
        }

        $archivos = File::files($directorio);
        $backups = [];

        foreach ($archivos as $archivo) {
            if ($archivo->getExtension() === 'sql') {
                $backups[] = [
                    'nombre' => $archivo->getFilename(),
                    'tamano' => round($archivo->getSize() / 1024, 1),
                    'fecha'  => $archivo->getMTime(),
                ];
            }
        }

        usort($backups, fn ($a, $b) => $b['fecha'] <=> $a['fecha']);

        return $backups;
    }
}
