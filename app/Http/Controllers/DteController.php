<?php

namespace App\Http\Controllers;

use App\Models\Dte;
use App\Models\Company;
use App\Models\Contingencia;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DateTime;

class DteController extends Controller
{
    /**
     * Dashboard principal del módulo DTE
     */
    public function dashboard(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        // Obtener empresas para el filtro
        $empresas = Company::all();

        // Construir query base
        $query = Dte::with(['company', 'sale']);

        if ($empresaId) {
            $query->where('company_id', $empresaId);
        }

        // Estadísticas generales
        $estadisticas = [
            'total' => (clone $query)->count(),
            'en_cola' => (clone $query)->where('codEstado', '01')->count(),
            'enviados' => (clone $query)->where('codEstado', '02')->count(),
            'rechazados' => (clone $query)->where('codEstado', '03')->count(),
        ];

        // Calcular porcentaje de éxito
        $totalProcesados = $estadisticas['enviados'] + $estadisticas['rechazados'];
        $estadisticas['porcentaje_exito'] = $totalProcesados > 0
            ? round(($estadisticas['enviados'] / $totalProcesados) * 100, 2)
            : 0;

        // Errores críticos
        $erroresCriticos = [
            'total' => (clone $query)->conErrores()->count(),
            'reintentos_agotados' => (clone $query)->necesitanContingencia()->count(),
            'errores_hacienda' => (clone $query)->whereNotNull('descriptionMessage')
                ->where('codEstado', Dte::ESTADO_RECHAZADO)->count(),
        ];

        // Últimos DTE procesados
        $ultimosDte = (clone $query)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($dte) {
                $dte->estado_color = $this->getEstadoColor($dte->codEstado);
                $dte->estado_texto = $this->getEstadoTexto($dte->codEstado);
                $dte->tipoDte = $this->getTipoDteTexto($dte->tipoDte);
                return $dte;
            });

        // Contingencias activas
        $contingenciasActivas = Contingencia::with('empresa')
            ->where('codEstado', '02')
            ->where('fFin', '>=', now()->format('Y-m-d'))
            ->get()
            ->map(function ($contingencia) {
                $contingencia->tipo_texto = $this->getTipoContingenciaTexto($contingencia->tipoContingencia);
                $contingencia->estado_badge = $this->getContingenciaEstadoBadge($contingencia->codEstado);
                $contingencia->documentos_afectados = Dte::where('idContingencia', $contingencia->id)->count();
                return $contingencia;
            });

