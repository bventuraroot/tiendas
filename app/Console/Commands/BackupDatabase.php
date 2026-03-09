<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--compress : Comprimir el respaldo}
                            {--keep=7 : Número de respaldos a mantener}
                            {--path=backups : Directorio donde guardar los respaldos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un respaldo completo de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando respaldo de base de datos...');

        try {
            // Obtener configuración de base de datos
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            if (!$config) {
                $this->error('Configuración de base de datos no encontrada');
                return 1;
            }

            // Crear directorio de respaldos si no existe
            $backupPath = $this->option('path');
            $fullPath = storage_path("app/{$backupPath}");

            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Generar nombre del archivo
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$connection}_{$timestamp}.sql";
            $filepath = "{$fullPath}/{$filename}";

            // Ejecutar respaldo según el tipo de base de datos
            $success = $this->executeBackup($config, $filepath);

            if (!$success) {
                $this->error('Error al crear el respaldo');
                return 1;
            }

            // Comprimir si se solicita
            if ($this->option('compress')) {
                $this->info('Comprimiendo respaldo...');
                $compressedFile = $this->compressBackup($filepath);
                if ($compressedFile) {
                    unlink($filepath); // Eliminar archivo original
                    $filepath = $compressedFile;
                    $filename = basename($compressedFile);
                }
            }

            // Limpiar respaldos antiguos
            $this->cleanOldBackups($fullPath, $this->option('keep'));

            // Registrar en log
            Log::info("Respaldo de base de datos creado: {$filename}");

            $this->info("✅ Respaldo creado exitosamente: {$filename}");
            $this->info("📁 Ubicación: {$filepath}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error durante el respaldo: " . $e->getMessage());
            Log::error("Error en respaldo de base de datos: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Ejecutar el respaldo según el tipo de base de datos
     */
    private function executeBackup($config, $filepath)
    {
        $driver = $config['driver'];

        switch ($driver) {
            case 'mysql':
                return $this->backupMySQL($config, $filepath);
            case 'pgsql':
                return $this->backupPostgreSQL($config, $filepath);
            case 'sqlite':
                return $this->backupSQLite($config, $filepath);
            default:
                $this->error("Driver de base de datos no soportado: {$driver}");
                return false;
        }
    }

    /**
     * Respaldar MySQL
     */
    private function backupMySQL($config, $filepath)
    {
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        $command = "mysqldump --host={$host} --port={$port} --user={$username}";

        if ($password) {
            $command .= " --password={$password}";
        }

        $command .= " --single-transaction --routines --triggers --events";
        $command .= " --add-drop-database --add-drop-table";
        $command .= " {$database} > {$filepath}";

        $this->info("Ejecutando: mysqldump para {$database}");

        exec($command, $output, $returnCode);

        return $returnCode === 0 && file_exists($filepath);
    }

    /**
     * Respaldar PostgreSQL
     */
    private function backupPostgreSQL($config, $filepath)
    {
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        // Configurar variable de entorno para la contraseña
        putenv("PGPASSWORD={$password}");

        $command = "pg_dump --host={$host} --port={$port} --username={$username}";
        $command .= " --verbose --clean --no-owner --no-privileges";
        $command .= " {$database} > {$filepath}";

        $this->info("Ejecutando: pg_dump para {$database}");

        exec($command, $output, $returnCode);

        return $returnCode === 0 && file_exists($filepath);
    }

    /**
     * Respaldar SQLite
     */
    private function backupSQLite($config, $filepath)
    {
        $database = $config['database'];

        if (!file_exists($database)) {
            $this->error("Archivo de base de datos SQLite no encontrado: {$database}");
            return false;
        }

        $command = "sqlite3 {$database} .dump > {$filepath}";

        $this->info("Ejecutando: sqlite3 dump para {$database}");

        exec($command, $output, $returnCode);

        return $returnCode === 0 && file_exists($filepath);
    }

    /**
     * Comprimir archivo de respaldo
     */
    private function compressBackup($filepath)
    {
        $compressedFile = $filepath . '.gz';

        $command = "gzip -c {$filepath} > {$compressedFile}";

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($compressedFile)) {
            $this->info("✅ Archivo comprimido: " . basename($compressedFile));
            return $compressedFile;
        }

        $this->warn("⚠️ No se pudo comprimir el archivo");
        return false;
    }

    /**
     * Limpiar respaldos antiguos
     */
    private function cleanOldBackups($directory, $keepCount)
    {
        $files = glob($directory . '/backup_*.sql*');

        if (count($files) <= $keepCount) {
            return;
        }

        // Ordenar por fecha de modificación (más antiguos primero)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Eliminar archivos antiguos
        $filesToDelete = array_slice($files, 0, count($files) - $keepCount);

        foreach ($filesToDelete as $file) {
            unlink($file);
            $this->info("🗑️ Eliminado respaldo antiguo: " . basename($file));
        }

        $this->info("🧹 Limpieza completada. Se mantienen {$keepCount} respaldos.");
    }
}
