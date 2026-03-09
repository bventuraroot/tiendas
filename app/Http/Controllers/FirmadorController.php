<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Ambiente;
use Exception;

class FirmadorController extends Controller
{
    public function __construct()
    {
        // Middleware de permisos
        $this->middleware('permission:firmador.test')->only(['test']);
        $this->middleware('permission:firmador.server-info')->only(['serverInfo']);
        $this->middleware('permission:firmador.ambientes')->only(['ambientes']);
        $this->middleware('permission:firmador.test-connection')->only(['testConnection']);
        $this->middleware('permission:firmador.test-firma')->only(['testFirma']);
        $this->middleware('permission:firmador.test-network')->only(['testNetwork']);
    }

    /**
     * Mostrar la vista de prueba del firmador
     */
    public function test()
    {
        // Obtener la URL del firmador actual
        $firmadorUrl = $this->getCurrentFirmadorUrl();

        return view('firmador.test', compact('firmadorUrl'));
    }

    /**
     * Obtener información del servidor
     */
    public function serverInfo(): JsonResponse
    {
        try {
            $serverInfo = [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'curl_version' => curl_version()['version'] ?? 'Not available',
                'allow_url_fopen' => ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled',
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
            ];

            return response()->json([
                'success' => true,
                'server_info' => $serverInfo
            ]);
        } catch (Exception $e) {
            Log::error('Error getting server info: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de ambientes disponibles
     */
    public function ambientes(): JsonResponse
    {
        try {
            $ambientes = Ambiente::select('cod', 'description', 'url_firmador')->get();
            $currentUrl = $this->getCurrentFirmadorUrl();

            return response()->json([
                'success' => true,
                'ambientes' => $ambientes,
                'current_url' => $currentUrl
            ]);
        } catch (Exception $e) {
            Log::error('Error getting ambientes: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ambientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Probar conexión básica al firmador
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $timeout = $request->input('timeout', 30);
            $firmadorUrl = $this->getCurrentFirmadorUrl();

            if (!$firmadorUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró URL del firmador configurada'
                ], 400);
            }

            $startTime = microtime(true);

            // Hacer una petición GET simple para probar conectividad
            $response = Http::timeout($timeout)
                ->get($firmadorUrl . '/health');

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'status_code' => $response->status(),
                    'response_time_ms' => $responseTime,
                    'url' => $firmadorUrl,
                    'message' => 'Conexión exitosa al firmador'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status_code' => $response->status(),
                    'response_time_ms' => $responseTime,
                    'url' => $firmadorUrl,
                    'message' => 'Error en la respuesta del firmador: ' . $response->body()
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error testing connection: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'url' => $firmadorUrl ?? 'No configurada'
            ], 500);
        }
    }

    /**
     * Probar funcionalidad de firma
     */
    public function testFirma(Request $request): JsonResponse
    {
        try {
            $timeout = $request->input('timeout', 30);
            $firmadorUrl = $this->getCurrentFirmadorUrl();

            if (!$firmadorUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró URL del firmador configurada'
                ], 400);
            }

