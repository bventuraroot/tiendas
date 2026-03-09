<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    public function __construct()
    {
        // Middleware de permisos - temporalmente comentado para pruebas
        // $this->middleware('permission:backups.index')->only(['index']);
        // $this->middleware('permission:backups.create')->only(['create']);
        // $this->middleware('permission:backups.download')->only(['download']);
        // $this->middleware('permission:backups.destroy')->only(['destroy']);
        // $this->middleware('permission:backups.restore')->only(['restore']);
        // $this->middleware('permission:backups.list')->only(['getBackups']);
        // $this->middleware('permission:backups.stats')->only(['getStats']);

        // Solo requerir autenticación por ahora
        $this->middleware('auth');
    }

    /**
     * Mostrar la página principal de respaldos
     */
    public function index()
    {
        try {
            $backups = $this->getBackupsList();
            $stats = $this->getBackupStats();

            Log::info('Cargando página de respaldos', [
                'backups_count' => count($backups),
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar página de respaldos: ' . $e->getMessage());

            // Valores por defecto en caso de error
            $backups = [];
            $stats = [
                'total_backups' => 0,
                'total_size' => '0 B',
                'total_size_bytes' => 0,
                'oldest_backup' => null,
                'newest_backup' => null,
                'compressed_count' => 0,
                'uncompressed_count' => 0
            ];
        }

        return view('backups.index', compact('backups', 'stats'));
    }

    /**
     * Crear un nuevo respaldo
     */
    public function create(Request $request)
    {
        try {
            Log::info('=== INICIANDO CREACIÓN DE RESPALDO ===', [
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'timestamp' => now()
            ]);

            $compress = $request->has('compress');
            $keep = $request->input('keep', 7);

            Log::info('=== PARÁMETROS DE COMPRESIÓN ===', [
                'compress_has' => $request->has('compress'),
                'compress_input' => $request->input('compress'),
                'compress_all_data' => $request->all(),
                'keep' => $keep,
                'compress_final' => $compress
            ]);

            // Crear directorio de respaldos si no existe
            $backupPath = storage_path('app/backups');
            Log::info('Ruta de respaldos: ' . $backupPath);

            if (!file_exists($backupPath)) {
                Log::info('Creando directorio de respaldos...');
                $created = mkdir($backupPath, 0755, true);
                Log::info('Directorio creado: ' . ($created ? 'SÍ' : 'NO'));
            } else {
                Log::info('Directorio ya existe');
            }

            // Verificar permisos del directorio
            $writable = is_writable($backupPath);
            Log::info('Directorio escribible: ' . ($writable ? 'SÍ' : 'NO'));

            // Crear respaldo real de la base de datos
            $timestamp = now()->format('Y-m-d_H-i-s');
            $databaseName = config('database.connections.mysql.database');
            $filename = 'backup_' . $databaseName . '_' . $timestamp . '.sql';
            $filepath = $backupPath . '/' . $filename;

            Log::info('Creando respaldo real de la base de datos: ' . $filepath);

            // Configuración de la base de datos
            $dbHost = config('database.connections.mysql.host');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbName = config('database.connections.mysql.database');

            // Comando mysqldump
            $command = "mysqldump -h {$dbHost} -u {$dbUser} -p{$dbPass} {$dbName} > {$filepath}";

            Log::info('Ejecutando comando: ' . str_replace($dbPass, '***', $command));

            // Ejecutar comando
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            Log::info('Comando ejecutado. Código de retorno: ' . $returnCode);
            Log::info('Salida del comando: ' . implode("\n", $output));

            if ($returnCode !== 0) {
                throw new \Exception('Error al ejecutar mysqldump: ' . implode("\n", $output));
            }

            // Verificar que el archivo se creó
            if (!file_exists($filepath)) {
                throw new \Exception('El archivo de respaldo no se creó correctamente');
            }

            $fileSize = filesize($filepath);
            Log::info('Respaldo creado exitosamente. Tamaño: ' . $fileSize . ' bytes');

            // Comprimir si se solicitó
            Log::info('=== VERIFICANDO COMPRESIÓN ===', [
                'compress_value' => $compress,
                'compress_type' => gettype($compress),
                'will_compress' => $compress ? 'SÍ' : 'NO'
            ]);

            if ($compress) {
                Log::info('INICIANDO COMPRESIÓN...');
                $compressedFilepath = $filepath . '.gz';
                $compressed = gzopen($compressedFilepath, 'w9');
                gzwrite($compressed, file_get_contents($filepath));
                gzclose($compressed);

                // Eliminar archivo original
                unlink($filepath);
                $filepath = $compressedFilepath;
                $filename = basename($compressedFilepath);

                Log::info('Archivo comprimido: ' . $compressedFilepath);
            } else {
                Log::info('NO SE COMPRIMIRÁ - Archivo se mantiene como .sql');
            }

            // Limpiar respaldos antiguos si se especificó
            if ($keep > 0) {
                $this->cleanOldBackups($backupPath, $keep);
            }

            return response()->json([
                'success' => true,
                'message' => 'Respaldo de base de datos creado exitosamente',
                'output' => 'Archivo creado: ' . $filename,
                'info' => 'Respaldo real de la base de datos ' . $databaseName,
                'debug' => [
                    'backup_path' => $backupPath,
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'file_size' => filesize($filepath),
                    'compressed' => $compress,
                    'database' => $databaseName
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('=== ERROR AL CREAR RESPALDO ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el respaldo: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    /**
     * Descargar un respaldo
     */
    public function download($filename)
    {
        try {
            Log::info('=== INICIANDO DESCARGA DE RESPALDO ===', [
                'filename' => $filename,
                'user_id' => auth()->id(),
                'timestamp' => now()
            ]);

            $backupPath = storage_path('app/backups/' . $filename);

            Log::info('Ruta de descarga: ' . $backupPath);

            if (!file_exists($backupPath)) {
                Log::warning('Archivo de respaldo no encontrado: ' . $backupPath);
                return redirect()->back()->with('error', 'Archivo de respaldo no encontrado');
            }

            // Verificar que es un archivo de respaldo válido
            if (!preg_match('/^backup_.*\.sql(\.gz)?$/', $filename)) {
                Log::warning('Intento de descarga de archivo no válido: ' . $filename);
                return redirect()->back()->with('error', 'Archivo no válido para descargar');
            }

            $fileSize = filesize($backupPath);
            Log::info('Descarga iniciada exitosamente', [
                'filename' => $filename,
                'size' => $fileSize,
                'path' => $backupPath
            ]);

            return response()->download($backupPath, $filename);

        } catch (\Exception $e) {
            Log::error('=== ERROR AL DESCARGAR RESPALDO ===', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return redirect()->back()->with('error', 'Error al descargar el respaldo: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un respaldo
     */
    public function destroy($filename)
    {
        try {
            $backupPath = storage_path('app/backups/' . $filename);

            if (!file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo de respaldo no encontrado'
                ], 404);
            }

            // Verificar que es un archivo de respaldo válido
            if (!preg_match('/^backup_.*\.sql(\.gz)?$/', $filename)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no válido para eliminar'
                ], 400);
            }

            unlink($backupPath);

            Log::info('Respaldo eliminado desde interfaz web: ' . $filename);

            return response()->json([
                'success' => true,
                'message' => 'Respaldo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar respaldo: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el respaldo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar un respaldo
     */
    public function restore(Request $request, $filename)
    {
        try {
            $backupPath = storage_path('app/backups/' . $filename);

            if (!file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo de respaldo no encontrado'
                ], 404);
            }

            // Ejecutar comando de restauración
            $exitCode = Artisan::call('backup:restore', [
                'file' => $filename,
                '--force' => true
            ]);

            if ($exitCode === 0) {
                $output = Artisan::output();
                Log::info('Respaldo restaurado desde interfaz web: ' . $filename);

                return response()->json([
                    'success' => true,
                    'message' => 'Respaldo restaurado exitosamente',
                    'output' => $output
                ]);
            } else {
                throw new \Exception('Error al restaurar el respaldo');
            }

        } catch (\Exception $e) {
            Log::error('Error al restaurar respaldo: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el respaldo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de respaldos
     */
    public function getBackups()
    {
        $backups = $this->getBackupsList();

        return response()->json([
            'success' => true,
            'backups' => $backups
        ]);
    }

    /**
     * Obtener estadísticas de respaldos
     */
    public function getStats()
    {
        try {
            $stats = $this->getBackupStats();

            Log::info('Obteniendo estadísticas de respaldos', [
                'stats' => $stats
            ]);

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'stats' => [
                    'total_backups' => 0,
                    'total_size' => '0 B',
                    'total_size_bytes' => 0,
                    'oldest_backup' => null,
                    'newest_backup' => null,
                    'compressed_count' => 0,
                    'uncompressed_count' => 0
                ]
            ]);
        }
    }

    /**
     * Método de debug para verificar el estado del sistema
     */
    public function debug()
    {
        $backupPath = storage_path('app/backups');
        $files = glob($backupPath . '/backup_*.sql*');
        $testFiles = glob($backupPath . '/backup_test_*.sql*');
        $allFiles = array_merge($files, $testFiles);

        $debug = [
            'backup_path' => $backupPath,
            'path_exists' => file_exists($backupPath),
            'path_writable' => is_writable($backupPath),
            'files_pattern' => $files,
            'test_files_pattern' => $testFiles,
            'all_files' => $allFiles,
            'backups_list' => $this->getBackupsList(),
            'backup_stats' => $this->getBackupStats(),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'user' => get_current_user(),
                'temp_dir' => sys_get_temp_dir(),
                'storage_path' => storage_path(),
                'base_path' => base_path()
            ]
        ];

        return response()->json($debug);
    }

    /**
     * Método de prueba simple para crear un archivo
     */
    public function test()
    {
        try {
            $backupPath = storage_path('app/backups');

            // Crear directorio si no existe
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            // Crear archivo de prueba
            $testFile = $backupPath . '/test_' . time() . '.txt';
            $content = "Archivo de prueba creado en: " . now() . "\n";
            $content .= "Usuario: " . (auth()->user()->name ?? 'No autenticado') . "\n";
            $content .= "Servidor: cPanel\n";

            $result = file_put_contents($testFile, $content);

            return response()->json([
                'success' => $result !== false,
                'message' => $result !== false ? 'Archivo de prueba creado' : 'Error al crear archivo',
                'file' => $testFile,
                'size' => $result,
                'exists' => file_exists($testFile),
                'path_writable' => is_writable($backupPath),
                'debug' => [
                    'backup_path' => $backupPath,
                    'test_file' => $testFile,
                    'result' => $result
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Obtener lista de respaldos con información detallada
     */
    private function getBackupsList()
    {
        $backupPath = storage_path('app/backups');

        Log::info('=== OBTENIENDO LISTA DE RESPALDOS ===', [
            'backup_path' => $backupPath,
            'path_exists' => file_exists($backupPath)
        ]);

        if (!file_exists($backupPath)) {
            Log::info('Directorio de respaldos no existe');
            return [];
        }

        $files = glob($backupPath . '/backup_*.sql*');
        Log::info('Archivos backup_*.sql* encontrados: ' . json_encode($files));

        Log::info('Total de archivos encontrados: ' . count($files));

        if (empty($files)) {
            Log::info('No se encontraron archivos de respaldo');
            return [];
        }

        // Ordenar por fecha de modificación (más recientes primero)
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
                'download_url' => route('backups.download', $filename),
                'delete_url' => route('backups.destroy', $filename),
                'restore_url' => route('backups.restore', $filename)
            ];
        }

        return $backups;
    }

    /**
     * Obtener estadísticas de respaldos
     */
    private function getBackupStats()
    {
        $backups = $this->getBackupsList();

        if (empty($backups)) {
            return [
                'total_backups' => 0,
                'total_size' => '0 B',
                'total_size_bytes' => 0,
                'oldest_backup' => null,
                'newest_backup' => null,
                'compressed_count' => 0,
                'uncompressed_count' => 0
            ];
        }

        $totalSize = array_sum(array_column($backups, 'size_bytes'));
        $compressedCount = count(array_filter($backups, fn($b) => $b['compressed']));
        $uncompressedCount = count($backups) - $compressedCount;

        return [
            'total_backups' => count($backups),
            'total_size' => $this->formatBytes($totalSize),
            'total_size_bytes' => $totalSize,
            'oldest_backup' => end($backups)['modified'],
            'newest_backup' => $backups[0]['modified'],
            'compressed_count' => $compressedCount,
            'uncompressed_count' => $uncompressedCount
        ];
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

        return [
            'database' => 'unknown',
            'date' => 'unknown'
        ];
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

    /**
     * Limpiar respaldos antiguos manteniendo solo los más recientes
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
                Log::info('Archivo antiguo eliminado: ' . basename($file));
            }
        }
    }
}
