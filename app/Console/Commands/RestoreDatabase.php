<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RestoreDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore
                            {file : Nombre del archivo de respaldo a restaurar}
                            {--path=backups : Directorio donde buscar los respaldos}
                            {--force : Forzar la restauración sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaurar un respaldo de base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->argument('file');
        $backupPath = $this->option('path');
        $fullPath = storage_path("app/{$backupPath}");

        // Verificar si el archivo existe
        $filepath = "{$fullPath}/{$filename}";

        if (!file_exists($filepath)) {
            $this->error("❌ Archivo de respaldo no encontrado: {$filename}");
            $this->info("📁 Buscando en: {$fullPath}");

            // Mostrar archivos disponibles
            $files = glob($fullPath . '/backup_*.sql*');
            if (!empty($files)) {
                $this->info("📋 Archivos disponibles:");
                foreach ($files as $file) {
                    $this->line("  - " . basename($file));
                }
            }
            return 1;
        }

        // Confirmar restauración
        if (!$this->option('force')) {
            $this->warn("⚠️ ADVERTENCIA: Esta operación sobrescribirá la base de datos actual.");
            $this->warn("📁 Archivo a restaurar: {$filename}");
            $this->warn("📊 Tamaño: " . $this->formatBytes(filesize($filepath)));

            if (!$this->confirm('¿Estás seguro de que quieres continuar?')) {
                $this->info('❌ Restauración cancelada');
                return 0;
            }
        }

        $this->info('🔄 Iniciando restauración de base de datos...');

        try {
            // Obtener configuración de base de datos
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            if (!$config) {
                $this->error('Configuración de base de datos no encontrada');
                return 1;
            }

            // Descomprimir si es necesario
            $restoreFile = $filepath;
            if (pathinfo($filepath, PATHINFO_EXTENSION) === 'gz') {
                $this->info('📦 Descomprimiendo archivo...');
                $restoreFile = $this->decompressBackup($filepath);
                if (!$restoreFile) {
                    $this->error('Error al descomprimir el archivo');
                    return 1;
                }
            }

            // Ejecutar restauración
            $success = $this->executeRestore($config, $restoreFile);

            // Limpiar archivo temporal si se descomprimió
            if ($restoreFile !== $filepath && file_exists($restoreFile)) {
                unlink($restoreFile);
            }

            if (!$success) {
                $this->error('❌ Error al restaurar la base de datos');
                return 1;
            }

            // Registrar en log
            Log::info("Base de datos restaurada desde: {$filename}");

            $this->info("✅ Restauración completada exitosamente");
            $this->info("📁 Archivo restaurado: {$filename}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante la restauración: " . $e->getMessage());
            Log::error("Error en restauración de base de datos: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Ejecutar la restauración según el tipo de base de datos
     */
    private function executeRestore($config, $filepath)
    {
        $driver = $config['driver'];

        switch ($driver) {
            case 'mysql':
                return $this->restoreMySQL($config, $filepath);
            case 'pgsql':
                return $this->restorePostgreSQL($config, $filepath);
            case 'sqlite':
                return $this->restoreSQLite($config, $filepath);
            default:
                $this->error("Driver de base de datos no soportado: {$driver}");
                return false;
        }
    }

    /**
     * Restaurar MySQL
     */
    private function restoreMySQL($config, $filepath)
    {
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        $command = "mysql --host={$host} --port={$port} --user={$username}";

        if ($password) {
            $command .= " --password={$password}";
        }

        $command .= " {$database} < {$filepath}";

        $this->info("🔄 Ejecutando restauración MySQL para {$database}");

        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Restaurar PostgreSQL
     */
    private function restorePostgreSQL($config, $filepath)
    {
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        // Configurar variable de entorno para la contraseña
        putenv("PGPASSWORD={$password}");

        $command = "psql --host={$host} --port={$port} --username={$username}";
        $command .= " --dbname={$database} < {$filepath}";

        $this->info("🔄 Ejecutando restauración PostgreSQL para {$database}");

        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Restaurar SQLite
     */
    private function restoreSQLite($config, $filepath)
    {
        $database = $config['database'];

        // Crear respaldo del archivo actual si existe
        if (file_exists($database)) {
            $backupName = $database . '.backup.' . Carbon::now()->format('Y-m-d_H-i-s');
            copy($database, $backupName);
            $this->info("📋 Respaldo del archivo actual creado: " . basename($backupName));
        }

        $command = "sqlite3 {$database} < {$filepath}";

        $this->info("🔄 Ejecutando restauración SQLite para {$database}");

        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Descomprimir archivo de respaldo
     */
    private function decompressBackup($filepath)
    {
        $decompressedFile = str_replace('.gz', '', $filepath);

        $command = "gunzip -c {$filepath} > {$decompressedFile}";

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($decompressedFile)) {
            $this->info("✅ Archivo descomprimido: " . basename($decompressedFile));
            return $decompressedFile;
        }

        $this->error("❌ No se pudo descomprimir el archivo");
        return false;
    }

    /**
     * Formatear bytes en formato legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