            // Crear un documento de prueba simple
            $testDocument = [
                'nit' => '12345678-9',
                'activo' => true,
                'passwordPri' => 'test_password',
                'dteJson' => [
                    'identificacion' => [
                        'version' => 3,
                        'ambiente' => '00',
                        'tipoDte' => '01',
                        'numeroControl' => 'TEST-' . time(),
                        'codigoGeneracion' => 'TEST-' . uniqid(),
                        'tipoModelo' => 1,
                        'tipoOperacion' => 1,
                        'tipoContingencia' => null,
                        'motivoContin' => null,
                        'tipoMoneda' => 'USD'
                    ],
                    'documentoRelacionado' => null,
                    'emisor' => [
                        'nit' => '12345678-9',
                        'nrc' => '123456',
                        'nombre' => 'Empresa de Prueba',
                        'codActividad' => '62010',
                        'descActividad' => 'Desarrollo de software',
                        'nombreComercial' => 'Empresa Test',
                        'tipoEstablecimiento' => '01',
                        'direccion' => [
                            'departamento' => '06',
                            'municipio' => '0601',
                            'complemento' => 'Centro de San Salvador'
                        ],
                        'telefono' => '2222-2222',
                        'email' => 'test@empresa.com'
                    ],
                    'receptor' => [
                        'nit' => '98765432-1',
                        'nrc' => '654321',
                        'nombre' => 'Cliente de Prueba',
                        'codActividad' => '62010',
                        'descActividad' => 'Desarrollo de software',
                        'direccion' => [
                            'departamento' => '06',
                            'municipio' => '0601',
                            'complemento' => 'Centro de San Salvador'
                        ],
                        'telefono' => '3333-3333',
                        'email' => 'cliente@test.com'
                    ],
                    'otrosDocumentos' => null,
                    'ventaTercero' => null,
                    'cuerpoDocumento' => [
                        [
                            'numItem' => 1,
                            'tipoItem' => 2,
                            'numeroDocumento' => null,
                            'codigo' => 'TEST001',
                            'codTributo' => null,
                            'descripcion' => 'Producto de prueba',
                            'cantidad' => 1,
                            'uniMedida' => 59,
                            'precioUni' => 100.00,
                            'montoDescu' => 0.00,
                            'ventaNoSuj' => 0.00,
                            'ventaExenta' => 0.00,
                            'ventaGravada' => 100.00,
                            'tributos' => null,
                            'psv' => 0.00,
                            'noGravado' => 0.00
                        ]
                    ],
                    'resumen' => [
                        'totalNoSuj' => 0.00,
                        'totalExenta' => 0.00,
                        'totalGravada' => 100.00,
                        'subTotalVentas' => 100.00,
                        'descuNoSuj' => 0.00,
                        'descuExenta' => 0.00,
                        'descuGravada' => 0.00,
                        'porcentajeDescuento' => 0.00,
                        'totalDescu' => 0.00,
                        'tributos' => [
                            [
                                'codigo' => '20',
                                'descripcion' => 'Impuesto al Valor Agregado 13%',
                                'valor' => 13.00
                            ]
                        ],
                        'subTotal' => 100.00,
                        'ivaRete1' => 0.00,
                        'reteRenta' => 0.00,
                        'montoTotalOperacion' => 113.00,
                        'totalNoGravado' => 0.00,
                        'totalPagar' => 113.00,
                        'totalLetras' => 'CIENTO TRECE DOLARES',
                        'totalIva' => 13.00,
                        'saldoFavor' => 0.00,
                        'condicionOperacion' => 1,
                        'pagos' => [
                            [
                                'codigo' => '01',
                                'montoPago' => 113.00,
                                'referencia' => null,
                                'plazo' => null,
                                'periodo' => null
                            ]
                        ],
                        'numPagoElectronico' => null
                    ],
                    'extension' => null,
                    'apendice' => null
                ]
            ];

            $startTime = microtime(true);

