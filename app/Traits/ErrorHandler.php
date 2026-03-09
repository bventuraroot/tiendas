<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\DteError;

trait ErrorHandler
{
    /**
     * Maneja errores de manera consistente y proporciona información detallada
     *
     * @param \Exception $e
     * @param string $context
     * @param array $additionalData
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleError(\Exception $e, string $context = 'general', array $additionalData = [])
    {
        // Análisis específico para errores de array index
        $arrayErrorInfo = $this->analyzeArrayError($e->getMessage(), $e->getFile(), $e->getLine());

        // Log detallado del error para debugging
        $logData = array_merge([
            'context' => $context,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString(),
            'user_id' => auth()->id() ?? 'no_auth',
            'timestamp' => now()->toDateTimeString(),
            'array_error_analysis' => $arrayErrorInfo
        ], $additionalData);

        Log::error("Error en {$context}:", $logData);

        // Determinar el tipo de error para dar una respuesta más específica
        $errorType = $this->determineErrorType($e->getMessage());
        $errorDetails = $this->getErrorDetails($e->getMessage(), $errorType);

        // Guardar error en la base de datos si es un error relacionado con DTE o ventas
        $this->saveErrorToDatabase($e, $context, $errorType, $additionalData);

        // Rollback de transacción si está activa
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        // Si es un error de createdocument, intentar incluir información del DTE
        $dteResponse = null;
        if ($context === 'createdocument' && isset($additionalData['dte_response'])) {
            $dteResponse = $additionalData['dte_response'];
        }

        return response()->json([
            'res' => 0, // Para compatibilidad con el frontend
            'error' => 'Ha ocurrido un error en el sistema',
            'error_type' => $errorType,
            'message' => $errorDetails,
            'context' => $context,
            'dte_response' => $dteResponse,
            'array_error_details' => $arrayErrorInfo,
            'debug_info' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'timestamp' => now()->toDateTimeString()
            ],
            'additional_data' => $additionalData
        ], 500);
    }

    /**
     * Determina el tipo de error basado en el mensaje
     *
     * @param string $message
     * @return string
     */
    private function determineErrorType(string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, 'undefined array key') ||
            str_contains($message, 'array index') ||
            str_contains($message, 'offset') ||
            str_contains($message, 'does not exist')) {
            return 'array_index';
        } elseif (str_contains($message, 'sqlstate') || str_contains($message, 'database')) {
            return 'database';
        } elseif (str_contains($message, 'client_id') || str_contains($message, 'cliente')) {
            return 'client_missing';
        } elseif (str_contains($message, 'correlativo') || str_contains($message, 'documento')) {
            return 'correlativo';
        } elseif (str_contains($message, 'hacienda') || str_contains($message, 'mh') || str_contains($message, 'ministerio')) {
            return 'hacienda';
        } elseif (str_contains($message, 'validation') || str_contains($message, 'validación')) {
            return 'validation';
        } elseif (str_contains($message, 'permission') || str_contains($message, 'permiso')) {
            return 'permission';
        } elseif (str_contains($message, 'not found') || str_contains($message, 'no encontrado')) {
            return 'not_found';
        } elseif (str_contains($message, 'ticket') || str_contains($message, 'impresión')) {
            return 'ticket';
        } else {
            return 'general';
        }
    }

    /**
     * Obtiene detalles específicos del error basado en el tipo
     *
     * @param string $message
     * @param string $errorType
     * @return string
     */
    private function getErrorDetails(string $message, string $errorType): string
    {
        switch ($errorType) {
            case 'array_index':
                return 'Error de acceso a array: ' . $message;
            case 'database':
                return 'Error en la base de datos: ' . $message;
            case 'client_missing':
                return 'Error: La venta no tiene un cliente asignado correctamente';
            case 'correlativo':
                return 'Error con el correlativo del documento: ' . $message;
            case 'hacienda':
                return 'Error en comunicación con Ministerio de Hacienda: ' . $message;
            case 'validation':
                return 'Error de validación: ' . $message;
            case 'permission':
                return 'Error de permisos: ' . $message;
            case 'not_found':
                return 'Recurso no encontrado: ' . $message;
            case 'ticket':
                return 'Error generando ticket: ' . $message;
            default:
                return $message;
        }
    }

    /**
     * Analiza errores específicos de arrays para identificar la variable problemática
     *
     * @param string $errorMessage
     * @param string $file
     * @param int $line
     * @return array
     */
    private function analyzeArrayError(string $errorMessage, string $file, int $line): array
    {
        $analysis = [
            'is_array_error' => false,
            'problematic_variable' => null,
            'array_index' => null,
            'suggested_fix' => null,
            'context_info' => null
        ];

        // Detectar errores de array index
        if (str_contains($errorMessage, 'Undefined array key') ||
            str_contains($errorMessage, 'array index') ||
            str_contains($errorMessage, 'offset') ||
            str_contains($errorMessage, 'does not exist')) {

            $analysis['is_array_error'] = true;

            // Extraer información del mensaje de error
            if (preg_match('/Undefined array key (\d+)/', $errorMessage, $matches)) {
                $analysis['array_index'] = $matches[1];
            }

            if (preg_match('/offset (\d+)/', $errorMessage, $matches)) {
                $analysis['array_index'] = $matches[1];
            }

            // Leer el archivo para analizar la línea específica
            $fileContent = file($file);
            if (isset($fileContent[$line - 1])) {
                $problemLine = trim($fileContent[$line - 1]);
                $analysis['problematic_line'] = $problemLine;

                // Identificar la variable problemática
                if (preg_match('/\$(\w+)\[(\d+)\]/', $problemLine, $matches)) {
                    $analysis['problematic_variable'] = '$' . $matches[1];
                    $analysis['array_index'] = $matches[2];

                    // Buscar dónde se define esta variable
                    $variableName = $matches[1];
                    $definitionLine = $this->findVariableDefinition($fileContent, $variableName, $line);
                    if ($definitionLine) {
                        $analysis['variable_definition_line'] = $definitionLine;
                        $analysis['context_info'] = "Variable \${$variableName} definida en línea {$definitionLine}";
                    }

                    // Sugerir solución
                    $analysis['suggested_fix'] = "Verificar que \${$variableName} tenga datos antes de acceder al índice {$matches[2]}. " .
                                               "Agregar validación: if (!empty(\${$variableName}) && isset(\${$variableName}[{$matches[2]}]))";
                }
            }
        }

        return $analysis;
    }

    /**
     * Encuentra la línea donde se define una variable
     *
     * @param array $fileContent
     * @param string $variableName
     * @param int $currentLine
     * @return int|null
     */
    private function findVariableDefinition(array $fileContent, string $variableName, int $currentLine): ?int
    {
        // Buscar hacia atrás desde la línea actual
        for ($i = $currentLine - 2; $i >= 0; $i--) {
            $line = trim($fileContent[$i]);
            if (preg_match('/\$' . $variableName . '\s*=/', $line)) {
                return $i + 1; // +1 porque los arrays empiezan en 0
            }
        }
        return null;
    }

    /**
     * Valida que un array tenga datos antes de acceder a un índice específico
     *
     * @param mixed $array
     * @param int $index
     * @param string $variableName
     * @param string $context
     * @return bool
     * @throws \Exception
     */
    protected function validateArrayAccess($array, int $index, string $variableName, string $context = 'general'): bool
    {
        if (empty($array)) {
            throw new \Exception("Array \${$variableName} está vacío en contexto: {$context}");
        }

        if (!is_array($array) && !is_object($array)) {
            throw new \Exception("Variable \${$variableName} no es un array en contexto: {$context}");
        }

        if (!isset($array[$index])) {
            $count = is_array($array) ? count($array) : (method_exists($array, 'count') ? $array->count() : 'desconocido');
            throw new \Exception("Índice {$index} no existe en \${$variableName}. Array tiene {$count} elementos en contexto: {$context}");
        }

        return true;
    }

    /**
     * Obtiene un valor seguro de un array con validación
     *
     * @param mixed $array
     * @param int $index
     * @param string $variableName
     * @param string $context
     * @param mixed $default
     * @return mixed
     */
    protected function getSafeArrayValue($array, int $index, string $variableName, string $context = 'general', $default = null)
    {
        try {
            $this->validateArrayAccess($array, $index, $variableName, $context);
            return $array[$index];
        } catch (\Exception $e) {
            Log::warning("Acceso inseguro a array: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Maneja errores específicos de tickets
     *
     * @param \Exception $e
     * @param int $saleId
     * @param int $exit
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleTicketError(\Exception $e, int $saleId, int $exit = 1)
    {
        $this->handleError($e, 'ticket_generation', ['sale_id' => $saleId]);

        return response()->json([
            "res" => $exit,
            "ticket_error" => "No se pudo generar el ticket automáticamente",
            "ticket_error_details" => $e->getMessage(),
            "debug_info" => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'sale_id' => $saleId
            ]
        ]);
    }

    /**
     * Guarda el error en la base de datos para seguimiento y análisis
     *
     * @param \Exception $e
     * @param string $context
     * @param string $errorType
     * @param array $additionalData
     * @return void
     */
    protected function saveErrorToDatabase(\Exception $e, string $context, string $errorType, array $additionalData = []): void
    {
        try {
            // Solo guardar errores relacionados con DTE, ventas o sistema
            if (!in_array($context, ['createdocument', 'process-sale', 'ticket_generation', 'hacienda', 'dte']) &&
                !in_array($errorType, ['hacienda', 'correlativo', 'client_missing', 'ticket', 'system'])) {
                return;
            }

            $dteErrorData = [
                'dte_id' => $additionalData['dte_id'] ?? null,
                'sale_id' => $additionalData['sale_id'] ?? null,
                'company_id' => $additionalData['company_id'] ?? auth()->user()->company_id ?? null,
                'tipo_error' => $this->mapErrorTypeToDteErrorType($errorType),
                'codigo_error' => $this->generateErrorCode($e, $context),
                'descripcion' => $e->getMessage(),
                'datos_adicionales' => [
                    'context' => $context,
                    'error_type' => $errorType,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => auth()->id(),
                    'timestamp' => now()->toDateTimeString(),
                    'additional_data' => $additionalData
                ]
            ];

            DteError::create($dteErrorData);

            Log::info("Error guardado en base de datos", [
                'context' => $context,
                'error_type' => $errorType,
                'sale_id' => $additionalData['sale_id'] ?? null
            ]);

        } catch (\Exception $saveException) {
            // No queremos que un error al guardar el error cause más problemas
            Log::error("Error al guardar error en base de datos: " . $saveException->getMessage(), [
                'original_error' => $e->getMessage(),
                'context' => $context
            ]);
        }
    }

    /**
     * Mapea el tipo de error del sistema al tipo de error DTE
     *
     * @param string $errorType
     * @return string
     */
    private function mapErrorTypeToDteErrorType(string $errorType): string
    {
        return match($errorType) {
            'hacienda' => 'hacienda',
            'correlativo' => 'validacion',
            'client_missing' => 'validacion',
            'ticket' => 'sistema',
            'database' => 'sistema',
            'validation' => 'validacion',
            'permission' => 'sistema',
            'not_found' => 'datos',
            'array_index' => 'sistema',
            default => 'sistema'
        };
    }

    /**
     * Genera un código de error único basado en el contexto y tipo
     *
     * @param \Exception $e
     * @param string $context
     * @return string
     */
    private function generateErrorCode(\Exception $e, string $context): string
    {
        $contextCode = strtoupper(substr($context, 0, 3));
        $errorClass = class_basename($e);
        $timestamp = now()->format('YmdHis');

        return "{$contextCode}_{$errorClass}_{$timestamp}";
    }
}