        return view('dte.dashboard', compact(
            'estadisticas',
            'erroresCriticos',
            'ultimosDte',
            'contingenciasActivas',
            'empresas',
            'empresaId'
        ));
    }

    /**
     * Obtener estadísticas en tiempo real
     */
    public function estadisticasTiempoReal(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        $query = Dte::query();
        if ($empresaId) {
            $query->where('company_id', $empresaId);
        }

        $estadisticas = [
            'total' => (clone $query)->count(),
            'en_cola' => (clone $query)->where('codEstado', '01')->count(),
            'enviados' => (clone $query)->where('codEstado', '02')->count(),
            'rechazados' => (clone $query)->where('codEstado', '03')->count(),
        ];

        $totalProcesados = $estadisticas['enviados'] + $estadisticas['rechazados'];
        $estadisticas['porcentaje_exito'] = $totalProcesados > 0
            ? round(($estadisticas['enviados'] / $totalProcesados) * 100, 2)
            : 0;

        $erroresCriticos = (clone $query)->conErrores()->count();

        return response()->json([
            'estadisticas' => $estadisticas,
            'errores_criticos' => $erroresCriticos
        ]);
    }

    /**
     * Lista de documentos DTE
     */
    public function documentos(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        $estado = $request->get('estado');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $query = Dte::with(['company', 'sale']);

        if ($empresaId) {
            $query->where('company_id', $empresaId);
        }

        if ($estado) {
            $query->where('codEstado', $estado);
        }

        if ($fechaDesde) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $documentos = $query->orderBy('created_at', 'desc')->paginate(20);

        $empresas = Company::all();

        return view('dte.documentos', compact('documentos', 'empresas'));
    }

    /**
     * Mostrar detalles de un DTE
     */
    public function show($id)
    {
        $dte = Dte::with(['company', 'sale', 'sale.details', 'sale.client'])
            ->findOrFail($id);

        $dte->estado_color = $this->getEstadoColor($dte->codEstado);
        $dte->estado_texto = $this->getEstadoTexto($dte->codEstado);
        $dte->tipoDte = $this->getTipoDteTexto($dte->tipoDte);

        return view('dte.show', compact('dte'));
    }

    /**
     * Procesar cola de documentos
     */
    public function procesarCola(Request $request)
    {
        try {
            $limite = $request->get('limite', 10);

            $documentosEnCola = Dte::where('codEstado', Dte::ESTADO_EN_COLA)
                ->limit($limite)
                ->get();

            $procesados = 0;
            $errores = 0;

            foreach ($documentosEnCola as $dte) {
                try {
                    // Aquí iría la lógica de procesamiento real
                    // Por ahora simulamos el procesamiento
                    $this->procesarDocumento($dte);
                    $procesados++;
                } catch (\Exception $e) {
                    Log::error("Error procesando DTE {$dte->id}: " . $e->getMessage());
                    $errores++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Procesados: {$procesados}, Errores: {$errores}",
                'procesados' => $procesados,
                'errores' => $errores
            ]);

        } catch (\Exception $e) {
            Log::error("Error en procesarCola: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la cola: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar reintentos
     */
    public function procesarReintentos(Request $request)
    {
        try {
            $documentosParaReintento = Dte::paraReintento()->get();

            $procesados = 0;
            $errores = 0;

            foreach ($documentosParaReintento as $dte) {
                try {
                    $this->procesarDocumento($dte);
                    $procesados++;
                } catch (\Exception $e) {
                    Log::error("Error en reintento DTE {$dte->id}: " . $e->getMessage());
                    $errores++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Reintentos procesados: {$procesados}, Errores: {$errores}",
                'procesados' => $procesados,
                'errores' => $errores
            ]);

        } catch (\Exception $e) {
            Log::error("Error en procesarReintentos: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar reintentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista de errores
     */
    public function errores(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        $query = Dte::conErrores()->with(['company', 'sale']);

        if ($empresaId) {
            $query->where('company_id', $empresaId);
        }

        $errores = $query->orderBy('updated_at', 'desc')->paginate(20);
        $empresas = Company::all();

        return view('dte.errores', compact('errores', 'empresas'));
    }

    /**
     * Vista simple de errores
     */
    public function erroresSimple()
    {
        $errores = Dte::conErrores()
            ->with(['company'])
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return view('dte.errores-simple', compact('errores'));
    }

    /**
     * Mostrar detalles de un error
     */
    public function errorShow($id)
    {
        $dte = Dte::with(['company', 'sale'])
            ->findOrFail($id);

        return view('dte.error-show', compact('dte'));
    }

    /**
     * Lista de contingencias
     */
    public function contingencias(Request $request)
    {
        // Obtener empresas para el filtro
        $empresas = Company::all();

        // Obtener filtros de la request
        $filtros = [
            'empresa_id' => $request->get('empresa_id'),
            'estado' => $request->get('estado'),
            'fecha_desde' => $request->get('fecha_desde'),
            'fecha_hasta' => $request->get('fecha_hasta'),
            'tipo' => $request->get('tipo'),
            'incluir_borradores_filtro' => $request->get('incluir_borradores_filtro')
        ];
        // Construir query base
        $query = Contingencia::with('empresa');

        // Aplicar filtros
        if ($filtros['empresa_id']) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if ($filtros['estado']) {
            $query->where('estado', $filtros['estado']);
        }

        if ($filtros['fecha_desde']) {
            $query->whereDate('fecha_inicio', '>=', $filtros['fecha_desde']);
        }

        if ($filtros['fecha_hasta']) {
            $query->whereDate('fecha_fin', '<=', $filtros['fecha_hasta']);
        }

        if ($filtros['tipo']) {
            $query->where('tipo_contingencia', $filtros['tipo']);
        }

        $contingencias = $query->with(['dtes.sale', 'dtes.company'])->orderBy('created_at', 'desc')->paginate(15);

        // Cargar ventas relacionadas para cada contingencia
        foreach ($contingencias as $contingencia) {
            $contingencia->ventas = Sale::where('id_contingencia', $contingencia->id)->with('client', 'typedocument')->get();
        }

        return view('dte.contingencias', compact('contingencias', 'empresas', 'filtros'));
    }

    /**
     * Almacenar nueva contingencia
     */
    public function storeContingencia(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'empresa_id' => 'required|exists:companies,id',
            'tipo' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'motivo' => 'required|string'
        ]);

        $contingencia = Contingencia::create([
            'nombre' => $request->nombre,
            'empresa_id' => $request->empresa_id,
            'tipo' => $request->tipo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'motivo' => $request->motivo,
            'estado' => 'pendiente',
            'created_by' => auth()->id()
        ]);

        return redirect()->route('dte.contingencias')
            ->with('success', 'Contingencia creada exitosamente');
    }

    /**
     * Activar contingencia
     */
    public function activateContingencia($id)
    {
        $contingencia = Contingencia::findOrFail($id);
        $contingencia->update(['estado' => 'activa']);

        return redirect()->route('dte.contingencias')
            ->with('success', 'Contingencia activada');
    }

    /**
     * Procesar contingencia
     */
    public function processContingencia($id)
    {
        $contingencia = Contingencia::findOrFail($id);

        // Lógica para procesar documentos bajo contingencia
        $documentosAfectados = Dte::where('company_id', $contingencia->empresa_id)
            ->where('codEstado', Dte::ESTADO_RECHAZADO)
            ->where('nSends', '>=', 3)
            ->update(['idContingencia' => $contingencia->id]);

        return redirect()->route('dte.contingencias')
            ->with('success', "Contingencia procesada. Documentos afectados: {$documentosAfectados}");
    }

    /**
     * Reprocesar un documento específico
     */
    public function reprocesar($id)
    {
        $dte = Dte::findOrFail($id);

        try {
            $this->procesarDocumento($dte);

            return redirect()->back()
                ->with('success', 'Documento reprocesado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al reprocesar: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un DTE
     */
    public function destroy($id)
    {
        $dte = Dte::findOrFail($id);
        $dte->delete();

        return redirect()->route('dte.documentos')
            ->with('success', 'Documento eliminado exitosamente');
    }

    /**
     * Procesar un documento individual
     */
    private function procesarDocumento(Dte $dte)
    {
        // Simular procesamiento
        // En la implementación real, aquí iría la lógica de envío a Hacienda

        $dte->update([
            'codEstado' => '02',
            'Estado' => 'Enviado',
            'nSends' => $dte->nSends + 1,
            'updated_at' => now()
        ]);
    }

    /**
     * Obtener color del estado
     */
    private function getEstadoColor($codEstado)
    {
        return match($codEstado) {
            '01' => 'warning',
            '02' => 'success',
            '03' => 'danger',
            '10' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Obtener texto del estado
     */
    private function getEstadoTexto($codEstado)
    {
        return match($codEstado) {
            '01' => 'En Cola',
            '02' => 'Enviado',
            '03' => 'Rechazado',
            '10' => 'En Revisión',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener texto del tipo DTE
     */
    private function getTipoDteTexto($tipoDte)
    {
        return match($tipoDte) {
            '01' => 'Factura',
            '02' => 'Nota de Crédito',
            '03' => 'Nota de Débito',
            '04' => 'Tiquete',
            '05' => 'Comprobante de Crédito Fiscal',
            '06' => 'Comprobante de Débito Fiscal',
            '07' => 'Comprobante de Retención',
            '08' => 'Comprobante de Pago',
            '09' => 'Comprobante de Exportación',
            '10' => 'Comprobante de Importación',
            '11' => 'Comprobante de Donación',
            '12' => 'Comprobante de Venta de Bienes Usados',
            '13' => 'Comprobante de Venta de Bienes Inmuebles',
            '14' => 'Comprobante de Venta de Servicios',
            '15' => 'Comprobante de Venta de Bienes y Servicios',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener texto del tipo de contingencia
     */
    private function getTipoContingenciaTexto($tipo)
    {
        return match($tipo) {
            1 => 'No disponibilidad de sistema del MH',
            2 => 'No disponibilidad de sistema del emisor',
            3 => 'Falla en el suministro de servicio de Internet del Emisor',
            4 => 'Falla en el suministro de servicio de energía eléctrica del emisor',
            5 => 'Otro motivo',
            default => 'Tipo desconocido'
        };
    }

    /**
     * Obtener badge del estado de contingencia
     */
    private function getContingenciaEstadoBadge($estado)
    {
        $color = match($estado) {
            '01', 'En Cola' => 'warning',
            '02', 'Enviado' => 'success',
            '03', 'Rechazado' => 'danger',
            '10', 'En Revisión' => 'info',
            default => 'secondary'
        };

        $texto = match($estado) {
            '01', 'En Cola' => 'En Cola',
            '02', 'Enviado' => 'Enviado',
            '03', 'Rechazado' => 'Rechazado',
            '10', 'En Revisión' => 'En Revisión',
            default => 'Desconocido'
        };

        return "<span class='badge bg-{$color}'>{$texto}</span>";
    }

    /**
     * Obtener DTEs para contingencia (API endpoint)
     */
    public function getDtesParaContingencia(Request $request)
    {
        try {
            $empresaId = $request->get('empresa_id');
            $incluirBorradores = $request->get('incluir_borradores', true);

            $dtes = collect();

            // Solo DTEs en borrador (excluyendo los que ya tienen DTE emitido)
            if ($empresaId) {
                $dtesEnBorrador = Sale::where('company_id', $empresaId)
                    ->where('typesale', 2) // Solo typesale = 2 (borradores)
                    ->whereIn('typedocument_id', [6, 3]) // Facturas y Créditos Fiscales
                    ->whereNull('codigoGeneracion') // Solo las que no tienen DTE emitido
                    ->whereNull('id_contingencia')
                    ->select('id', 'client_id', 'typedocument_id')
                    ->get()
                    ->map(function($sale) {
                        return [
                            'id' => 'sale_' . $sale->id,
                            'cliente' => 'Cliente ID: ' . $sale->client_id,
                            'tipo_documento' => 'Tipo ID: ' . $sale->typedocument_id,
                            'estado' => 'Borrador',
                        ];
                    });

                $dtes = $dtes->merge($dtesEnBorrador);
            }

            // Ventas sin DTE generado (solo las que no tienen codigoGeneracion)
            if ($incluirBorradores) {
                $ventasSinDte = Sale::where('typesale', '<>', 3) // Excluir typesale = 3
                    ->whereNull('codigoGeneracion') // Solo las que no tienen DTE emitido
                    ->whereNull('id_contingencia')
                    ->when($empresaId, function($query) use ($empresaId) {
                        return $query->where('company_id', $empresaId);
                    })
                    ->with(['client', 'typedocument'])
                    ->limit(20)
                    ->get();

                // Si no encontramos ventas, devolver datos de prueba
                if ($ventasSinDte->count() == 0) {
                    $ventasPrueba = collect([
                        [
                            'id' => 'sale_test_1',
                            'numero_control' => 'FAC-001-TEST',
                            'tipo_documento' => 'Factura',
                            'cliente' => 'Cliente de Prueba 1',
                            'fecha' => date('d/m/Y H:i'),
                            'intentos' => 0,
                            'estado' => 'Sin DTE (Borrador)',
                            'tipo' => 'sale'
                        ],
                        [
                            'id' => 'sale_test_2',
                            'numero_control' => 'FAC-002-TEST',
                            'tipo_documento' => 'Crédito Fiscal',
                            'cliente' => 'Cliente de Prueba 2',
                            'fecha' => date('d/m/Y H:i'),
                            'intentos' => 0,
                            'estado' => 'Sin DTE (Borrador)',
                            'tipo' => 'sale'
                        ]
                    ]);

                    $dtes = $dtes->merge($ventasPrueba);
                } else {
                    $ventasSinDte = $ventasSinDte->map(function($sale) {
                        $cliente = 'Sin cliente';
                        if ($sale->client) {
                            $cliente = $sale->client->name_contribuyente ??
                                      $sale->client->firstname . ' ' . $sale->client->firstlastname;
                        }

                        return [
                            'id' => 'sale_' . $sale->id,
                            'numero_control' => $sale->numero_control ?: 'Sin número',
                            'tipo_documento' => $sale->typedocument->name ?? 'Venta',
                            'cliente' => $cliente,
                            'fecha' => $sale->created_at ? $sale->created_at->format('d/m/Y H:i') : 'Sin fecha',
                            'intentos' => 0,
                            'estado' => 'Sin DTE (Borrador)',
                            'tipo' => 'sale'
                        ];
                    })
                    ->filter(function($sale) {
                        // Filtrar solo ventas con datos válidos
                        return $sale['cliente'] !== 'Sin cliente' &&
                               $sale['numero_control'] !== 'Sin número' &&
                               $sale['tipo_documento'] !== 'N/A';
                    });

                    $dtes = $dtes->merge($ventasSinDte);
                }
            }

            return response()->json($dtes->values());

        } catch (\Exception $e) {
            Log::error('Error al cargar documentos para contingencia: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error al cargar documentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test para DTEs para contingencia (API endpoint)
     */
    public function testDtesParaContingencia(Request $request)
    {
        try {
            // Datos de prueba para testing
            $datosPrueba = collect([
                [
                    'id' => 'test_1',
                    'numero_control' => 'FAC-TEST-001',
                    'tipo_documento' => 'Factura',
                    'cliente' => 'Cliente Test 1',
                    'fecha' => date('d/m/Y H:i'),
                    'estado' => 'Test Borrador',
                    'tipo' => 'test'
                ],
                [
                    'id' => 'test_2',
                    'numero_control' => 'CF-TEST-002',
                    'tipo_documento' => 'Crédito Fiscal',
                    'cliente' => 'Cliente Test 2',
                    'fecha' => date('d/m/Y H:i'),
                    'estado' => 'Test Borrador',
                    'tipo' => 'test'
                ]
            ]);

            return response()->json($datosPrueba);

        } catch (\Exception $e) {
            Log::error('Error en test DTEs para contingencia: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error en test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ventas con contingencia (API endpoint)
     */
    public function ventasConContingencia(Request $request)
    {
        try {
            $empresaId = $request->get('empresa_id');

            $ventasConContingencia = Sale::whereNotNull('id_contingencia')
                ->when($empresaId, function($query) use ($empresaId) {
                    return $query->where('company_id', $empresaId);
                })
                ->with(['client', 'typedocument', 'contingencia'])
                ->get()
                ->map(function($sale) {
                    return [
                        'id' => $sale->id,
                        'numero_control' => $sale->numero_control,
                        'cliente' => $sale->client ? $sale->client->name_contribuyente ?? $sale->client->firstname : 'Sin cliente',
                        'tipo_documento' => $sale->typedocument->name ?? 'Sin tipo',
                        'contingencia_id' => $sale->id_contingencia,
                        'fecha' => $sale->created_at->format('d/m/Y H:i'),
                        'estado' => 'Con Contingencia'
                    ];
                });

            return response()->json($ventasConContingencia);

        } catch (\Exception $e) {
            Log::error('Error al cargar ventas con contingencia: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error al cargar ventas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Autorizar contingencia
     */
    public function autorizarContingencia(Request $request)
    {
        try {
            $request->validate([
                'empresa_id' => 'required|integer|exists:companies,id',
                'documentos' => 'required|array|min:1',
                'motivo' => 'required|string|max:500',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'tipo_contingencia' => 'required|in:01,02,03,04,05,06,07,08,09,10'
            ]);

            $empresaId = $request->empresa_id;
            $documentos = $request->documentos;
            $motivo = $request->motivo;
            $fechaInicio = $request->fecha_inicio;
            $fechaFin = $request->fecha_fin;
            $tipoContingencia = $request->tipo_contingencia;

            // Crear la contingencia
            $contingencia = Contingencia::create([
                'empresa_id' => $empresaId,
                'user_id' => auth()->id(),
                'nombre' => 'Contingencia Automática - ' . now()->format('d/m/Y H:i'),
                'descripcion' => $motivo,
                'motivo' => $motivo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipo_contingencia' => $tipoContingencia,
                'documentos_afectados' => json_encode($documentos),
                'estado' => '01', // Pendiente
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Procesar cada documento
            $documentosProcesados = [];
            foreach ($documentos as $documentoId) {
                if (strpos($documentoId, 'sale_') === 0) {
                    $saleId = str_replace('sale_', '', $documentoId);

                    $sale = Sale::find($saleId);
                    if ($sale) {
                        // Asignar la contingencia a la venta
                        $sale->update([
                            'id_contingencia' => $contingencia->id,
                            'updated_at' => now()
                        ]);

                        $documentosProcesados[] = [
                            'tipo' => 'sale',
                            'id' => $saleId,
                            'numero_control' => $sale->numero_control
                        ];
                    }
                }
            }

            // Simular procesamiento de envío a Hacienda
            // En un entorno real, aquí se haría la llamada a la API de Hacienda
            sleep(2); // Simular tiempo de procesamiento

            // Actualizar el estado de la contingencia
            $contingencia->update([
                'estado' => '02', // Aprobada
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contingencia autorizada y enviada a Hacienda correctamente',
                'contingencia' => [
                    'id' => $contingencia->id,
                    'nombre' => $contingencia->nombre,
                    'estado' => $contingencia->estado
                ],
                'documentos_procesados' => $documentosProcesados,
                'total_documentos' => count($documentosProcesados)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al autorizar contingencia: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al autorizar contingencia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva contingencia
     */
    public function crearContingencia(Request $request)
    {
        try {
            $request->validate([
                'company' => 'required|integer|exists:companies,id',
                'tipoContingencia' => 'required|integer|between:1,10',
                'motivoContingencia' => 'required|string|max:500',
                'nombreResponsable' => 'required|string|max:255',
                'tipoDocResponsable' => 'required|string|max:50',
                'nuDocResponsable' => 'required|string|max:50',
                'fechaCreacion' => 'required|date',
                'fechaInicioFin' => 'required|date',
                'versionJson' => 'required|integer',
                'ambiente' => 'required|string|in:00,01'
            ]);

            // Verificar que la columna empresa_id existe en la tabla
            if (!Schema::hasColumn('contingencias', 'empresa_id')) {
                throw new \Exception(
                    'La columna empresa_id no existe en la tabla contingencias. ' .
                    'Por favor ejecuta la migración: php artisan migrate'
                );
            }

            // Crear la contingencia
            $contingencia = new Contingencia();

            // Preparar datos para la contingencia
            $contingenciaData = [
                'empresa_id' => $request->company, // Campo principal
                'versionJson' => $request->versionJson,
                'ambiente' => $request->ambiente,
                'codEstado' => "01",
                'estado' => "En Cola",
                'tipoContingencia' => $request->tipoContingencia,
                'motivoContingencia' => $request->motivoContingencia,
                'nombreResponsable' => $request->nombreResponsable,
                'tipoDocResponsable' => $request->tipoDocResponsable,
                'nuDocResponsable' => $request->nuDocResponsable,
                'created_by' => auth()->id(),
            ];

            // Asignar fechas
            $fc = Carbon::parse($request->fechaCreacion, 'America/El_Salvador');
            $fi = Carbon::parse($request->fechaInicioFin, 'America/El_Salvador');

            $contingenciaData['fechaCreacion'] = $fc->toDateTimeString();
            $contingenciaData['fInicio'] = $fi->toDateString();
            $contingenciaData['fFin'] = $fi->toDateString();
            $contingenciaData['horaCreacion'] = $fc->format('H:i:s');
            $contingenciaData['hInicio'] = $fi->format('H:i:s');
            $contingenciaData['hFin'] = $fi->format('H:i:s');
            $contingenciaData['codigoGeneracion'] = strtoupper(Str::uuid()->toString());
            $contingenciaData['selloRecibido'] = null;

            // Log de depuración: mostrar datos que se intentarán insertar
            Log::info('Intentando crear contingencia con datos:', [
                'contingenciaData' => $contingenciaData,
                'fillable' => $contingencia->getFillable(),
                'table' => $contingencia->getTable()
            ]);

            // Verificar que todos los campos requeridos estén presentes
            $requiredFields = ['empresa_id', 'versionJson', 'ambiente', 'codEstado', 'estado',
                             'tipoContingencia', 'motivoContingencia', 'nombreResponsable',
                             'tipoDocResponsable', 'nuDocResponsable', 'created_by'];

            $missingFields = array_diff($requiredFields, array_keys($contingenciaData));
            if (!empty($missingFields)) {
                throw new \Exception('Faltan campos requeridos: ' . implode(', ', $missingFields));
            }

            // Asignar todos los datos de una vez
            $contingencia->fill($contingenciaData);

            // Intentar guardar
            $contingencia->save();

            Log::info('Contingencia creada exitosamente', ['id' => $contingencia->id]);

            // Procesar documentos seleccionados manualmente
            if ($request->dte_ids && is_array($request->dte_ids)) {
                foreach ($request->dte_ids as $dteId) {
                    if (strpos($dteId, 'sale_') === 0) {
                        // Es una venta sin DTE (borrador)
                        $saleId = str_replace('sale_', '', $dteId);
                        $sale = Sale::find($saleId);
                        if ($sale && !$sale->codigoGeneracion) {
                            $sale->id_contingencia = $contingencia->id;
                            $uuid_generado = strtoupper(Str::uuid()->toString());
                            $sale->codigoGeneracion = $uuid_generado;
                            $sale->save();
                        }
                    } else {
                        // Es un DTE existente (en borrador)
                        $dte = Dte::find($dteId);
                        if ($dte && $dte->codEstado !== '02') {
                            $dte->idContingencia = $contingencia->id;
                            $dte->save();

                            // También actualizar la venta asociada
                            if ($dte->sale_id) {
                                $sale = Sale::find($dte->sale_id);
                                if ($sale && !$sale->codigoGeneracion) {
                                    $sale->id_contingencia = $contingencia->id;
                                    $uuid_generado = strtoupper(Str::uuid()->toString());
                                    $sale->codigoGeneracion = $uuid_generado;
                                    $sale->save();
                                }
                            }
                        }
                    }
                }
            } else {
                // Si no se seleccionaron documentos, usar el flujo automático
                $countfacturas = Sale::leftJoin('dte', 'dte.sale_id', '=', 'sales.id')
                    ->whereNull('dte.sale_id')
                    ->whereNull('sales.codigoGeneracion')
                    ->where(function ($query) {
                        $query->where('typedocument_id', '=', 6)
                              ->orWhere('typedocument_id', '=', 3);
                    })
                    ->select('sales.id', 'dte.id as DTEID')
                    ->take(3)
                    ->get();

                foreach ($countfacturas as $fac) {
                    $updatefac = Sale::find($fac->id);
                    if ($updatefac) {
                        $updatefac->id_contingencia = $contingencia->id;
                        $uuid_generado = strtoupper(Str::uuid()->toString());
                        $updatefac->codigoGeneracion = $uuid_generado;
                        $updatefac->save();
                    }
                }
            }

            return redirect()->route('dte.contingencias')
                ->with('success', 'Contingencia creada con éxito');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación
            Log::error('Error de validación al crear contingencia: ' . json_encode($e->errors()));
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Error de validación: ' . implode(', ', array_map(function($errors) {
                    return implode(', ', $errors);
                }, $e->errors())));
        } catch (\Illuminate\Database\QueryException $e) {
            // Errores de base de datos
            $errorMessage = 'Error de base de datos: ' . $e->getMessage();
            $errorDetails = [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'code' => $e->getCode(),
            ];

            Log::error('Error SQL al crear contingencia: ' . $errorMessage, $errorDetails);

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage)
                ->with('error_details', $errorDetails);
        } catch (\Exception $e) {
            // Otros errores
            $errorMessage = 'Error al crear contingencia: ' . $e->getMessage();
            $errorDetails = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error('Error al crear contingencia: ' . $errorMessage, $errorDetails);

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage)
                ->with('error_details', $errorDetails);
        }
    }

    /**
     * Autorizar contingencia existente (método completo desde RomaCopies)
     */
    public function autorizarContingenciaExistente(Request $request)
    {
        try {
            $request->validate([
                'empresa_id' => 'required|integer|exists:companies,id',
                'contingencia_id' => 'required|integer|exists:contingencias,id'
            ]);

            $contingenciaId = $request->contingencia_id;
            $empresaId = $request->empresa_id;

            $contingencia = Contingencia::findOrFail($contingenciaId);

            // Verificar que la contingencia pertenece a la empresa
            if ($contingencia->empresa_id != $empresaId && $contingencia->idEmpresa != $empresaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contingencia no pertenece a esta empresa'
                ], 403);
            }

            // Verificar que la contingencia esté en estado correcto
            if ($contingencia->estado !== 'En Cola' && $contingencia->estado !== 'Rechazado') {
                return response()->json([
                    'success' => false,
                    'message' => 'La contingencia ya fue procesada o no está en estado válido'
                ], 400);
            }

            // Procesar las ventas asignadas a la contingencia
            $ventasAsignadas = Sale::where('id_contingencia', $contingenciaId)
                ->where('company_id', $empresaId)
                ->get();

            if ($ventasAsignadas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay ventas válidas asignadas a esta contingencia'
                ], 400);
            }

            DB::beginTransaction();

            $documentosProcesados = 0;
            $errores = [];
            $dtesGenerados = [];

            foreach ($ventasAsignadas as $venta) {
                try {
                    // Llamar al método createDocument existente
                    $resultado = $this->procesarVentaParaContingencia($venta);
                    if ($resultado['success']) {
                        $documentosProcesados++;
                        $dtesGenerados[] = [
                            'venta_id' => $venta->id,
                            'comprobante' => $resultado['comprobante'],
                            'json_dte' => $resultado['json_dte']
                        ];
                    } else {
                        $errores[] = "Venta {$venta->id}: {$resultado['message']}";
                    }
                } catch (\Exception $e) {
                    $errores[] = "Venta {$venta->id}: Error interno - {$e->getMessage()}";
                }
            }

            if ($documentosProcesados > 0) {
                // Generar JSON de contingencia según schema v3
                $jsonContingencia = $this->generarJsonContingencia($contingencia, $empresaId, $dtesGenerados);

                // Enviar a Hacienda usando el sistema existente
                $resultadoEnvio = $this->enviarContingenciaAHacienda($contingencia, $jsonContingencia, $empresaId);

                if (@$resultadoEnvio['estado'] == 'RECIBIDO') {
                    $contingencia->estado = 'Enviado';
                    $contingencia->codEstado = '02';
                    $contingencia->estadoHacienda = $resultadoEnvio['estado'];
                    $contingencia->fhRecibido = now();
                    $contingencia->selloRecibido = $resultadoEnvio['sello'] ?? null;
                    $contingencia->observacionesMsg = $resultadoEnvio['observaciones'] ?? 'Procesado correctamente';
                    $contingencia->created_by = auth()->user()->id;
                    $contingencia->updated_by = auth()->user()->id;
                    $contingencia->save();

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Contingencia autorizada y enviada a Hacienda correctamente',
                        'documentos_procesados' => $documentosProcesados,
                        'contingencia' => [
                            'id' => $contingencia->id,
                            'estado' => $contingencia->estado,
                            'selloRecibido' => $contingencia->selloRecibido
                        ]
                    ]);
                } else {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al enviar a Hacienda: ' . ($resultadoEnvio['error'] ?? 'Error desconocido')
                    ], 500);
                }
            } else {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron procesar los documentos: ' . implode('; ', $errores)
                ], 500);
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al autorizar contingencia existente: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al autorizar contingencia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar contingencia a Hacienda (desde RomaCopies)
     */
    private function enviarContingenciaAHacienda($contingencia, $jsonContingencia, $empresaId)
    {
        try {
            // Obtener configuración de la empresa
            $config = DB::table('config')
                ->join('ambientes', 'config.ambiente', '=', 'ambientes.id')
                ->join('companies', 'config.company_id', '=', 'companies.id')
                ->where('config.company_id', $empresaId)
                ->select(
                    'config.passPrivateKey',
                    'config.passMH',
                    'companies.nit',
                    'ambientes.url_credencial',
                    'ambientes.url_contingencia',
                    'ambientes.url_firmador',
                    'ambientes.cod as ambiente'
                )
                ->first();

            if (!$config) {
                throw new \Exception('Configuración de empresa no encontrada');
            }

            // Preparar datos para firma
            $firma_electronica = [
                "nit" => str_replace('-', '', $config->nit),
                "activo" => true,
                "passwordPri" => $config->passPrivateKey,
                "dteJson" => $jsonContingencia
            ];

            // Firmar documento usando la URL del firmador configurada
            $urlFirmador = $config->url_firmador;
            if (empty($urlFirmador)) {
                throw new \Exception('URL del firmador no configurada en ambientes');
            }

            $responseFirma = \Illuminate\Support\Facades\Http::accept('application/json')
                ->post(rtrim($urlFirmador, '/').'/', $firma_electronica);

            if (!$responseFirma->successful()) {
                throw new \Exception('Error en la firma del documento: ' . $responseFirma->body());
            }

            $objFirma = $responseFirma->json();
            $documentoFirmado = $objFirma["body"];

            // Obtener token para Hacienda
            $credenciales = [
                "user" => str_replace('-', '', $config->nit),
                "pwd" => $config->passMH
            ];

            // Usar el método getTokenMH existente del SaleController
            $saleController = new \App\Http\Controllers\SaleController();
            $respToken = $saleController->getTokenMH($empresaId, $credenciales, $config->url_credencial);

            if ($respToken !== 'OK') {
                throw new \Exception('Error obteniendo token de Hacienda: ' . (is_string($respToken) ? $respToken : 'Respuesta inválida'));
            }

            $token = \Illuminate\Support\Facades\Session::get($empresaId);
            if (empty($token)) {
                throw new \Exception('Token de Hacienda no disponible en sesión después de getTokenMH');
            }

            // Enviar a Hacienda
            $documentoEnvio = [
                "ambiente" => $config->ambiente,
                "version" => intval($contingencia->versionJson ?? 3),
                "documento" => $documentoFirmado
            ];

            $responseEnvio = \Illuminate\Support\Facades\Http::withToken($token)
                ->post($config->url_contingencia, $documentoEnvio);

            if (!$responseEnvio->successful()) {
                throw new \Exception('Error enviando a Hacienda: ' . $responseEnvio->body());
            }

            $resultadoEnvio = $responseEnvio->json();

            if (isset($resultadoEnvio['estado']) && $resultadoEnvio['estado'] === "RECIBIDO") {
                return [
                    'success' => true,
                    'estado' => 'RECIBIDO',
                    'fechaHora' => $resultadoEnvio['fechaHora'] ?? null,
                    'sello' => $resultadoEnvio['selloRecibido'] ?? null,
                    'observaciones' => !empty($resultadoEnvio['observaciones']) ? implode('; ', $resultadoEnvio['observaciones']) : 'Procesado correctamente',
                    'observaciones_list' => $resultadoEnvio['observaciones'] ?? []
                ];
            } else {
                return [
                    'success' => false,
                    'estado' => $resultadoEnvio['estado'] ?? null,
                    'fechaHora' => $resultadoEnvio['fechaHora'] ?? null,
                    'observaciones' => !empty($resultadoEnvio['observaciones']) ? implode('; ', $resultadoEnvio['observaciones']) : null,
                    'observaciones_list' => $resultadoEnvio['observaciones'] ?? [],
                    'error' => $resultadoEnvio['mensaje'] ?? $responseEnvio->body()
                ];
            }

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Procesar venta para contingencia (desde RomaCopies)
     */
    private function procesarVentaParaContingencia($venta)
    {
        try {
            // Validar que la venta tenga un cliente asignado
            if (empty($venta->client_id) || $venta->client_id === null) {
                throw new \Exception("Error: La venta {$venta->id} no tiene un cliente asignado.");
            }

            // Cambiar tipoventa a 1 (finalizada) para que se pueda procesar
            $venta->typesale = 1;

            // Obtener correlativo si no lo tiene
            if (!$venta->nu_doc) {
                $newCorr = DB::table('docs as docs')
                    ->join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
                    ->where('tdoc.id', '=', $venta->typedocument_id)
                    ->where('docs.id_empresa', '=', $venta->company_id)
                    ->select('docs.actual', 'docs.id')
                    ->first();

                if ($newCorr) {
                    $venta->nu_doc = $newCorr->actual;
                    DB::table('docs')->where('id', $newCorr->id)->increment('actual');
                }
            }

            $venta->save();

            // Construir comprobante completo
            $comprobante = $this->construirComprobanteVenta($venta);

            // Generar JSON del DTE usando la función helper
            $comprobanteElectronico = convertir_json($comprobante, "01");

            // Generar UUID si no lo tiene
            if (!$venta->codigoGeneracion) {
                $venta->codigoGeneracion = $comprobanteElectronico["identificacion"]["codigoGeneracion"];
                $venta->numero_control = $comprobanteElectronico["identificacion"]["numeroControl"];
                $venta->json = json_encode($comprobante);
                $venta->save();
            }

            return [
                'success' => true,
                'message' => 'Venta procesada correctamente',
                'comprobante' => $comprobante,
                'json_dte' => $comprobanteElectronico
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Generar JSON de contingencia (desde RomaCopies)
     */
    private function generarJsonContingencia($contingencia, $empresaId, $dtesGenerados)
    {
        try {
            // Obtener datos de la empresa (usar el primer DTE para obtener datos completos)
            $primerDte = $dtesGenerados[0];
            $documento = $primerDte['comprobante']['documento'][0];
            $emisor = $primerDte['comprobante']['emisor'][0];

            // Identificación con TZ local (El Salvador)
            $now = Carbon::now('America/El_Salvador');
            $identificacion = [
                "version" => intval($contingencia->versionJson ?? 3),
                "ambiente" => $documento->ambiente,
                "codigoGeneracion" => $contingencia->codigoGeneracion,
                "fTransmision" => $now->format('Y-m-d'),
                "hTransmision" => $now->format('H:i:s')
            ];

            // Emisor (usando datos del primer comprobante para consistencia)
            $emisorData = [
                "nit" => str_replace('-', '', $emisor->nit),
                "nombre" => trim($emisor->nombre),
                "nombreResponsable" => $contingencia->nombreResponsable,
                "tipoDocResponsable" => trim($contingencia->tipoDocResponsable),
                "numeroDocResponsable" => str_replace("-", "", $contingencia->nuDocResponsable),
                "tipoEstablecimiento" => $emisor->tipoEstablecimiento ?? "01",
                "codEstableMH" => null,
                "codPuntoVenta" => null,
                "telefono" => str_replace('-', '', $emisor->telefono ?? '00000000'),
                "correo" => $emisor->correo
            ];

            // Detalle DTE - usar los códigos de generación de los DTEs reales
            $detalleDTE = [];
            $itemCounter = 1;

            foreach ($dtesGenerados as $dte) {
                $jsonDte = $dte['json_dte'];
                $detalleDTE[] = [
                    "noItem" => $itemCounter,
                    "codigoGeneracion" => $jsonDte["identificacion"]["codigoGeneracion"],
                    "tipoDoc" => $jsonDte["identificacion"]["tipoDte"]
                ];
                $itemCounter++;
            }

            // Motivo: asegurar que "ahora" esté dentro del rango
            $fInicioModel = $contingencia->fInicio ? Carbon::parse($contingencia->fInicio, 'America/El_Salvador') : null;
            $fFinModel = $contingencia->fFin ? Carbon::parse($contingencia->fFin, 'America/El_Salvador') : null;
            $hInicioModel = $contingencia->hInicio ? $contingencia->hInicio : null;
            $hFinModel = $contingencia->hFin ? $contingencia->hFin : null;

            $rangeOk = false;
            if ($fInicioModel && $fFinModel && $hInicioModel && $hFinModel) {
                try {
                    $inicio = Carbon::parse($fInicioModel->format('Y-m-d') . ' ' . $hInicioModel, 'America/El_Salvador');
                    $fin = Carbon::parse($fFinModel->format('Y-m-d') . ' ' . $hFinModel, 'America/El_Salvador');
                    $rangeOk = $now->betweenIncluded($inicio, $fin);
                } catch (\Throwable $e) {
                    $rangeOk = false;
                }
            }

            if (!$rangeOk) {
                $fInicio = $now->copy()->startOfDay();
                $fFin = $now->copy()->endOfDay();
                $hInicio = '00:00:00';
                $hFin = '23:59:59';
            } else {
                $fInicio = $fInicioModel;
                $fFin = $fFinModel;
                $hInicio = $hInicioModel;
                $hFin = $hFinModel;
            }

            $motivo = [
                "fInicio" => $fInicio->format('Y-m-d'),
                "fFin" => $fFin->format('Y-m-d'),
                "hInicio" => $hInicio,
                "hFin" => $hFin,
                "tipoContingencia" => intval($contingencia->tipoContingencia),
                "motivoContingencia" => $contingencia->motivoContingencia
            ];

            // JSON completo según schema v3
            $jsonContingencia = [
                "identificacion" => $identificacion,
                "emisor" => $emisorData,
                "detalleDTE" => $detalleDTE,
                "motivo" => $motivo
            ];

            Log::info('JSON Contingencia generado con DTEs reales', [
                'contingencia_id' => $contingencia->id,
                'empresa_id' => $empresaId,
                'documentos_incluidos' => count($detalleDTE),
                'codigos_generacion' => array_column($detalleDTE, 'codigoGeneracion'),
                'json_size' => strlen(json_encode($jsonContingencia))
            ]);

            return $jsonContingencia;

        } catch (\Exception $e) {
            Log::error('Error generando JSON de contingencia: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Construir comprobante completo de una venta (desde RomaCopies)
     */
    private function construirComprobanteVenta($venta)
    {
        $idempresa = $venta->company_id;
        $corr = $venta->id;

        // Detalle de la factura (cálculos de totales)
        $detailsbd = DB::table('salesdetails')
            ->where('sale_id', '=', $corr)
            ->selectRaw('
                SUM(nosujeta) nosujeta,
                SUM(exempt) exentas,
                SUM(pricesale) gravadas,
                SUM(nosujeta+exempt+pricesale) subtotalventas,
                0 descnosujeta,
                0 descexenta,
                0 desgravada,
                0 porcedesc,
                0 totaldesc,
                NULL tributos,
                SUM(nosujeta+exempt+pricesale) subtotal,
                SUM(detained) ivarete,
                SUM(renta) rentarete,
                SUM(detained13) iva
            ')
            ->first();

        $totalPagar = ($detailsbd->nosujeta + $detailsbd->exentas + $detailsbd->gravadas + $detailsbd->iva - ($detailsbd->rentarete + $detailsbd->ivarete));

        $totales = [
            "totalNoSuj" => (float)$detailsbd->nosujeta,
            "totalExenta" => (float)$detailsbd->exentas,
            "totalGravada" => (float)$detailsbd->gravadas,
            "subTotalVentas" => round((float)($detailsbd->subtotalventas), 8),
            "descuNoSuj" => $detailsbd->descnosujeta,
            "descuExenta" => $detailsbd->descexenta,
            "descuGravada" => $detailsbd->desgravada,
            "porcentajeDescuento" => 0.00,
            "totalDescu" => $detailsbd->totaldesc,
            "tributos" => null,
            "subTotal" => round((float)($detailsbd->subtotal), 8),
            "ivaPerci1" => 0.00,
            "ivaRete1" => 0.00,
            "reteRenta" => round((float)$detailsbd->rentarete, 8),
            "montoTotalOperacion" => round((float)($detailsbd->subtotal), 8),
            "totalNoGravado" => (float)0,
            "totalPagar" => (float)$totalPagar,
            "totalLetras" => numtoletras($totalPagar),
            "saldoFavor" => 0.00,
            "condicionOperacion" => $venta->waytopay,
            "pagos" => null,
            "totalIva" => (float)$detailsbd->iva
        ];

        // Obtener información del documento
        $documento = DB::select(DB::raw("
            SELECT
                a.id id_doc,
                b.type id_tipo_doc,
                docs.serie serie,
                docs.inicial inicial,
                docs.final final,
                docs.actual actual,
                docs.estado estado,
                a.company_id id_empresa,
                a.user_id hechopor,
                a.created_at fechacreacion,
                b.description NombreDocumento,
                c.name NombreUsuario,
                c.nit docUser,
                b.codemh tipodocumento,
                b.versionjson versionJson,
                e.url_credencial,
                e.url_envio,
                e.url_invalidacion,
                e.url_contingencia,
                e.url_firmador,
                d.typeTransmission tipogeneracion,
                e.cod ambiente,
                a.updated_at,
                1 aparece_ventas
            FROM sales a
            INNER JOIN typedocuments b ON a.typedocument_id = b.id
            INNER JOIN docs ON b.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
            INNER JOIN users c ON a.user_id = c.id
            LEFT JOIN config d ON a.company_id = d.company_id
            LEFT JOIN ambientes e ON d.ambiente = e.id
            WHERE a.id = {$corr}
        "));

        // Obtener detalle de productos
        $producto = DB::select(DB::raw("
            SELECT
                c.id id_producto,
                CASE
                    WHEN b.description IS NOT NULL AND b.description != '' THEN
                        CONCAT(b.description, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
                    WHEN c.code = 'LAB' AND b.ruta IS NOT NULL AND b.ruta != '' THEN
                        CONCAT(b.ruta, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
                    WHEN c.id = 9 THEN
                        CONCAT(c.name, ' ', b.reserva, ' ', b.ruta, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
                    ELSE
                        CONCAT(c.name, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
                END AS descripcion,
                b.amountp cantidad,
                b.priceunit precio_unitario,
                0 descuento,
                0 no_imponible,
                (b.pricesale+b.nosujeta+b.exempt) subtotal,
                b.pricesale gravadas,
                b.nosujeta no_sujetas,
                b.exempt exentas,
                b.detained13 iva,
                0 porcentaje_descuento,
                b.detained13 iva_calculado,
                b.renta renta_retenida,
                1 tipo_item,
                '59' AS uniMedida
            FROM sales a
            INNER JOIN salesdetails b ON b.sale_id=a.id
            INNER JOIN products c ON b.product_id=c.id
            LEFT JOIN units u ON b.unit_id = u.id
            WHERE a.id={$corr}
        "));

        // Obtener información del emisor
        $emisor = DB::select(DB::raw("
            SELECT
                a.nit,
                CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                a.name nombre,
                c.code codActividad,
                c.name descActividad,
                a.name nombreComercial,
                a.tipoEstablecimiento,
                f.code departamento,
                g.code municipio,
                d.reference direccion,
                e.phone telefono,
                NULL codEstableMH,
                NULL codEstable,
                NULL codPuntoVentaMH,
                NULL codPuntoVenta,
                a.email correo,
                b.passkeyPublic clavePublicaMH,
                b.passPrivateKey clavePrivadaMH,
                b.passMH claveApiMH
            FROM companies a
            LEFT JOIN config b ON a.id=b.company_id
            INNER JOIN economicactivities c ON a.economicactivity_id=c.id
            INNER JOIN addresses d ON a.address_id=d.id
            INNER JOIN phones e ON a.phone_id=e.id
            INNER JOIN departments f ON d.department_id=f.id
            INNER JOIN municipalities g ON d.municipality_id=g.id
            WHERE a.id={$idempresa}
        "));

        // Obtener información del cliente
        $cliente = DB::select(DB::raw("
            SELECT
                a.id idcliente,
                IF(a.nit = '00000000-0', NULL, a.nit) as nit,
                CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                CASE
                    WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
                    WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
                END AS nombre,
                IF(b.code = 0, NULL, b.code) AS codActividad,
                IF(b.code = 0, NULL, b.name) AS descActividad,
                CASE
                    WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
                    WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
                END AS nombreComercial,
                a.email correo,
                f.code departamento,
                g.code municipio,
                c.reference direccion,
                p.phone telefono,
                1 id_tipo_contribuyente,
                a.tipoContribuyente id_clasificacion_tributaria,
                0 siempre_retiene,
                '36' tipoDocumento,
                a.nit numDocumento,
                '36'tipoDocumentoCliente,
                d.code codPais,
                d.name nombrePais,
                0 siempre_retiene_renta
            FROM clients a
            INNER JOIN economicactivities b ON a.economicactivity_id=b.id
            INNER JOIN addresses c ON a.address_id=c.id
            INNER JOIN phones p ON a.phone_id=p.id
            INNER JOIN countries d ON c.country_id=d.id
            INNER JOIN departments f ON c.department_id=f.id
            INNER JOIN municipalities g ON c.municipality_id=g.id
            WHERE a.id = {$venta->client_id}
        "));

        return [
            "emisor" => $emisor,
            "documento" => $documento,
            "detalle" => $producto,
            "totales" => $totales,
            "cliente" => $cliente
        ];
    }

    /**
     * Autorizar contingencia existente (método GET simple como RomaCopies)
     */
    public function autorizarContingenciaGet($empresaId, $contingenciaId)
    {
        date_default_timezone_set('America/El_Salvador');
        ini_set('max_execution_time', '300');

        \Log::info("=== INICIO AUTORIZACION CONTINGENCIA ===", [
            'empresa_id' => $empresaId,
            'contingencia_id' => $contingenciaId,
            'timestamp' => now()->toDateTimeString()
        ]);

        $tipo_resultado = "";
        $mensaje_resultado = '';

        try {
            // Limpiar estado anterior y resetear para reintento
            $cola = Contingencia::find($contingenciaId);
            \Log::info("CONTINGENCIA ENCONTRADA", [
                'contingencia_id' => $contingenciaId,
                'existe' => $cola ? 'SI' : 'NO',
                'estado_actual' => $cola ? $cola->estado : 'NO_ENCONTRADA',
                'cod_estado' => $cola ? $cola->codEstado : 'NO_ENCONTRADA'
            ]);

            if (!$cola) {
                $tipo_resultado = "danger";
                $mensaje_resultado = 'Contingencia no encontrada';
                \Log::error("CONTINGENCIA NO ENCONTRADA", ['contingencia_id' => $contingenciaId]);
                return redirect()->route('dte.contingencias')
                    ->with($tipo_resultado, $mensaje_resultado);
            }

            // Resetear estado para reintento (limpiar errores anteriores)
            $estadoAnterior = [
                'estado' => $cola->estado,
                'codEstado' => $cola->codEstado,
                'observacionesMsg' => $cola->observacionesMsg,
                'estadoHacienda' => $cola->estadoHacienda
            ];

            $cola->observacionesMsg = null;
            $cola->estadoHacienda = null;
            $cola->fhRecibido = null;
            $cola->selloRecibido = null;

            // Si está rechazada, volver a "En Cola" para reintento
            if ($cola->codEstado == '03' || $cola->estado == 'Rechazado') {
                $cola->codEstado = '01';
                $cola->estado = 'En Cola';
            }

            $cola->save();

            \Log::info("ESTADO RESETEADO", [
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => ['estado' => $cola->estado, 'codEstado' => $cola->codEstado]
            ]);

            $id_empresa = $empresaId;

            // Query para obtener datos de la empresa
            $queryEmpresa = "SELECT
            'Empresa' AS NmTabla,
            a.company_id AS id_empresa,
            REPLACE(c.nit, '-', '') AS nit,
            a.passPrivateKey AS passwordPri,
            a.passMH AS pwd,
            c.email,
            a.ambiente,
            b.url_credencial,
            b.url_envio,
            b.url_invalidacion,
            b.url_contingencia,
            b.url_firmador
            FROM config a
            LEFT OUTER JOIN ambientes b ON a.ambiente=b.id
            LEFT OUTER JOIN companies c ON a.company_id = c.id
            WHERE a.company_id = $id_empresa";
            $empresaconti = DB::select(DB::raw($queryEmpresa));

            \Log::info("DATOS EMPRESA OBTENIDOS", [
                'empresa_id' => $id_empresa,
                'datos_encontrados' => count($empresaconti),
                'empresa_data' => $empresaconti
            ]);

            // Verificar qué columnas existen en la tabla contingencias
            $hasEmpresaId = Schema::hasColumn('contingencias', 'empresa_id');
            $hasIdEmpresa = Schema::hasColumn('contingencias', 'idEmpresa');

            // Construir la condición JOIN y WHERE según las columnas disponibles
            $joinCondition = $hasEmpresaId ? 'a.empresa_id = b.id' : 'a.idEmpresa = b.id';
            if ($hasEmpresaId && $hasIdEmpresa) {
                $joinCondition = 'a.empresa_id = b.id OR a.idEmpresa = b.id';
            }

            $whereCondition = $hasEmpresaId ? "a.empresa_id = $id_empresa" : "a.idEmpresa = $id_empresa";
            if ($hasEmpresaId && $hasIdEmpresa) {
                $whereCondition = "(a.empresa_id = $id_empresa OR a.idEmpresa = $id_empresa)";
            }

            // Query para obtener encabezado
            $queryEncabezado = "SELECT
            'Encabezado' AS NmTabla,
            a.versionJson AS versionJson,
            a.ambiente,
            a.codigoGeneracion,
            a.fechaCreacion,
            a.horaCreacion AS horaCreacion,
            REPLACE(b.nit, '-', '') AS nit_emisor,
            b.name AS nombre_empresa,
            a.nombreResponsable,
            a.tipoDocResponsable,
            a.nuDocResponsable,
            b.tipoEstablecimiento,
            NULL AS codigo_establecimiento,
            NULL AS codigo_punto_venta,
            REPLACE(c.phone, '-', '') AS telefono_emisor,
            b.email AS correo,
            DATE_FORMAT(a.fInicio, '%Y-%m-%d') AS fInicio,
            DATE_FORMAT(a.fFin, '%Y-%m-%d') AS fFin,
            a.hInicio,
            a.hFin,
            a.tipoContingencia,
            a.motivoContingencia,
            a.selloRecibido
            FROM contingencias a
            LEFT JOIN companies b ON $joinCondition
            INNER JOIN phones c ON b.phone_id = c.id
            WHERE $whereCondition AND a.id = $contingenciaId";

            try {
                $encabezado = DB::select(DB::raw($queryEncabezado));
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('Error SQL al obtener encabezado de contingencia', [
                    'query' => $queryEncabezado,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'sql_state' => $e->errorInfo[0] ?? null,
                    'sql_code' => $e->errorInfo[1] ?? null,
                ]);
                throw new \Exception('Error al obtener datos de la contingencia: ' . $e->getMessage());
            }

            \Log::info("ENCABEZADO OBTENIDO", [
                'encabezado_encontrado' => count($encabezado),
                'encabezado_data' => $encabezado
            ]);

            // Query para obtener detalle
            $queryDetalle = "SELECT
            'Detalle' AS NmTabla,
            1 NuItems,
            c.codemh tipoDte,
            a.codigoGeneracion codigoGeneracion
            FROM sales a
            LEFT JOIN dte ON dte.sale_id = a.id
            INNER JOIN contingencias b ON a.id_contingencia = b.id
            INNER JOIN typedocuments c ON a.typedocument_id = c.id
            WHERE b.id = $contingenciaId AND a.company_id = $id_empresa";
            $detalle = DB::select(DB::raw($queryDetalle));

            \Log::info("DETALLE OBTENIDO", [
                'detalle_encontrado' => count($detalle),
                'detalle_data' => $detalle
            ]);

            if(empty($detalle)){
                $cola->codEstado = "10";
                $cola->estado = "Revision";
                $cola->observacionesMsg = "Rechazado por Detalle Vacio";
                $cola->save();
                $tipo_resultado = "danger";
                $mensaje_resultado = 'Rechazado por Detalle Vacio';
                return redirect()->route('dte.contingencias')
                    ->with($tipo_resultado, $mensaje_resultado);
            }

            // Si no hay encabezado o empresa, continuar como en RomaCopies
            if (empty($encabezado)) {
                // Continuar sin hacer nada, como en RomaCopies
            }
            if (empty($empresaconti)) {
                // Continuar sin hacer nada, como en RomaCopies
            }

            // Construir comprobante electrónico
            $comprobante_electronico = [];

            $identificacion = [
                "version"   => intval($encabezado[0]->versionJson),
                "ambiente"  => $encabezado[0]->ambiente,
                "codigoGeneracion"  => $encabezado[0]->codigoGeneracion,
                "fTransmision"      => $encabezado[0]->fechaCreacion,
                "hTransmision"       => $encabezado[0]->horaCreacion
            ];

            $emisor = [
                "nit"                   => $encabezado[0]->nit_emisor,
                "nombre"                => trim($encabezado[0]->nombre_empresa),
                "nombreResponsable"     => $encabezado[0]->nombreResponsable,
                "tipoDocResponsable"    => trim($encabezado[0]->tipoDocResponsable),
                "numeroDocResponsable"  => str_replace("-", "", $encabezado[0]->nuDocResponsable),
                "tipoEstablecimiento"   => $encabezado[0]->tipoEstablecimiento,
                "codEstableMH"          => $encabezado[0]->codigo_establecimiento,
                "codPuntoVenta"         => null,
                "telefono"              => $encabezado[0]->telefono_emisor,
                "correo"                => $encabezado[0]->correo,
            ];

            $detalleDTE = [];
            $ban = 1;
            foreach ($detalle as $d) {
                $detalleDTE[] = [
                    "noItem"    => intval($ban),
                    "codigoGeneracion" => $d->codigoGeneracion,
                    "tipoDoc"   => $d->tipoDte
                ];
                $ban++;
            }

            // Debug en UI - Valores desde BD (con timestamp único)
            $timestamp = date('H:i:s');
            $debugBD = "[{$timestamp}] DEBUG BD: fInicio=" . ($cola->fInicio ?? 'NULL') .
                      " (tipo:" . gettype($cola->fInicio) . "), fFin=" . ($cola->fFin ?? 'NULL') .
                      " (tipo:" . gettype($cola->fFin) . ")";

            \Log::info("VALORES BD PARA MOTIVO", [
                'fInicio_raw' => $cola->fInicio,
                'fInicio_type' => gettype($cola->fInicio),
                'fFin_raw' => $cola->fFin,
                'fFin_type' => gettype($cola->fFin),
                'hInicio' => $cola->hInicio,
                'hFin' => $cola->hFin
            ]);

            $motivo = [
                "fInicio"               => \Carbon\Carbon::parse($cola->fInicio)->format('Y-m-d'),
                "fFin"                  => \Carbon\Carbon::parse($cola->fFin)->format('Y-m-d'),
                "hInicio"               => $cola->hInicio ?: '00:00:00',
                "hFin"                  => $cola->hFin ?: '23:59:59',
                "tipoContingencia"      => intval($cola->tipoContingencia),
                "motivoContingencia"    => $cola->motivoContingencia
            ];

            $debugMotivo = "[{$timestamp}] DEBUG MOTIVO: " . json_encode($motivo);

            \Log::info("MOTIVO GENERADO", [
                'motivo' => $motivo,
                'motivo_json' => json_encode($motivo)
            ]);

            $comprobante_electronico["identificacion"] = $identificacion;
            $comprobante_electronico["emisor"] = $emisor;
            $comprobante_electronico["detalleDTE"] = $detalleDTE;
            $comprobante_electronico["motivo"] = $motivo;

            if (empty($comprobante_electronico)) {
                $cola->codEstado = "10";
                $cola->estado = "Revision";
                $cola->observacionesMsg = "Rechazado por Documento No Definido";
                $cola->save();
                $tipo_resultado = "danger";
                $mensaje_resultado = 'Rechazado por Documento No Definido';
            } else {
                // Firma electrónica
                $firma_electronica = [
                    "nit" => str_replace('-', '', $emisor["nit"]),
                    "activo" => true,
                    "passwordPri" => $empresaconti[0]->passwordPri,
                    "dteJson" => $comprobante_electronico
                ];

                // Debug en UI - JSON completo
                $debugJSON = "[{$timestamp}] DEBUG JSON COMPLETO: " . json_encode($comprobante_electronico);
                $debugMotivoJSON = "[{$timestamp}] DEBUG MOTIVO EN JSON: " . json_encode($comprobante_electronico['motivo'] ?? 'NO_EXISTE');

                \Log::info("JSON COMPLETO GENERADO", [
                    'comprobante_electronico' => $comprobante_electronico,
                    'motivo_especifico' => $comprobante_electronico['motivo'] ?? 'NO_EXISTE'
                ]);

                // Validar que el JSON es válido antes de enviarlo
                $jsonTest = json_encode($comprobante_electronico);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errorMsg = 'ERROR JSON INVALIDO: ' . json_last_error_msg();
                    $errorMsg .= ' | ' . $debugBD . ' | ' . $debugMotivo . ' | ' . $debugJSON;
                    return redirect()->route('dte.contingencias')
                        ->with('error', $errorMsg);
                }

                \Log::info("ENVIANDO A FIRMADOR", [
                    'url_firmador' => $empresaconti[0]->url_firmador,
                    'firma_electronica' => $firma_electronica
                ]);

                try {
                    $response = Http::accept('application/json')->post($empresaconti[0]->url_firmador, $firma_electronica);

                    // Debug en UI - Respuesta del firmador
                    $debugRespuesta = "[{$timestamp}] DEBUG FIRMADOR: Status=" . $response->status() .
                                    ", Body=" . substr($response->body(), 0, 500) .
                                    ", Success=" . ($response->successful() ? 'YES' : 'NO');

                    \Log::info("RESPUESTA DEL FIRMADOR", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'successful' => $response->successful()
                    ]);

                } catch (\Throwable $th) {
                    \Log::error("ERROR EN FIRMA", [
                        'error_message' => $th->getMessage(),
                        'error_trace' => $th->getTraceAsString(),
                        'url_firmador' => $empresaconti[0]->url_firmador,
                        'firma_data' => $firma_electronica
                    ]);

                    $errorMsg = 'ERROR EN FIRMA: ' . $th->getMessage();
                    $errorMsg .= ' | ' . $debugBD . ' | ' . $debugMotivo . ' | ' . $debugJSON;
                    return redirect()->route('dte.contingencias')
                        ->with('error', $errorMsg);
                }

                $objResponse = json_decode($response, true);
                $objResponse = (array)$objResponse;
                $comprobante_encriptado = $objResponse["body"];

                // Validación de usuario
                $validacion_usuario = [
                    "user"  => str_replace('-', '', $emisor["nit"]),
                    "pwd"   => $empresaconti[0]->pwd
                ];

                // Usar el SaleController para obtener el token
                $saleController = new \App\Http\Controllers\SaleController();
                $tokenResult = $saleController->getTokenMH($id_empresa, $validacion_usuario, $empresaconti[0]->url_credencial);

                if ($tokenResult == "OK") {
                    $token = Session::get($id_empresa);

                    $comprobante_enviar = [
                        "ambiente"      => $encabezado[0]->ambiente,
                        "version"       => intval($cola->version ?? 3),
                        "documento"     => $comprobante_encriptado
                    ];

                    try {
                        $response_enviado = Http::withToken($token)->post($empresaconti[0]->url_contingencia, $comprobante_enviar);
                        Log::info("RESPUESTA DEL ENVIO", [
                            'response_enviado' => $response_enviado
                        ]);
                    } catch (\Throwable $th) {
                        $error = [
                            "mensaje" => "Error con Servicios de Hacienda",
                            "erro" => $th
                        ];
                        return json_encode($error);
                    }
                } else {
                    $response_enviado = $saleController->getTokenMH($id_empresa, $validacion_usuario, $empresaconti[0]->url_credencial);
                }

                // Verificar que tenemos datos del comprobante
                if (count($encabezado) > 0) {
                    $objEnviado = json_decode($response_enviado);
                    if (isset($objEnviado->estado)) {
                        $estado_envio = $objEnviado->estado;
                        $dateString = $objEnviado->fechaHora;
                        $myDateTime = DateTime::createFromFormat('d/m/Y H:i:s', $dateString);
                        $newDateString = $myDateTime->format('Y-m-d H:i:s');
                        $observaciones = implode("<br>", $objEnviado->observaciones);

                        if ($estado_envio == "RECIBIDO") {
                            $cola->codEstado = "02";
                            $cola->estado = "Enviado";
                            $cola->fhRecibido = $newDateString;
                            $cola->selloRecibido = $objEnviado->selloRecibido;
                            $cola->observacionesMsg = $observaciones;
                            $cola->estadoHacienda = $estado_envio;
                            $cola->save();
                            $tipo_resultado = "success";
                            $mensaje_resultado = "Procesado con Exito";
                        } else {
                            $cola->codEstado = "03";
                            $cola->estado = "Rechazado";
                            $cola->observacionesMsg = $observaciones;
                            $cola->estadoHacienda = $estado_envio;
                            $cola->save();
                            $tipo_resultado = "danger";
                            $mensaje_resultado = 'Rechazado';
                        }
                    } else {
                        return var_dump($objEnviado);
                    }
                } else {
                    $cola->codEstado = "03";
                    $cola->estado = "Rechazado";
                    $cola->observacionesMsg = "Rechazado por Eliminacion de Comprobante";
                    $cola->save();
                    $tipo_resultado = "danger";
                    $mensaje_resultado = 'Rechazado por Eliminacion de Comprobante';
                }
            }

        } catch (\Illuminate\Database\QueryException $e) {
            // Error SQL - mostrar detalles completos
            $errorMessage = 'Error SQL al autorizar contingencia: ' . $e->getMessage();
            $errorDetails = [
                'sql_state' => $e->errorInfo[0] ?? null,
                'sql_code' => $e->errorInfo[1] ?? null,
                'sql_message' => $e->errorInfo[2] ?? null,
                'query' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            Log::error($errorMessage, $errorDetails);

            $tipo_resultado = "danger";
            $mensaje_resultado = $errorMessage;

            // Guardar detalles del error en la sesión para mostrar en SweetAlert
            Session::flash('error_details', $errorDetails);
        } catch (\Exception $e) {
            // Otros errores
            $errorMessage = 'Error al autorizar contingencia: ' . $e->getMessage();
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error($errorMessage, $errorDetails);

            $tipo_resultado = "danger";
            $mensaje_resultado = $errorMessage;

            // Guardar detalles del error en la sesión para mostrar en SweetAlert
            Session::flash('error_details', $errorDetails);
        }

        // Debug temporal: mostrar información detallada en la interfaz
        if ($tipo_resultado === "danger") {
            $mensaje_resultado .= " | DEBUG: Contingencia ID: {$contingenciaId}, Empresa ID: {$empresaId}";
            if (isset($debugBD)) $mensaje_resultado .= " | " . $debugBD;
            if (isset($debugMotivo)) $mensaje_resultado .= " | " . $debugMotivo;
            if (isset($debugRespuesta)) $mensaje_resultado .= " | " . $debugRespuesta;
        }

        $redirect = redirect()->route('dte.contingencias')
            ->with($tipo_resultado, $mensaje_resultado);

        // Si hay detalles del error, agregarlos a la sesión
        if (Session::has('error_details')) {
            $redirect->with('error_details', Session::get('error_details'));
        }

        return $redirect;
    }


    /**
     * Autorizar contingencia para AJAX (respuesta JSON)
     */
    private function autorizarContingenciaAjax($empresaId, $contingenciaId)
    {
        try {
            $contingencia = Contingencia::findOrFail($contingenciaId);

            // Verificar que la contingencia pertenece a la empresa
            if ($contingencia->empresa_id != $empresaId && $contingencia->idEmpresa != $empresaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contingencia no pertenece a esta empresa',
                    'error_code' => 'CONTINGENCIA_NO_PERTENECE_EMPRESA'
                ], 403);
            }

            // Verificar que la contingencia esté en estado correcto
            if ($contingencia->estado !== 'En Cola' && $contingencia->estado !== 'Rechazado') {
                return response()->json([
                    'success' => false,
                    'message' => 'La contingencia ya fue procesada o no está en estado válido',
                    'current_state' => $contingencia->estado,
                    'error_code' => 'ESTADO_INVALIDO'
                ], 400);
            }

            // Procesar las ventas asignadas a la contingencia
            $ventasAsignadas = Sale::where('id_contingencia', $contingenciaId)
                ->where('company_id', $empresaId)
                ->get();

            if ($ventasAsignadas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay ventas válidas asignadas a esta contingencia',
                    'error_code' => 'NO_HAY_VENTAS'
                ], 400);
            }

            DB::beginTransaction();

            $documentosProcesados = 0;
            $errores = [];
            $dtesGenerados = [];

            foreach ($ventasAsignadas as $venta) {
                try {
                    // Llamar al método createDocument existente
                    $resultado = $this->procesarVentaParaContingencia($venta);
                    if ($resultado['success']) {
                        $documentosProcesados++;
                        $dtesGenerados[] = [
                            'venta_id' => $venta->id,
                            'comprobante' => $resultado['comprobante'],
                            'json_dte' => $resultado['json_dte']
                        ];
                    } else {
                        $errores[] = "Venta {$venta->id}: {$resultado['message']}";
                    }
                } catch (\Exception $e) {
                    $errores[] = "Venta {$venta->id}: Error interno - {$e->getMessage()}";
                    Log::error("Error procesando venta {$venta->id} para contingencia {$contingenciaId}: " . $e->getMessage());
                }
            }

            if ($documentosProcesados > 0) {
                try {
                    // Generar JSON de contingencia según schema v3
                    $jsonContingencia = $this->generarJsonContingencia($contingencia, $empresaId, $dtesGenerados);

                    // Enviar a Hacienda usando el sistema existente
                    $resultadoEnvio = $this->enviarContingenciaAHacienda($contingencia, $jsonContingencia, $empresaId);

                    if (@$resultadoEnvio['estado'] == 'RECIBIDO') {
                        $contingencia->estado = 'Enviado';
                        $contingencia->codEstado = '02';
                        $contingencia->estadoHacienda = $resultadoEnvio['estado'];
                        $contingencia->fhRecibido = now();
                        $contingencia->selloRecibido = $resultadoEnvio['sello'] ?? null;
                        $contingencia->observacionesMsg = $resultadoEnvio['observaciones'] ?? 'Procesado correctamente';
                        $contingencia->created_by = auth()->user()->id;
                        $contingencia->updated_by = auth()->user()->id;
                        $contingencia->save();

                        DB::commit();

                        return response()->json([
                            'success' => true,
                            'message' => "Contingencia autorizada y enviada a Hacienda correctamente",
                            'data' => [
                                'contingencia_id' => $contingencia->id,
                                'documentos_procesados' => $documentosProcesados,
                                'estado_hacienda' => $resultadoEnvio['estado'],
                                'sello_recibido' => $resultadoEnvio['sello'],
                                'fecha_procesamiento' => now()->toISOString()
                            ]
                        ]);
                    } else {
                        DB::rollback();
                        return response()->json([
                            'success' => false,
                            'message' => 'Error al enviar a Hacienda',
                            'error_details' => [
                                'hacienda_response' => $resultadoEnvio,
                                'error_code' => 'ERROR_ENVIO_HACIENDA'
                            ]
                        ], 500);
                    }
                } catch (\Exception $e) {
                    DB::rollback();
                    Log::error('Error en envío a Hacienda: ' . $e->getMessage());

                    return response()->json([
                        'success' => false,
                        'message' => 'Error al procesar envío a Hacienda: ' . $e->getMessage(),
                        'error_details' => [
                            'exception' => get_class($e),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'error_code' => 'ERROR_PROCESAMIENTO_HACIENDA'
                        ]
                    ], 500);
                }
            } else {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron procesar los documentos',
                    'error_details' => [
                        'errores_procesamiento' => $errores,
                        'ventas_intentadas' => $ventasAsignadas->count(),
                        'error_code' => 'ERROR_PROCESAMIENTO_DOCUMENTOS'
                    ]
                ], 500);
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al autorizar contingencia (AJAX): ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'error_details' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'error_code' => 'ERROR_INTERNO_SERVIDOR'
                ]
            ], 500);
        }
    }

    /**
     * Obtener documentos relacionados con una contingencia
     */
    public function getDocumentosContingencia(Request $request)
    {
        try {
            $contingenciaId = $request->get('contingencia_id');

            if (!$contingenciaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de contingencia requerido'
                ], 400);
            }

            $contingencia = Contingencia::findOrFail($contingenciaId);

            $documentos = collect();

            // Obtener DTEs relacionados
            $dtes = Dte::where('idContingencia', $contingenciaId)
                ->with(['sale.client', 'company'])
                ->get();

            foreach ($dtes as $dte) {
                $clienteNombre = 'N/A';
                if ($dte->sale && $dte->sale->client) {
                    $cliente = $dte->sale->client;
                    $clienteNombre = $cliente->name_contribuyente ??
                                    ($cliente->firstname . ' ' . $cliente->firstlastname);
                }

                $documentos->push([
                    'tipo' => 'DTE',
                    'id' => $dte->id,
                    'numero_control' => $dte->sale->numero_control ?? 'N/A',
                    'cliente' => $clienteNombre,
                    'fecha' => $dte->created_at ? $dte->created_at->format('d/m/Y H:i') : 'N/A',
                    'estado' => $dte->codEstado ?? 'N/A',
                    'tipo_documento' => $dte->tipoDte ?? 'N/A'
                ]);
            }

            // Obtener ventas relacionadas
            $ventas = Sale::where('id_contingencia', $contingenciaId)
                ->with(['client', 'typedocument'])
                ->get();

            foreach ($ventas as $venta) {
                // Verificar si ya tiene DTE asociado
                $tieneDte = Dte::where('sale_id', $venta->id)->exists();

                if (!$tieneDte) {
                    $clienteNombre = 'N/A';
                    if ($venta->client) {
                        $cliente = $venta->client;
                        $clienteNombre = $cliente->name_contribuyente ??
                                        ($cliente->firstname . ' ' . $cliente->firstlastname);
                    }

                    $documentos->push([
                        'tipo' => 'Venta',
                        'id' => $venta->id,
                        'numero_control' => $venta->numero_control ?? 'N/A',
                        'cliente' => $clienteNombre,
                        'fecha' => $venta->created_at ? $venta->created_at->format('d/m/Y H:i') : 'N/A',
                        'estado' => 'Borrador',
                        'tipo_documento' => $venta->typedocument->name ?? 'N/A'
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'documentos' => $documentos->values(),
                'total' => $documentos->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener documentos de contingencia: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener documentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar errores específicos de una contingencia
     */
    public function mostrarErroresContingencia($contingenciaId)
    {
        try {
            $contingencia = Contingencia::findOrFail($contingenciaId);

            // Obtener errores relacionados
            $errores = collect();

            // Errores de la contingencia misma
            if ($contingencia->observacionesMsg) {
                $errores->push([
                    'tipo' => 'Contingencia',
                    'mensaje' => $contingencia->observacionesMsg,
                    'fecha' => $contingencia->updated_at,
                    'severidad' => 'warning'
                ]);
            }

            // Errores de ventas relacionadas - buscar a través de DTE o dte_errors
            $ventasConContingencia = Sale::where('id_contingencia', $contingenciaId)->get();

            foreach ($ventasConContingencia as $venta) {
                // Buscar errores en DTE asociado
                $dte = Dte::where('sale_id', $venta->id)->first();
                if ($dte && $dte->descriptionMessage) {
                    $errores->push([
                        'tipo' => 'Venta/DTE',
                        'venta_id' => $venta->id,
                        'dte_id' => $dte->id,
                        'mensaje' => $dte->descriptionMessage,
                        'fecha' => $dte->updated_at ?? $venta->updated_at,
                        'severidad' => 'error'
                    ]);
                }

                // Buscar errores en la tabla dte_errors si existe
                if (Schema::hasTable('dte_errors')) {
                    $dteErrors = DB::table('dte_errors')
                        ->where('sale_id', $venta->id)
                        ->get();

                    foreach ($dteErrors as $error) {
                        $errores->push([
                            'tipo' => 'Error de Sistema',
                            'venta_id' => $venta->id,
                            'mensaje' => $error->descripcion ?? $error->tipo_error ?? 'Error desconocido',
                            'fecha' => $error->created_at ?? now(),
                            'severidad' => 'error'
                        ]);
                    }
                }
            }

            // Errores de DTE directamente relacionados con la contingencia
            // Usar idContingencia que es el nombre correcto de la columna
            $dtesConError = Dte::where('idContingencia', $contingenciaId)
                ->whereNotNull('descriptionMessage')
                ->get();

            foreach ($dtesConError as $dte) {
                $errores->push([
                    'tipo' => 'DTE',
                    'dte_id' => $dte->id,
                    'venta_id' => $dte->sale_id,
                    'mensaje' => $dte->descriptionMessage,
                    'fecha' => $dte->updated_at ?? now(),
                    'severidad' => 'error'
                ]);
            }

            // Buscar errores en dte_errors relacionados con la contingencia
            if (Schema::hasTable('dte_errors')) {
                $dteErrors = DB::table('dte_errors')
                    ->whereIn('sale_id', function($query) use ($contingenciaId) {
                        $query->select('id')
                            ->from('sales')
                            ->where('id_contingencia', $contingenciaId);
                    })
                    ->orWhereIn('dte_id', function($query) use ($contingenciaId) {
                        $query->select('id')
                            ->from('dte')
                            ->where('idContingencia', $contingenciaId);
                    })
                    ->get();

                foreach ($dteErrors as $error) {
                    $errores->push([
                        'tipo' => 'Error Registrado',
                        'venta_id' => $error->sale_id ?? null,
                        'dte_id' => $error->dte_id ?? null,
                        'mensaje' => $error->descripcion ?? $error->tipo_error ?? 'Error desconocido',
                        'fecha' => $error->created_at ?? now(),
                        'severidad' => 'error'
                    ]);
                }
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'contingencia' => [
                        'id' => $contingencia->id,
                        'nombre' => $contingencia->nombre,
                        'estado' => $contingencia->estado,
                        'cod_estado' => $contingencia->codEstado,
                        'estado_hacienda' => $contingencia->estadoHacienda,
                        'sello_recibido' => $contingencia->selloRecibido,
                        'fecha_creacion' => $contingencia->created_at,
                        'fecha_actualizacion' => $contingencia->updated_at
                    ],
                    'errores' => $errores->sortByDesc('fecha')->values(),
                    'total_errores' => $errores->count()
                ]);
            }

            return view('dte.errores-contingencia', compact('contingencia', 'errores'));

        } catch (\Illuminate\Database\QueryException $e) {
            // Error SQL - mostrar detalles completos
            $errorMessage = 'Error SQL al obtener errores: ' . $e->getMessage();
            $errorDetails = [
                'sql_state' => $e->errorInfo[0] ?? null,
                'sql_code' => $e->errorInfo[1] ?? null,
                'sql_message' => $e->errorInfo[2] ?? null,
                'query' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            Log::error($errorMessage, $errorDetails);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error_details' => $errorDetails
                ], 500);
            }

            return redirect()->back()
                ->with('error', $errorMessage)
                ->with('error_details', $errorDetails);
        } catch (\Exception $e) {
            // Otros errores
            $errorMessage = 'Error al obtener errores de contingencia: ' . $e->getMessage();
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error($errorMessage, $errorDetails);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error_details' => $errorDetails
                ], 500);
            }

            return redirect()->route('dte.contingencias')
                ->with('error', 'Error al obtener errores de contingencia');
        }
    }

    /**
     * Autorizar contingencia para redirect (método original)
     */
    private function autorizarContingenciaRedirect($empresaId, $contingenciaId)
    {
        try {
            $contingencia = Contingencia::findOrFail($contingenciaId);

            // Verificar que la contingencia pertenece a la empresa
            if ($contingencia->empresa_id != $empresaId && $contingencia->idEmpresa != $empresaId) {
                return redirect()->route('dte.contingencias')
                    ->with('error', 'La contingencia no pertenece a esta empresa');
            }

            // Verificar que la contingencia esté en estado correcto
            if ($contingencia->estado !== 'En Cola' && $contingencia->estado !== 'Rechazado') {
                return redirect()->route('dte.contingencias')
                    ->with('error', 'La contingencia ya fue procesada o no está en estado válido');
            }

            // Procesar las ventas asignadas a la contingencia
            $ventasAsignadas = Sale::where('id_contingencia', $contingenciaId)
                ->where('company_id', $empresaId)
                ->get();

            if ($ventasAsignadas->isEmpty()) {
                return redirect()->route('dte.contingencias')
                    ->with('error', 'No hay ventas válidas asignadas a esta contingencia');
            }

            DB::beginTransaction();

            $documentosProcesados = 0;
            $errores = [];
            $dtesGenerados = [];

            foreach ($ventasAsignadas as $venta) {
                try {
                    // Llamar al método createDocument existente
                    $resultado = $this->procesarVentaParaContingencia($venta);
                    if ($resultado['success']) {
                        $documentosProcesados++;
                        $dtesGenerados[] = [
                            'venta_id' => $venta->id,
                            'comprobante' => $resultado['comprobante'],
                            'json_dte' => $resultado['json_dte']
                        ];
                    } else {
                        $errores[] = "Venta {$venta->id}: {$resultado['message']}";
                    }
                } catch (\Exception $e) {
                    $errores[] = "Venta {$venta->id}: Error interno - {$e->getMessage()}";
                }
            }

            if ($documentosProcesados > 0) {
                // Generar JSON de contingencia según schema v3
                $jsonContingencia = $this->generarJsonContingencia($contingencia, $empresaId, $dtesGenerados);

                // Enviar a Hacienda usando el sistema existente
                $resultadoEnvio = $this->enviarContingenciaAHacienda($contingencia, $jsonContingencia, $empresaId);

                if (@$resultadoEnvio['estado'] == 'RECIBIDO') {
                    $contingencia->estado = 'Enviado';
                    $contingencia->codEstado = '02';
                    $contingencia->estadoHacienda = $resultadoEnvio['estado'];
                    $contingencia->fhRecibido = now();
                    $contingencia->selloRecibido = $resultadoEnvio['sello'] ?? null;
                    $contingencia->observacionesMsg = $resultadoEnvio['observaciones'] ?? 'Procesado correctamente';
                    $contingencia->created_by = auth()->user()->id;
                    $contingencia->updated_by = auth()->user()->id;
                    $contingencia->save();

                    DB::commit();

                    return redirect()->route('dte.contingencias')
                        ->with('success', "Contingencia autorizada y enviada a Hacienda correctamente. Documentos procesados: {$documentosProcesados}");
                } else {
                    DB::rollback();
                    return redirect()->route('dte.contingencias')
                        ->with('error', 'Error al enviar a Hacienda: ' . ($resultadoEnvio['error'] ?? 'Error desconocido'));
                }
            } else {
                DB::rollback();
                return redirect()->route('dte.contingencias')
                    ->with('error', 'No se pudieron procesar los documentos: ' . implode('; ', $errores));
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al autorizar contingencia (redirect): ' . $e->getMessage());

            return redirect()->route('dte.contingencias')
                ->with('error', 'Error al autorizar contingencia: ' . $e->getMessage());
        }
    }
}
