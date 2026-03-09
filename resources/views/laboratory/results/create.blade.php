@php
$configData = Helper::appClasses();
$exam = $orderExam->exam ?? null;
$valoresRef = ($exam && isset($exam->valores_referencia_especificos)) ? $exam->valores_referencia_especificos : null;
// isMultiParam, parametros, result, results, editResultId vienen del controlador
@endphp

@extends('layouts/layoutMaster')

@section('title', (($editResultId ?? (isset($result) && isset($result->id))) ? 'Editar' : 'Registrar') . ' Resultado - ' . $exam->nombre)

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.css')}}">
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1 fw-bold">{{ ($editResultId ?? (isset($result) && isset($result->id))) ? 'Editar' : 'Registrar' }} Resultado</h4>
                <p class="mb-0 text-muted">
                    <strong>Examen:</strong> {{ $exam->nombre }}<br>
                    <strong>Orden:</strong> <code>{{ $orderExam->order->numero_orden }}</code>
                </p>
            </div>
            <a href="/lab-orders/{{ $orderExam->order_id }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver a la Orden
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fa-solid fa-flask me-2"></i>Datos del Resultado</h6>
            </div>
            <div class="card-body">

                @if($isMultiParam)
                    <!-- Formulario multi-parámetro (varios resultados según catálogo) -->
                    <form id="formResultado">
                        @csrf
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <div class="alert alert-info">
                                    <i class="fa-solid fa-info-circle me-2"></i>
                                    <strong>Examen:</strong> {{ $exam->nombre }}<br>
                                    @if($exam->category)
                                    <strong>Categoría:</strong> {{ $exam->category->nombre }}
                                    @endif
                                </div>
                            </div>
                            @php
                                $unidadesLista = [
                                    'Unidades de Hormonas' => ['mUI/mL', 'mIU/mL', 'UI/mL', 'U/mL', 'UI/L', 'mUI/L'],
                                    'Unidades de Peso/Volumen' => ['ng/mL', 'ug/mL', 'pg/mL', 'mg/dL', 'g/dL', 'ug/dL', 'ng/dL'],
                                    'Unidades Molares' => ['mmol/L', 'umol/L', 'pmol/L', 'nmol/L'],
                                    'Unidades de Concentración' => ['g/L', 'mg/L', '%'],
                                    'Unidades de Tiempo' => ['minutos', 'horas', 'días', 'segundos'],
                                    'Unidades Hematológicas' => ['cel/uL', 'x10³/uL', 'x10⁶/uL', 'mm/h'],
                                    'Otras Unidades' => ['mm', 'cm', 'L/min', 'mL/min', 'mL/Minuto', 'mL/24 Horas', 'UFC/mL'],
                                ];
                                $unidadesTodas = array_merge(...array_values($unidadesLista));
                            @endphp
                            @foreach($parametros as $p)
                            @php
                                $r = $results->get($p['label']) ?? $results->get($p['label_alt'] ?? '');
                                $est = $r ? ($r->estado_resultado ?? '') : '';
                                if (!in_array($est, ['normal', 'alto', 'bajo', 'critico'])) {
                                    $est = 'normal';
                                }
                                $valRef = $r ? ($r->valor_referencia ?? '') : '';
                                if ($valRef === '' && isset($p['valor_referencia']) && $p['valor_referencia'] !== '') {
                                    $valRef = $p['valor_referencia'];
                                } elseif ($valRef === '') {
                                    $valRef = $p['valor_referencia'] ?? '---';
                                }
                                $resVal = $r ? ($r->resultado ?? '') : '';
                                if ($resVal === '' && !empty($p['valor_default'])) {
                                    $resVal = $p['valor_default'];
                                }
                                $unidadActual = $r ? ($r->unidad_medida ?? '') : '';
                                if ($unidadActual === '' && !empty($p['unidad'])) {
                                    $unidadActual = $p['unidad'];
                                }
                                $ocultarValorRef = str_contains(strtolower($exam->nombre ?? ''), 'strout');
                                $isHecesCompleto = str_contains(strtolower($exam->nombre ?? ''), 'heces') && str_contains(strtolower($exam->nombre ?? ''), 'completo');
                                $isUrocultivo = str_contains(strtolower($exam->nombre ?? ''), 'urocultivo') || str_contains(strtolower($exam->nombre ?? ''), 'cultivo de orina');
                                $isCoprocultivo = str_contains(strtolower($exam->nombre ?? ''), 'coprocultivo');
                                $isTextarea = ($p['tipo'] ?? '') === 'textarea';
                                $textareaRows = $isHecesCompleto ? '4' : ($isUrocultivo ? '4' : ($isCoprocultivo ? '3' : '6'));
                                $textareaCols = $isTextarea ? ($isHecesCompleto ? 'col-md-6' : ($isUrocultivo ? 'col-md-6' : ($isCoprocultivo ? 'col-md-5' : ($ocultarValorRef ? 'col-md-8' : 'col-md-8')))) : ($ocultarValorRef ? 'col-md-5' : 'col-md-3');
                            @endphp
                            <div class="mb-3 {{ $textareaCols }}">
                                <label class="form-label">{{ $p['label'] }}@if($p['required'])<span class="text-danger"> *</span>@endif</label>
                                @if($isTextarea)
                                    <textarea class="form-control"
                                              name="resultado_{{ $p['param_key'] }}"
                                              id="resultado_{{ $p['param_key'] }}"
                                              rows="{{ $textareaRows }}"
                                              {{ $p['required'] ? 'required' : '' }}
                                              placeholder="{{ $p['placeholder'] ?? 'Ej: ' . $p['label'] }}">{{ $resVal }}</textarea>
                                @else
                                    <div class="input-group">
                                        <input type="{{ ($p['tipo'] ?? '') === 'number' ? 'number' : 'text' }}"
                                               step="{{ ($p['tipo'] ?? '') === 'number' ? '0.01' : '' }}"
                                               class="form-control"
                                               name="resultado_{{ $p['param_key'] }}"
                                               id="resultado_{{ $p['param_key'] }}"
                                               {{ $p['required'] ? 'required' : '' }}
                                               value="{{ $resVal }}"
                                               placeholder="{{ $p['placeholder'] ?? 'Ej: ' . $p['label'] }}">
                                        <select class="form-select" name="unidad_medida_{{ $p['param_key'] }}" id="unidad_medida_{{ $p['param_key'] }}" style="max-width: 160px;">
                                            <option value="">Sin unidad</option>
                                            @foreach($unidadesLista as $categoria => $unidades)
                                                <optgroup label="{{ $categoria }}">
                                                    @foreach($unidades as $unidad)
                                                        <option value="{{ $unidad }}" {{ $unidadActual === $unidad ? 'selected' : '' }}>{{ $unidad }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                            @if($unidadActual && !in_array($unidadActual, $unidadesTodas))
                                                <option value="{{ $unidadActual }}" selected>{{ $unidadActual }}</option>
                                            @endif
                                        </select>
                                    </div>
                                @endif
                            </div>
                            @if(!$ocultarValorRef)
                            @php
                                $refCols = $isTextarea ? ($isHecesCompleto ? 'col-md-3' : ($isUrocultivo ? 'col-md-3' : ($isCoprocultivo ? 'col-md-4' : 'col-md-2'))) : 'col-md-6';
                                $refRows = $isTextarea ? ($isHecesCompleto ? '2' : ($isUrocultivo ? '2' : ($isCoprocultivo ? '2' : '2'))) : '3';
                            @endphp
                            <div class="mb-3 {{ $refCols }}">
                                <label class="form-label">Valor ref.</label>
                                <textarea class="form-control" name="valor_referencia_{{ $p['param_key'] }}"
                                          id="valor_referencia_{{ $p['param_key'] }}"
                                          rows="{{ $refRows }}"
                                          placeholder="---">{{ $valRef }}</textarea>
                            </div>
                            @else
                            <input type="hidden" name="valor_referencia_{{ $p['param_key'] }}" value="">
                            @endif
                            <div class="mb-3 {{ $ocultarValorRef ? 'col-md-4' : 'col-md-3' }}">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado_resultado_{{ $p['param_key'] }}" id="estado_resultado_{{ $p['param_key'] }}" {{ $p['required'] ? 'required' : '' }}>
                                    <option value="normal" {{ $est === 'normal' ? 'selected' : '' }}>Normal</option>
                                    <option value="alto" {{ $est === 'alto' ? 'selected' : '' }}>Alto</option>
                                    <option value="bajo" {{ $est === 'bajo' ? 'selected' : '' }}>Bajo</option>
                                    <option value="critico" {{ $est === 'critico' ? 'selected' : '' }}>Crítico</option>
                                </select>
                            </div>
                            @endforeach
                            <div class="mb-3 col-md-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" id="observaciones" rows="2">{{ $results->isNotEmpty() ? ($results->first()->observaciones ?? '**DATOS CONTROLADOS**') : '**DATOS CONTROLADOS**' }}</textarea>
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-end">
                            <a href="/lab-orders/{{ $orderExam->order_id }}" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-1"></i>{{ $editResultId ? 'Actualizar' : 'Guardar' }} Resultados
                            </button>
                        </div>
                    </form>
                @else
                    <!-- Formulario genérico - Todo desde el catálogo de exámenes -->
                    <form id="formResultado">
                        @csrf
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <div class="alert alert-info">
                                    <i class="fa-solid fa-info-circle me-2"></i>
                                    <strong>Examen:</strong> {{ $exam->nombre }}<br>
                                    @if($exam->category)
                                    <strong>Categoría:</strong> {{ $exam->category->nombre }}
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">Resultado <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number"
                                           step="0.01"
                                           class="form-control"
                                           name="resultado"
                                           id="resultado"
                                           required
                                           value="{{ isset($result) ? $result->resultado : '' }}"
                                           placeholder="Ej: 5.5"
                                           min="0">
                                    @php
                                        $unidadActual = isset($result)
                                            ? ($result->unidad_medida ?? $exam->unidad_medida ?? '')
                                            : ($exam->unidad_medida ?? '');
                                        $unidadesLista = [
                                            'Unidades de Hormonas' => ['mUI/mL', 'mIU/mL', 'UI/mL', 'U/mL', 'UI/L', 'mUI/L'],
                                            'Unidades de Peso/Volumen' => ['ng/mL', 'ug/mL', 'pg/mL', 'mg/dL', 'g/dL', 'ug/dL', 'ng/dL'],
                                            'Unidades Molares' => ['mmol/L', 'umol/L', 'pmol/L', 'nmol/L'],
                                            'Unidades de Concentración' => ['g/L', 'mg/L', '%'],
                                            'Unidades de Tiempo' => ['minutos', 'horas', 'días', 'segundos'],
                                            'Unidades Hematológicas' => ['cel/uL', 'x10³/uL', 'x10⁶/uL', 'mm/h'],
                                            'Otras Unidades' => ['mm', 'cm', 'L/min', 'mL/min', 'mL/Minuto', 'mL/24 Horas', 'UFC/mL'],
                                        ];
                                        $unidadesTodas = array_merge(...array_values($unidadesLista));
                                    @endphp
                                    <select class="form-select" name="unidad_medida" id="unidad_medida" style="max-width: 180px;">
                                        <option value="">Sin unidad</option>
                                        @foreach($unidadesLista as $categoria => $unidades)
                                            <optgroup label="{{ $categoria }}">
                                                @foreach($unidades as $unidad)
                                                    <option value="{{ $unidad }}" {{ $unidadActual === $unidad ? 'selected' : '' }}>{{ $unidad }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                        @if($unidadActual && !in_array($unidadActual, $unidadesTodas))
                                            <option value="{{ $unidadActual }}" selected>{{ $unidadActual }}</option>
                                        @endif
                                    </select>
                                </div>
                                <small class="text-muted">Ingrese el valor del resultado</small>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">Estado del Resultado <span class="text-danger">*</span></label>
                                @php
                                    $estadoGeneric = isset($result) ? ($result->estado_resultado ?? '') : '';
                                    if (!in_array($estadoGeneric, ['normal', 'alto', 'bajo', 'critico'])) {
                                        $estadoGeneric = 'normal';
                                    }
                                @endphp
                                <select class="form-select" name="estado_resultado" id="estado_resultado" required>
                                    <option value="normal" {{ $estadoGeneric === 'normal' ? 'selected' : '' }}>Normal</option>
                                    <option value="alto" {{ $estadoGeneric === 'alto' ? 'selected' : '' }}>Alto</option>
                                    <option value="bajo" {{ $estadoGeneric === 'bajo' ? 'selected' : '' }}>Bajo</option>
                                    <option value="critico" {{ $estadoGeneric === 'critico' ? 'selected' : '' }}>Crítico</option>
                                </select>
                                <small class="text-muted">Seleccione el estado del resultado</small>
                            </div>

                            <div class="mb-3 col-md-12">
                                <label class="form-label">Valor de Referencia <span class="text-danger">*</span></label>
                                @php
                                    // Obtener valor de referencia del catálogo como default
                                    $defaultValorRef = '';
                                    if (isset($result) && $result->valor_referencia) {
                                        // Si hay un resultado guardado, usar ese
                                        $defaultValorRef = $result->valor_referencia;
                                    } elseif ($exam->valores_referencia) {
                                        // Si no, usar el del catálogo
                                        $defaultValorRef = $exam->valores_referencia;
                                    } elseif ($exam->valores_referencia_especificos) {
                                        // O desde valores_referencia_especificos
                                        if (isset($exam->valores_referencia_especificos['valores_referencia']['rango'])) {
                                            $defaultValorRef = $exam->valores_referencia_especificos['valores_referencia']['rango'];
                                        } elseif (isset($exam->valores_referencia_especificos['rango'])) {
                                            $defaultValorRef = $exam->valores_referencia_especificos['rango'];
                                        }
                                    }
                                @endphp
                                <textarea class="form-control"
                                          name="valor_referencia"
                                          id="valor_referencia"
                                          rows="4"
                                          required
                                          placeholder="Ej: Hombres: 0.8 a 8.6, Mujeres: 0.0 a 12.0...">{{ $defaultValorRef }}</textarea>
                                <small class="text-muted">Valor de referencia del catálogo cargado por defecto. Puede editarlo si es necesario. Este valor se mostrará en el PDF.</small>
                            </div>

                            <div class="mb-3 col-md-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control"
                                          name="observaciones"
                                          id="observaciones"
                                          rows="3"
                                          placeholder="Observaciones adicionales sobre el resultado">{{ isset($result) ? ($result->observaciones ?? '**DATOS CONTROLADOS**') : '**DATOS CONTROLADOS**' }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end">
                            <a href="/lab-orders/{{ $orderExam->order_id }}" class="btn btn-outline-secondary me-2">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-1"></i>{{ isset($result) ? 'Actualizar' : 'Guardar' }} Resultado
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Paciente:</strong><br>
                    {{ $orderExam->order->patient ? ($orderExam->order->patient->primer_nombre . ' ' . $orderExam->order->patient->primer_apellido) : 'N/A' }}
                </p>
                <p class="mb-2">
                    <strong>Médico:</strong><br>
                    {{ $orderExam->order->doctor ? ($orderExam->order->doctor->nombres . ' ' . $orderExam->order->doctor->apellidos) : 'Sin médico' }}
                </p>
                <p class="mb-2">
                    <strong>Tipo de Muestra:</strong><br>
                    {{ $exam->tipo_muestra }}
                </p>
                @if($exam->valores_referencia)
                <p class="mb-0">
                    <strong>Valores de Referencia:</strong><br>
                    <small>{{ $exam->valores_referencia }}</small>
                </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {

    // Envío del formulario
    $('#formResultado').on('submit', function(e) {
        e.preventDefault();

        // Validación del lado del cliente
        let isValid = true;
        const form = $(this);

        @if($isMultiParam ?? false)
        // Validación multi-parámetro
        const parametros = @json($parametros ?? []);
        parametros.forEach(function(p) {
            const pk = p.param_key;
            const r = $('#resultado_' + pk).val();
            if (p.required && (!r || String(r).trim() === '')) {
                $('#resultado_' + pk).addClass('is-invalid');
                isValid = false;
            } else {
                $('#resultado_' + pk).removeClass('is-invalid');
            }
            const est = $('#estado_resultado_' + pk).val();
            const needEst = p.required || (r && String(r).trim() !== '');
            if (needEst && (!est || est.trim() === '')) {
                $('#estado_resultado_' + pk).addClass('is-invalid');
                isValid = false;
            } else {
                $('#estado_resultado_' + pk).removeClass('is-invalid');
            }
        });
        @else
        // Validar resultado (formulario genérico)
        const resultado = $('#resultado').val();
        if (!resultado || resultado.trim() === '') {
            $('#resultado').addClass('is-invalid');
            isValid = false;
        } else {
            $('#resultado').removeClass('is-invalid');
        }

        // Validar valor de referencia
        const valorReferencia = $('#valor_referencia').val();
        if (!valorReferencia || valorReferencia.trim() === '') {
            $('#valor_referencia').addClass('is-invalid');
            isValid = false;
        } else {
            $('#valor_referencia').removeClass('is-invalid');
        }

        // Validar estado del resultado
        const estadoResultado = $('#estado_resultado').val();
        if (!estadoResultado || estadoResultado.trim() === '') {
            $('#estado_resultado').addClass('is-invalid');
            isValid = false;
        } else {
            $('#estado_resultado').removeClass('is-invalid');
        }
        @endif

        if (!isValid) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Por favor complete todos los campos requeridos.'
            });
            return;
        }

        const submitBtn = $(this).find('button[type="submit"]');
        @php
            $resultId = $editResultId ?? (isset($result) && isset($result->id) ? $result->id : null);
            $isEdit = !empty($resultId);
            $orderExamId = isset($orderExam) && isset($orderExam->id) ? $orderExam->id : null;
            $orderId = isset($orderExam) && isset($orderExam->order_id) ? $orderExam->order_id : null;
        @endphp
        const isEdit = {{ $isEdit ? 'true' : 'false' }};
        const isMultiParam = {{ ($isMultiParam ?? false) ? 'true' : 'false' }};
        const resultId = {{ $resultId ?? 'null' }};
        const orderExamId = {{ $orderExamId ?? 'null' }};
        const orderId = {{ $orderId ?? 'null' }};
        const btnText = (isEdit ? 'Actualizar' : 'Guardar') + (isMultiParam ? ' Resultados' : ' Resultado');
        const btnTextLoading = (isEdit ? 'Actualizando' : 'Guardando') + (isMultiParam ? ' resultados...' : '...');

        if (!orderExamId || orderExamId === 'null') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo identificar el examen. Por favor, recargue la página.'
            });
            submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>' + btnText);
            return;
        }

        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>' + btnTextLoading);

        const url = isEdit
            ? `/lab-results/${resultId}/update`
            : `/lab-results/${orderExamId}/store`;
        const method = isEdit ? 'PUT' : 'POST';
        const formData = $(this).serialize();
        const data = formData + (isEdit ? '&_method=PUT' : '');

        // Debug: verificar que estado_resultado esté presente
        console.log('Form data:', formData);
        console.log('Estado resultado:', $('#estado_resultado').val());
        console.log('URL:', url);
        console.log('Method:', method);
        console.log('Order Exam ID:', orderExamId);
        console.log('Order ID:', orderId);

        $.ajax({
            url: url,
            method: method,
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
            },
            success: function(response) {
                console.log('Success response:', response);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: isEdit ? '¡Resultado Actualizado!' : '¡Resultado Guardado!',
                        text: response.message,
                        confirmButtonText: 'Ver Orden'
                    }).then(() => {
                        window.location.href = `/lab-orders/${orderId}`;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al guardar el resultado'
                    });
                    submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>' + btnText);
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al guardar el resultado';
                console.error('Error en AJAX:', xhr);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseJSON);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let errorMessages = [];

                    for (let field in errors) {
                        if (errors[field]) {
                            errorMessages.push(errors[field][0]);
                        }
                    }

                    errorMsg = errorMessages.length > 0
                        ? errorMessages.join('<br>')
                        : 'Por favor, verifique los datos ingresados';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 500) {
                    errorMsg = 'Error interno del servidor. Por favor, contacte al administrador.';
                } else if (xhr.status === 404) {
                    errorMsg = 'No se encontró la ruta. Por favor, verifique la URL.';
                } else if (xhr.status === 403) {
                    errorMsg = 'No tiene permisos para realizar esta acción.';
                } else if (xhr.status === 0) {
                    errorMsg = 'Error de conexión. Por favor, verifique su conexión a internet.';
                } else {
                    errorMsg = `Error ${xhr.status}: ${xhr.statusText || 'Error desconocido'}`;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMsg,
                    footer: xhr.status ? `Código de error: ${xhr.status}` : ''
                });

                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>' + btnText);
            }
        });
    });
});
</script>
@endsection




