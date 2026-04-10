<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $all = Setting::all();

        // Flat key→value map for easy use in Vue
        $values = $all->pluck('value', 'key')->toArray();

        return Inertia::render('Settings/Index', [
            'values' => $values,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'values' => 'required|array',
        ])['values'];

        try {
            foreach ($data as $key => $value) {
                Setting::where('key', $key)->update(['value' => $value ?? '']);
            }

            return redirect()->back()->with('success', 'La configuracion fue guardada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo guardar la configuracion. Intenta nuevamente.');
        }
    }

    public function backup()
    {
        try {
            $backupPath = Setting::get('backup_path', storage_path('backups'));

            if (!is_dir($backupPath)) {
                if (!mkdir($backupPath, 0755, true)) {
                    return redirect()->back()->with('error', 'No se pudo crear la carpeta de backups. Verifica los permisos del servidor.');
                }
            }

            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupPath . '/' . $filename;

            $dbHost = config('database.connections.mysql.host');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                $size = round(filesize($filepath) / 1024, 2);
                return redirect()->back()->with('success', 'Backup creado: ' . $filename . ' (' . $size . ' KB)');
            }

            return redirect()->back()->with('error', 'No se pudo crear el backup de la base de datos. Verifica que mysqldump este instalado y las credenciales sean correctas.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrio un error al crear el backup: ' . $e->getMessage());
        }
    }

    public function listBackups()
    {
        $backupPath = Setting::get('backup_path', storage_path('backups'));

        if (!is_dir($backupPath)) {
            return response()->json([]);
        }

        $files = glob($backupPath . '/*.sql');
        $backups = array_map(function ($file) {
            return [
                'name' => basename($file),
                'size' => round(filesize($file) / 1024, 2) . ' KB',
                'date' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }, $files);

        rsort($backups);

        return response()->json($backups);
    }
}
