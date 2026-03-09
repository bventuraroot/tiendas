<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class ListBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:list
                            {--path=backups : Directorio donde buscar los respaldos}
                            {--format=table : Formato de salida (table, json, csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listar todos los respaldos de base de datos disponibles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $backupPath = $this->option('path');
        $fullPath = storage_path("app/{$backupPath}");

        if (!file_exists($fullPath)) {
            $this->error("❌ Directorio de respaldos no encontrado: {$fullPath}");
            return 1;
        }

        $files = glob($fullPath . '/backup_*.sql*');

        if (empty($files)) {
            $this->info("📁 No se encontraron respaldos en: {$fullPath}");
            return 0;
        }

        // Ordenar archivos por fecha de modificación (más recientes primero)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $backups = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $modified = Carbon::createFromTimestamp(filemtime($file));

            // Extraer información del nombre del archivo
            $info = $this->parseBackupFilename($filename);

            $backups[] = [
                'filename' => $filename,
                'size' => $this->formatBytes($size),
                'size_bytes' => $size,
                'modified' => $modified->format('Y-m-d H:i:s'),
                'age' => $modified->diffForHumans(),
                'compressed' => pathinfo($file, PATHINFO_EXTENSION) === 'gz',
                'database' => $info['database'] ?? 'unknown',
                'date' => $info['date'] ?? 'unknown',
                'full_path' => $file
            ];
        }

        $format = $this->option('format');

        switch ($format) {
            case 'json':
                $this->output->write(json_encode($backups, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->outputCsv($backups);
                break;
            default:
                $this->outputTable($backups);
        }

        $this->info("📊 Total de respaldos encontrados: " . count($backups));

        return 0;
    }

    /**
     * Mostrar tabla de respaldos
     */
    private function outputTable($backups)
    {
        $headers = ['Archivo', 'Base de Datos', 'Tamaño', 'Fecha', 'Edad', 'Comprimido'];

        $rows = [];
        foreach ($backups as $backup) {
            $rows[] = [
                $backup['filename'],
                $backup['database'],
                $backup['size'],
                $backup['modified'],
                $backup['age'],
                $backup['compressed'] ? '✅' : '❌'
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Mostrar CSV de respaldos
     */
    private function outputCsv($backups)
    {
        $headers = ['filename', 'database', 'size', 'modified', 'age', 'compressed'];

        // Imprimir headers
        $this->output->write(implode(',', $headers) . "\n");

        // Imprimir datos
        foreach ($backups as $backup) {
            $row = [
                $backup['filename'],
                $backup['database'],
                $backup['size'],
                $backup['modified'],
                $backup['age'],
                $backup['compressed'] ? 'yes' : 'no'
            ];
            $this->output->write(implode(',', $row) . "\n");
        }
    }

    /**
     * Parsear información del nombre del archivo de respaldo
     */
    private function parseBackupFilename($filename)
    {
        // Formato esperado: backup_{database}_{date}_{time}.sql[.gz]
        $pattern = '/^backup_([^_]+)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql(\.gz)?$/';

        if (preg_match($pattern, $filename, $matches)) {
            return [
                'database' => $matches[1],
                'date' => $matches[2]
            ];
        }

        return [];
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
