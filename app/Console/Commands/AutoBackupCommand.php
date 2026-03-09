<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AutoBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:auto {--compress : Comprimir el respaldo} {--keep=7 : Número de respaldos a mantener}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear respaldo automático de la base de datos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Iniciando respaldo automático...');

            $compress = $this->option('compress');
            $keep = (int) $this->option('keep');

            // Crear directorio de respaldos si no existe
            $backupPath = storage_path('app/backups');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            // Configuración de la base de datos
            $dbHost = config('database.connections.mysql.host');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbName = config('database.connections.mysql.database');

            // Crear nombre del archivo
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = 'backup_' . $dbName . '_' . $timestamp . '.sql';
            $filepath = $backupPath . '/' . $filename;

            $this->info("Creando respaldo: {$filename}");

            // Comando mysqldump
            $command = "mysqldump -h {$dbHost} -u {$dbUser} -p{$dbPass} {$dbName} > {$filepath}";

            // Ejecutar comando
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Error al ejecutar mysqldump: ' . implode("\n", $output));
            }

            // Verificar que el archivo se creó
            if (!file_exists($filepath)) {
                throw new \Exception('El archivo de respaldo no se creó correctamente');
            }

            $fileSize = filesize($filepath);
            $this->info("Respaldo creado exitosamente. Tamaño: {$fileSize} bytes");

            // Comprimir si se solicitó
            if ($compress) {
                $this->info('Comprimiendo archivo...');
                $compressedFilepath = $filepath . '.gz';
                $compressed = gzopen($compressedFilepath, 'w9');
                gzwrite($compressed, file_get_contents($filepath));
                gzclose($compressed);

                // Eliminar archivo original
                unlink($filepath);
                $filepath = $compressedFilepath;
                $filename = basename($compressedFilepath);

                $this->info("Archivo comprimido: {$filename}");
            }

            // Limpiar respaldos antiguos
            $this->cleanOldBackups($backupPath, $keep);

            // Log del éxito
            Log::info('Respaldo automático creado exitosamente', [
                'filename' => $filename,
                'size' => filesize($filepath),
                'compressed' => $compress,
                'timestamp' => $timestamp
            ]);

            $this->info('✅ Respaldo automático completado exitosamente');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al crear respaldo automático: ' . $e->getMessage());
            Log::error('Error en respaldo automático: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Limpiar respaldos antiguos
     */
    private function cleanOldBackups($backupPath, $keep)
    {
        $files = glob($backupPath . '/backup_*.sql*');

        if (count($files) <= $keep) {
            return;
        }

        // Ordenar por fecha de modificación (más antiguos primero)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Eliminar archivos antiguos
        $filesToDelete = array_slice($files, 0, count($files) - $keep);

        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
                $this->info("Archivo antiguo eliminado: " . basename($file));
            }
        }
    }
}
