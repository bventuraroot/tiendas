@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
<link rel="stylesheet"
    href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script src="{{ asset('assets/js/tables-datatables-advanced.js') }}"></script>
@endsection

@section('title', 'Reporte de Ventas por Clientes')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Ventas por Clientes
</h4>

<!-- Filtros de búsqueda -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.sales-by-client-search') }}" id="searchForm" target="_blank">
            @csrf
            <div class="row">
                <div class="col-md-2">
                    <label for="company" class="form-label">Empresa</label>
                    <select class="form-select" name="company" id="company" required>
                        <option value="">Seleccionar empresa</option>
                        @isset($companies)
                            @foreach($companies as $c)
                                <option selected value="{{ $c->id }}" {{ (isset($heading) && $heading->id==$c->id) ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date_range" class="form-label">Rango de Fechas</label>
                    <input type="text" class="form-control" id="date_range" name="date_range" placeholder="Seleccionar rango de fechas">
                    <small class="form-text text-muted">Selecciona un rango para múltiples meses</small>
                </div>
                <div class="col-md-2">
                    <label for="year" class="form-label">Año (alternativo)</label>
                    <select class="form-select" name="year" id="year">
                        <option value="">Todos</option>
                        @php
                            $currentYear = date('Y');
                            for ($i = 0; $i < 5; $i++) {
                                $year = $currentYear - $i;
                                $selected = (isset($yearB) && $yearB == $year) ? 'selected' : '';
                                echo "<option value='{$year}' {$selected}>{$year}</option>";
                            }
                        @endphp
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="period" class="form-label">Mes (alternativo)</label>
                    <select class="form-select" name="period" id="period">
                        <option value="">Todos</option>
                        <option value="01" {{ (isset($period) && $period == '01') ? 'selected' : '' }}>Enero</option>
                        <option value="02" {{ (isset($period) && $period == '02') ? 'selected' : '' }}>Febrero</option>
                        <option value="03" {{ (isset($period) && $period == '03') ? 'selected' : '' }}>Marzo</option>
                        <option value="04" {{ (isset($period) && $period == '04') ? 'selected' : '' }}>Abril</option>
                        <option value="05" {{ (isset($period) && $period == '05') ? 'selected' : '' }}>Mayo</option>
                        <option value="06" {{ (isset($period) && $period == '06') ? 'selected' : '' }}>Junio</option>
                        <option value="07" {{ (isset($period) && $period == '07') ? 'selected' : '' }}>Julio</option>
                        <option value="08" {{ (isset($period) && $period == '08') ? 'selected' : '' }}>Agosto</option>
                        <option value="09" {{ (isset($period) && $period == '09') ? 'selected' : '' }}>Septiembre</option>
                        <option value="10" {{ (isset($period) && $period == '10') ? 'selected' : '' }}>Octubre</option>
                        <option value="11" {{ (isset($period) && $period == '11') ? 'selected' : '' }}>Noviembre</option>
                        <option value="12" {{ (isset($period) && $period == '12') ? 'selected' : '' }}>Diciembre</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="client_id" class="form-label">Cliente Específico</label>
                    <select class="form-select select2client" name="client_id" id="client_id">
                        <option value="">Todos los clientes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
// Script inline mínimo para asegurar carga inicial de clientes con la primera empresa
document.addEventListener('DOMContentLoaded', function(){
  try {
    var companySel = document.getElementById('company');
    var clientSel = document.getElementById('client_id');
    if (!companySel || !clientSel) return;

    async function loadClientsBasic(companyId){
      if (!companyId) { clientSel.innerHTML = '<option value="">Todos los clientes</option>'; return; }
      var url = '{{ route('sale.clients') }}' + '?company_id=' + encodeURIComponent(companyId) + '&document_type=6';
      try {
        var r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        var data = [];
        if (r.ok) { data = await r.json(); }
        if (!Array.isArray(data) || data.length === 0) {
          // Fallback legacy
          var r2 = await fetch('/client/getclientbycompany/' + btoa(String(companyId)));
          if (r2.ok) { data = await r2.json(); }
        }
        var opts = '<option value="">Todos los clientes</option>';
        (data || []).forEach(function(c){
          var label = (c.search_display_text || c.name_format_label || c.comercial_name || ((c.firstname||'') + ' ' + (c.firstlastname||''))).trim();
          opts += '<option value="'+ c.id +'">'+ label + (c.nit? ' | '+c.nit : '') + (c.ncr? ' | '+c.ncr : '') +'</option>';
        });
        clientSel.innerHTML = opts;
      } catch(e) { /* silencioso */ }
    }

    // Seleccionar primera empresa si no hay selección y cargar clientes
    var selected = companySel.value;
    if (!selected) {
      var firstOption = Array.prototype.find.call(companySel.options, function(op){ return op.value && op.value !== ''; });
      if (firstOption) {
        companySel.value = firstOption.value;
        selected = firstOption.value;
      }
    }
    if (selected) { loadClientsBasic(selected); }

    // También cargar al cambiar manualmente
    companySel.addEventListener('change', function(e){ loadClientsBasic(e.target.value); });

    // Inicializar Select2 para clientes si jQuery está disponible
    if (typeof $ !== 'undefined' && $.fn.select2) {
      setTimeout(function() {
        $('#client_id').select2({
          placeholder: 'Todos los clientes',
          allowClear: true,
          width: '100%',
          minimumResultsForSearch: 0,
          language: {
            noResults: function() { return "No se encontraron clientes"; },
            searching: function() { return "Buscando..."; }
          },
          matcher: function(params, data) {
            if ($.trim(params.term) === '') { return data; }
            if (typeof data.text === 'undefined') { return null; }

            function normalize(str) {
              return (str || '').toString().toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[-\s]/g, '');
            }

            const term = normalize(params.term);
            const text = normalize(data.text);

            if (text.indexOf(term) > -1) { return data; }
            return null;
          }
        });
        console.log('Select2 inicializado desde script inline');
      }, 200);
    }
  } catch(e) { /* silencioso */ }

  // Inicializar Flatpickr para rango de fechas
  setTimeout(function() {
    flatpickr("#date_range", {
      mode: "range",
      dateFormat: "Y-m-d",
      locale: "es",
      allowInput: true,
      placeholder: "Seleccionar rango de fechas",
      onReady: function(selectedDates, dateStr, instance) {
        // Limpiar selects cuando se selecciona rango
        document.getElementById('year').value = '';
        document.getElementById('period').value = '';
      },
      onChange: function(selectedDates, dateStr, instance) {
        if (selectedDates.length === 2) {
          // Limpiar selects cuando se selecciona rango
          document.getElementById('year').value = '';
          document.getElementById('period').value = '';
        }
      }
    });

    // Limpiar rango cuando se selecciona año/mes
    document.getElementById('year').addEventListener('change', function() {
      if (this.value) {
        document.getElementById('date_range').value = '';
        flatpickr("#date_range").clear();
      }
    });

    document.getElementById('period').addEventListener('change', function() {
      if (this.value) {
        document.getElementById('date_range').value = '';
        flatpickr("#date_range").clear();
      }
    });
  }, 500);
});

// Funciones globales para el reporte de ventas por clientes
window.showClientDetails = function(clientId) {
    // Crear formulario temporal para enviar datos
    let form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('report.sales-by-client-search') }}';

    // Agregar token CSRF
    let csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Agregar parámetros
    let companyInput = document.createElement('input');
    companyInput.type = 'hidden';
    companyInput.name = 'company';
    companyInput.value = document.getElementById('company').value;
    form.appendChild(companyInput);

    let yearInput = document.createElement('input');
    yearInput.type = 'hidden';
    yearInput.name = 'year';
    yearInput.value = document.getElementById('year').value;
    form.appendChild(yearInput);

    let periodInput = document.createElement('input');
    periodInput.type = 'hidden';
    periodInput.name = 'period';
    periodInput.value = document.getElementById('period').value;
    form.appendChild(periodInput);

    let clientInput = document.createElement('input');
    clientInput.type = 'hidden';
    clientInput.name = 'client_id';
    clientInput.value = clientId;
    form.appendChild(clientInput);

    let detailsInput = document.createElement('input');
    detailsInput.type = 'hidden';
    detailsInput.name = 'show_details';
    detailsInput.value = '1';
    form.appendChild(detailsInput);

    // Enviar formulario
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