            // Intentar firmar el documento de prueba
            $response = Http::timeout($timeout)
                ->accept('application/json')
                ->post($firmadorUrl, $testDocument);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            if ($response->successful()) {
                $responseData = $response->json();

                return response()->json([
                    'success' => true,
                    'status_code' => $response->status(),
                    'response_time_ms' => $responseTime,
                    'url' => $firmadorUrl,
                    'message' => 'Prueba de firma exitosa',
                    'response_data' => $responseData
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status_code' => $response->status(),
                    'response_time_ms' => $responseTime,
                    'url' => $firmadorUrl,
                    'message' => 'Error en la firma: ' . $response->body(),
                    'response_data' => $response->json()
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error testing firma: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error en la prueba de firma: ' . $e->getMessage(),
                'url' => $firmadorUrl ?? 'No configurada'
            ], 500);
        }
    }

    /**
     * Ejecutar diagnóstico de red
     */
    public function testNetwork(): JsonResponse
    {
        try {
            $firmadorUrl = $this->getCurrentFirmadorUrl();

            if (!$firmadorUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró URL del firmador configurada'
                ], 400);
            }

            $tests = [];
            $passedTests = 0;
            $totalTests = 0;

            // Test 1: Resolución DNS
            $totalTests++;
            try {
                $host = parse_url($firmadorUrl, PHP_URL_HOST);
                $ip = gethostbyname($host);

                if ($ip !== $host) {
                    $tests['dns_resolution'] = [
                        'success' => true,
                        'message' => "DNS resuelto correctamente: {$host} -> {$ip}"
                    ];
                    $passedTests++;
                } else {
                    $tests['dns_resolution'] = [
                        'success' => false,
                        'message' => "No se pudo resolver DNS para: {$host}"
                    ];
                }
            } catch (Exception $e) {
                $tests['dns_resolution'] = [
                    'success' => false,
                    'message' => "Error en resolución DNS: " . $e->getMessage()
                ];
            }

            // Test 2: Conectividad TCP
            $totalTests++;
            try {
                $host = parse_url($firmadorUrl, PHP_URL_HOST);
                $port = parse_url($firmadorUrl, PHP_URL_PORT) ?: 80;

                $connection = @fsockopen($host, $port, $errno, $errstr, 10);

                if ($connection) {
                    fclose($connection);
                    $tests['tcp_connection'] = [
                        'success' => true,
                        'message' => "Conexión TCP exitosa a {$host}:{$port}"
                    ];
                    $passedTests++;
                } else {
                    $tests['tcp_connection'] = [
                        'success' => false,
                        'message' => "No se pudo conectar TCP a {$host}:{$port} - {$errstr}"
                    ];
                }
            } catch (Exception $e) {
                $tests['tcp_connection'] = [
                    'success' => false,
                    'message' => "Error en conexión TCP: " . $e->getMessage()
                ];
            }

            // Test 3: HTTP Response
            $totalTests++;
            try {
                $response = Http::timeout(10)->get($firmadorUrl);

                if ($response->successful()) {
                    $tests['http_response'] = [
                        'success' => true,
                        'message' => "Respuesta HTTP exitosa: {$response->status()}"
                    ];
                    $passedTests++;
                } else {
                    $tests['http_response'] = [
                        'success' => false,
                        'message' => "Error HTTP: {$response->status()} - {$response->body()}"
                    ];
                }
            } catch (Exception $e) {
                $tests['http_response'] = [
                    'success' => false,
                    'message' => "Error en respuesta HTTP: " . $e->getMessage()
                ];
            }

            // Test 4: SSL/TLS (si es HTTPS)
            if (strpos($firmadorUrl, 'https://') === 0) {
                $totalTests++;
                try {
                    $context = stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ]
                    ]);

                    $host = parse_url($firmadorUrl, PHP_URL_HOST);
                    $port = parse_url($firmadorUrl, PHP_URL_PORT) ?: 443;

                    $connection = @stream_socket_client(
                        "ssl://{$host}:{$port}",
                        $errno,
                        $errstr,
                        10,
                        STREAM_CLIENT_CONNECT,
                        $context
                    );

                    if ($connection) {
                        fclose($connection);
                        $tests['ssl_connection'] = [
                            'success' => true,
                            'message' => "Conexión SSL/TLS exitosa a {$host}:{$port}"
                        ];
                        $passedTests++;
                    } else {
                        $tests['ssl_connection'] = [
                            'success' => false,
                            'message' => "Error en conexión SSL/TLS: {$errstr}"
                        ];
                    }
                } catch (Exception $e) {
                    $tests['ssl_connection'] = [
                        'success' => false,
                        'message' => "Error en SSL/TLS: " . $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'tests' => $tests,
                'summary' => [
                    'total_tests' => $totalTests,
                    'passed_tests' => $passedTests,
                    'failed_tests' => $totalTests - $passedTests
                ],
                'url' => $firmadorUrl
            ]);

        } catch (Exception $e) {
            Log::error('Error in network test: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error en el diagnóstico de red: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener la URL actual del firmador
     */
    private function getCurrentFirmadorUrl(): ?string
    {
        try {
            // Intentar obtener de la sesión del usuario
            $user = auth()->user();
            if ($user && $user->company_id) {
                $company = Company::find($user->company_id);
                if ($company && $company->url_firmador) {
                    return $company->url_firmador;
                }
            }

            // Intentar obtener del ambiente actual
            $ambiente = Ambiente::where('cod', '01')->first(); // Ambiente de producción por defecto
            if ($ambiente && $ambiente->url_firmador) {
                return $ambiente->url_firmador;
            }

            // URL por defecto si no se encuentra configuración
            return 'http://143.198.63.171:8113/firmardocumento/';

        } catch (Exception $e) {
            Log::error('Error getting firmador URL: ' . $e->getMessage());
            return 'http://143.198.63.171:8113/firmardocumento/';
        }
    }
}