window.exportToExcel = function() {
    // Crear formulario temporal para exportar
    let form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('report.sales-by-client-search') }}';
    form.target = '_blank';

    // Agregar token CSRF
    let csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Agregar parámetros
    let companyInput = document.createElement('input');
    companyInput.type = 'hidden';
    companyInput.name = 'company';
    companyInput.value = document.getElementById('company').value;
    form.appendChild(companyInput);

    let yearInput = document.createElement('input');
    yearInput.type = 'hidden';
    yearInput.name = 'year';
    yearInput.value = document.getElementById('year').value;
    form.appendChild(yearInput);

    let periodInput = document.createElement('input');
    periodInput.type = 'hidden';
    periodInput.name = 'period';
    periodInput.value = document.getElementById('period').value;
    form.appendChild(periodInput);

    let clientInput = document.createElement('input');
    clientInput.type = 'hidden';
    clientInput.name = 'client_id';
    clientInput.value = document.getElementById('client_id').value;
    form.appendChild(clientInput);

    let exportInput = document.createElement('input');
    exportInput.type = 'hidden';
    exportInput.name = 'export_excel';
    exportInput.value = '1';
    form.appendChild(exportInput);

    // Enviar formulario
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

window.exportToPDF = function() {
    // Crear formulario temporal para exportar
    let form = document.createElement('form');
    form.method = 'GET';
    form.action = '{{ route('report.sales-by-client-pdf') }}';
    form.target = '_blank';

    // Agregar parámetros
    let companyInput = document.createElement('input');
    companyInput.type = 'hidden';
    companyInput.name = 'company';
    companyInput.value = document.getElementById('company').value;
    form.appendChild(companyInput);

    let yearInput = document.createElement('input');
    yearInput.type = 'hidden';
    yearInput.name = 'year';
    yearInput.value = document.getElementById('year').value;
    form.appendChild(yearInput);

    let periodInput = document.createElement('input');
    periodInput.type = 'hidden';
    periodInput.name = 'period';
    periodInput.value = document.getElementById('period').value;
    form.appendChild(periodInput);

    let clientInput = document.createElement('input');
    clientInput.type = 'hidden';
    clientInput.name = 'client_id';
    clientInput.value = document.getElementById('client_id').value;
    form.appendChild(clientInput);

    // Enviar formulario
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

window.downloadPDF = function() {
    // Crear formulario temporal para descargar
    let form = document.createElement('form');
    form.method = 'GET';
    form.action = '{{ route('report.sales-by-client-pdf') }}';

    // Agregar parámetros
    let companyInput = document.createElement('input');
    companyInput.type = 'hidden';
    companyInput.name = 'company';
    companyInput.value = document.getElementById('company').value;
    form.appendChild(companyInput);

    let yearInput = document.createElement('input');
    yearInput.type = 'hidden';
    yearInput.name = 'year';
    yearInput.value = document.getElementById('year').value;
    form.appendChild(yearInput);

    let periodInput = document.createElement('input');
    periodInput.type = 'hidden';
    periodInput.name = 'period';
    periodInput.value = document.getElementById('period').value;
    form.appendChild(periodInput);

    let clientInput = document.createElement('input');
    clientInput.type = 'hidden';
    clientInput.name = 'client_id';
    clientInput.value = document.getElementById('client_id').value;
    form.appendChild(clientInput);

    let downloadInput = document.createElement('input');
    downloadInput.type = 'hidden';
    downloadInput.name = 'download';
    downloadInput.value = '1';
    form.appendChild(downloadInput);

    // Enviar formulario
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};
</script>

<!-- Resultados del reporte -->
@if(isset($salesByClient) && $salesByClient->count() > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 card-title">
            Resultados del Reporte
            @if(isset($heading))
                - {{ $heading->name }}
            @endif
        </h5>
        <div>
            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                    <i class="fas fa-eye"></i> Ver PDF
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="downloadPDF()">
                    <i class="fas fa-download"></i> Descargar
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Resumen estadístico -->
        <div class="mb-4 row">
            <div class="col-md-4">
                <div class="text-white card bg-success">
                    <div class="card-body">
                        <h6 class="card-title">Total Ventas</h6>
                        <h3 class="mb-0">{{ number_format($salesByClient->sum('total_sales')) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-white card bg-info">
                    <div class="card-body">
                        <h6 class="card-title">Monto Total</h6>
                        <h3 class="mb-0">${{ number_format($salesByClient->sum('total_amount'), 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-white card bg-warning">
                    <div class="card-body">
                        <h6 class="card-title">Promedio por Cliente</h6>
                        <h3 class="mb-0">${{ number_format($salesByClient->avg('total_amount'), 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="salesByClientTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>NIT</th>
                        <th>Total Ventas</th>
                        <th>Ventas Completadas</th>
                        <th>Ventas Canceladas</th>
                        <th>Monto Total</th>
                        <th>Promedio por Venta</th>
                        <th>Primera Venta</th>
                        <th>Última Venta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    @foreach($salesByClient as $client)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td>
                            @if($client->tpersona == 'J')
                                <strong>{{ $client->comercial_name }}</strong>
                            @else
                                <strong>{{ $client->firstname }} {{ $client->firstlastname }}</strong>
                            @endif
                            @if($client->email)
                                <br><small class="text-muted">{{ $client->email }}</small>
                            @endif
                        </td>
                        <td>
                            @if($client->tpersona == 'J')
                                <span class="badge bg-primary">Jurídica</span>
                            @else
                                <span class="badge bg-info">Natural</span>
                            @endif
                        </td>
                        <td>{{ $client->nit ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $client->total_sales }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success">{{ $client->completed_sales }}</span>
                        </td>
                        <td>
                            <span class="badge bg-danger">{{ $client->cancelled_sales }}</span>
                        </td>
                        <td>
                            <strong>${{ number_format($client->total_amount, 2) }}</strong>
                        </td>
                        <td>
                            ${{ number_format($client->average_amount, 2) }}
                        </td>
                        <td>
                            @if($client->first_sale_date)
                                {{ \Carbon\Carbon::parse($client->first_sale_date)->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>
                            @if($client->last_sale_date)
                                {{ \Carbon\Carbon::parse($client->last_sale_date)->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="showClientDetails({{ $client->client_id }})">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para detalles del cliente -->
<div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientDetailsModalLabel">Detalles de Ventas del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="clientDetailsContent">
                <!-- El contenido se cargará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@elseif(isset($salesByClient))
<div class="card">
    <div class="text-center card-body">
        <i class="mb-3 fas fa-search fa-3x text-muted"></i>
        <h5>No se encontraron resultados</h5>
        <p class="text-muted">No hay ventas registradas para los filtros seleccionados.</p>
    </div>
</div>
@endif
@endsection

@section('page-script')
@parent
<script>
// Forzar inicialización de Select2 después de que jQuery esté listo
$(document).ready(function() {
    console.log('Iniciando configuración de Select2...');
    // Inicializar Select2 en combos (antes de enlazar eventos)
    $('#company').select2({
        placeholder: 'Seleccionar empresa',
        width: '100%',
        minimumResultsForSearch: 0,
        language: {
            noResults: function() { return "No se encontraron resultados"; },
            searching: function() { return "Buscando..."; }
        }
    });

    // Inicializar Select2 para clientes con configuración robusta
    setTimeout(function() {
        console.log('Inicializando Select2 para clientes...');
        $('#client_id').select2({
            placeholder: 'Todos los clientes',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            language: {
                noResults: function() { return "No se encontraron clientes"; },
                searching: function() { return "Buscando..."; }
            },
            matcher: function(params, data) {
                if ($.trim(params.term) === '') { return data; }
                if (typeof data.text === 'undefined') { return null; }

                // Normalizar texto para búsqueda
                function normalize(str) {
                    return (str || '').toString().toLowerCase()
                        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                        .replace(/[-\s]/g, '');
                }

                const term = normalize(params.term);
                const text = normalize(data.text);

                if (text.indexOf(term) > -1) { return data; }
                return null;
            }
        });
        console.log('Select2 para clientes inicializado');
    }, 100);

    // Enlazar eventos para cambios (nativo y select2)
    $('#company').on('change', function() {
        console.log('Evento change company ->', $(this).val());
        loadClients($(this).val());
    }).on('select2:select', function(e) {
        const val = e.params.data.id;
        console.log('Evento select2:select company ->', val);
        loadClients(val);
    });

    // Si no hay empresas embebidas (fallback), intentar cargarlas
    if ($('#company option').length <= 1) {
        console.log('No hay empresas embebidas, se cargarán por AJAX');
        loadCompanies();
    } else {
        // Seleccionar la primera empresa disponible si no hay selección
        let selectedCompany = $('#company').val();
        if (!selectedCompany) {
            const firstVal = $('#company option[value!=""]').first().val();
            if (firstVal) {
                $('#company').val(firstVal).trigger('change');
                selectedCompany = firstVal;
            }
        }
        console.log('Empresa inicial seleccionada ->', selectedCompany);
        if (selectedCompany) {
            loadClients(selectedCompany);
        }
    }

    // Preseleccionar año y mes actuales en los selects si están vacíos
    (function setDefaultYearMonth(){
        const now = new Date();
        const y = String(now.getFullYear());
        const m = String(now.getMonth()+1).padStart(2,'0');
        if (!$('#year').val()) $('#year').val(y);
        if (!$('#period').val()) $('#period').val(m);
    })();

    // Inicializar DataTable
    if ($('#salesByClientTable').length) {
        $('#salesByClientTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[7, 'desc']], // Ordenar por monto total descendente
            pageLength: 25
        });
    }
});

function loadCompanies() {
    console.log('Iniciando carga de empresas...');
    $.ajax({
        url: '{{ route('company.getcompanies') }}',
        method: 'GET',
        success: function(response) {
            console.log('Empresas cargadas exitosamente:', response);
            let options = '<option value="">Seleccionar empresa</option>';
            if (response && response.length > 0) {
                response.forEach(function(company) {
                    let selected = '{{ isset($heading) ? $heading->id : "" }}' == company.id ? 'selected' : '';
                    options += `<option value="${company.id}" ${selected}>${company.name}</option>`;
                });
            } else {
                console.warn('No se encontraron empresas en la respuesta');
            }
            $('#company').html(options);
            console.log('Opciones de empresas actualizadas en el select');

            // Tomar empresa seleccionada o, si no hay, la primera disponible
            let selectedCompany = $('#company').val();
            if (!selectedCompany) {
                const firstVal = $('#company option[value!=""]').first().val();
                if (firstVal) {
                    $('#company').val(firstVal).trigger('change');
                    selectedCompany = firstVal;
                }
            }
            if (selectedCompany) { loadClients(selectedCompany); }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar empresas:', xhr.status, xhr.statusText, error);
            console.error('Respuesta del servidor:', xhr.responseText);
        }
    });
}

function loadClients(companyId) {
    if (!companyId) {
        $('#client_id').html('<option value="">Todos los clientes</option>');
        $('#client_id').val(null).trigger('change');
        return;
    }

    const url = '{{ route('sale.clients') }}' + '?company_id=' + encodeURIComponent(companyId) + '&document_type=6';

    console.log('Cargando clientes (patrón ventas dinámicas):', companyId, 'URL:', url);

    $.ajax({
        url: url,
        method: 'GET',
        success: function(response) {
            console.log('Clientes recibidos:', response);
            let options = '<option value="">Todos los clientes</option>';
            response.forEach(function(client) {
                const label = client.name_format_label || (client.comercial_name || (client.firstname + ' ' + (client.firstlastname || '')));
                const selected = '{{ isset($client_id) ? $client_id : "" }}' == client.id ? 'selected' : '';
                options += `<option value="${client.id}" ${selected}>${label}${client.nit ? ' | ' + client.nit : ''}${client.ncr ? ' | ' + client.ncr : ''}</option>`;
            });
            $('#client_id').html(options);

            // Fallback: si no hay clientes, intentar endpoint legacy por compatibilidad
            if (!response || response.length === 0) {
                const legacyUrl = '/client/getclientbycompany/' + btoa(String(companyId));
                console.warn('Sin clientes via sale.clients, probando endpoint legacy:', legacyUrl);
                $.ajax({
                    url: legacyUrl,
                    method: 'GET',
                    success: function(resp2) {
                        let opts2 = '<option value="">Todos los clientes</option>';
                        (resp2 || []).forEach(function(client) {
                            const label = client.search_display_text || (client.name_format_label || (client.comercial_name || (client.firstname + ' ' + (client.firstlastname || ''))));
                            opts2 += `<option value="${client.id}">${label}</option>`;
                        });
                        $('#client_id').html(opts2);
                        $('#client_id').trigger('change.select2');
                    },
                    error: function() {
                        console.error('Fallback legacy también falló');
                    }
                });
            }

            // Destruir y reinicializar Select2
            // Reinicializar Select2 después de cargar clientes
            setTimeout(function() {
                try {
                    $('#client_id').select2('destroy');
                } catch(e) {
                    console.log('Select2 no estaba inicializado');
                }

                $('#client_id').select2({
                    placeholder: 'Todos los clientes',
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 0,
                    language: {
                        noResults: function() { return "No se encontraron clientes"; },
                        searching: function() { return "Buscando..."; }
                    },
                    matcher: function(params, data) {
                        if ($.trim(params.term) === '') { return data; }
                        if (typeof data.text === 'undefined') { return null; }

                        function normalize(str) {
                            return (str || '').toString().toLowerCase()
                                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                                .replace(/[-\s]/g, '');
                        }

                        const term = normalize(params.term);
                        const text = normalize(data.text);

                        if (text.indexOf(term) > -1) { return data; }
                        return null;
                    }
                });
                console.log('Select2 reinicializado después de cargar clientes');
            }, 50);

            console.log('Clientes cargados:', response.length);
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar clientes:', xhr.status, xhr.statusText, error);
            console.error('Respuesta:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error al cargar clientes',
                text: 'No se pudieron cargar los clientes de la empresa seleccionada'
            });
        }
    });
}

});
</script>
@endsection
