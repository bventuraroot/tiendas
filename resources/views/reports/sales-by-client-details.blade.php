@extends('layouts.app')

@section('title', 'Detalles de Ventas por Cliente')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-user"></i> Detalles de Ventas - {{ $heading->name }}
                    </h4>
                    <div>
                        <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-danger btn-sm" onclick="exportToPDF()">
                                <i class="fas fa-eye"></i> Ver PDF
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="downloadPDF()">
                                <i class="fas fa-download"></i> Descargar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($salesDetails) && $salesDetails->count() > 0)
                        <!-- Información del cliente -->
                        @php
                            $firstSale = $salesDetails->first();
                            $clientName = $firstSale->client_name;
                            $totalAmount = $salesDetails->sum('totalamount');
                            $totalSales = $salesDetails->count();
                            $completedSales = $salesDetails->where('state', 1)->count();
                            $cancelledSales = $salesDetails->where('state', 0)->count();
                        @endphp

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-user"></i> Cliente: {{ $clientName }}</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong>Período:</strong>
                                                @if($yearB && $period)
                                                    @php
                                                        $meses = [
                                                            '01' => 'Enero','02' => 'Febrero','03' => 'Marzo','04' => 'Abril',
                                                            '05' => 'Mayo','06' => 'Junio','07' => 'Julio','08' => 'Agosto',
                                                            '09' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre'
                                                        ];
                                                    @endphp
                                                    {{ $meses[$period] ?? $period }} {{ $yearB }}
                                                @elseif($yearB)
                                                    Año {{ $yearB }}
                                                @else
                                                    Todos los períodos
                                                @endif
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong>Total Ventas:</strong> {{ $totalSales }} |
                                                <strong>Monto Total:</strong> ${{ number_format($totalAmount, 2) }} |
                                                <strong>Promedio:</strong> ${{ number_format($totalSales ? ($totalAmount / $totalSales) : 0, 2) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen estadístico simple -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="text-white card bg-success">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Ventas</h6>
                                        <h4 class="mb-0">{{ $totalSales }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-white card bg-info">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Monto Total</h6>
                                        <h4 class="mb-0">${{ number_format($totalAmount, 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-white card bg-warning">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Completadas</h6>
                                        <h4 class="mb-0">{{ $completedSales }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-white card bg-danger">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Canceladas</h6>
                                        <h4 class="mb-0">{{ $cancelledSales }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de detalles -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="salesDetailsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID Venta</th>
                                        <th>Fecha</th>
                                        <th>Tipo Documento</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Exento</th>
                                        <th>Retenido 13%</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salesDetails as $detail)
                                        <tr>
                                            <td><strong>#{{ $detail->sale_id }}</strong></td>
                                            <td>{{ $detail->formatted_date }}</td>
                                            <td>{{ $detail->document_type }}</td>
                                            <td>{{ $detail->product_name }}</td>
                                            <td class="text-end">{{ number_format($detail->quantity, 2) }}</td>
                                            <td class="text-end">${{ number_format($detail->pricesale, 2) }}</td>
                                            <td class="text-center">
                                                @if($detail->exempt)
                                                    <span class="badge bg-success">Sí</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($detail->detained13)
                                                    <span class="badge bg-warning">Sí</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>
                                            <td class="text-end"><strong>${{ number_format($detail->quantity * $detail->pricesale, 2) }}</strong></td>
                                            <td class="text-center">
                                                @if($detail->state == 1)
                                                    <span class="badge bg-success">Completada</span>
                                                @else
                                                    <span class="badge bg-danger">Cancelada</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            No se encontraron detalles de ventas para este cliente en el período seleccionado.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
@parent
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#salesDetailsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "order": [[1, "desc"]], // Ordenar por fecha descendente
        "pageLength": 25,
        "responsive": true
    });
});

function exportToExcel() {
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
    companyInput.value = '{{ $heading->id }}';
    form.appendChild(companyInput);

    let yearInput = document.createElement('input');
    yearInput.type = 'hidden';
    yearInput.name = 'year';
    yearInput.value = '{{ $yearB }}';
    form.appendChild(yearInput);

    let periodInput = document.createElement('input');
    periodInput.type = 'hidden';
    periodInput.name = 'period';
    periodInput.value = '{{ $period }}';
    form.appendChild(periodInput);

    let clientInput = document.createElement('input');
    clientInput.type = 'hidden';
    clientInput.name = 'client_id';
    clientInput.value = '{{ $client_id }}';
    form.appendChild(clientInput);

    let detailsInput = document.createElement('input');
    detailsInput.type = 'hidden';
    detailsInput.name = 'show_details';
    detailsInput.value = '1';
    form.appendChild(detailsInput);

    let exportInput = document.createElement('input');
    exportInput.type = 'hidden';
    exportInput.name = 'export_excel';
    exportInput.value = '1';
    form.appendChild(exportInput);

    // Enviar formulario
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function exportToPDF() {
    // Crear formulario temporal para exportar
    let form = document.createElement('form');
    form.method = 'GET';
    form.action = '{{ route('report.sales-by-client-details-pdf') }}';
    form.target = '_blank';

    // Agregar parámetros
    let companyInput = document.createElement('input');
    companyInput.type = 'hidden';
    companyInput.name = 'company';
    companyInput.value = '{{ $heading->id }}';
    form.appendChild(companyInput);

    let yearInput = document.createElement('input');
    yearInput.type = 'hidden';
    yearInput.name = 'year';
    yearInput.value = '{{ $yearB }}';
    form.appendChild(yearInput);

    let periodInput = document.createElement('input');
    periodInput.type = 'hidden';
    periodInput.name = 'period';
    periodInput.value = '{{ $period }}';
    form.appendChild(periodInput);

    let clientInput = document.createElement('input');
    clientInput.type = 'hidden';
    clientInput.name = 'client_id';
    clientInput.value = '{{ $client_id }}';
    form.appendChild(clientInput);

    // Enviar formulario
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function downloadPDF() {
    // Crear formulario temporal para descargar
    let form = document.createElement('form');
    form.method = 'GET';
    form.action = '{{ route('report.sales-by-client-details-pdf') }}';

    // Agregar parámetros
    let companyInput = document.createElement('input');
    companyInput.type = 'hidden';
    companyInput.name = 'company';
    companyInput.value = '{{ $heading->id }}';
    form.appendChild(companyInput);

    let yearInput = document.createElement('input');
    yearInput.type = 'hidden';
    yearInput.name = 'year';
    yearInput.value = '{{ $yearB }}';
    form.appendChild(yearInput);

    let periodInput = document.createElement('input');
    periodInput.type = 'hidden';
    periodInput.name = 'period';
    periodInput.value = '{{ $period }}';
    form.appendChild(periodInput);

    let clientInput = document.createElement('input');
    clientInput.type = 'hidden';
    clientInput.name = 'client_id';
    clientInput.value = '{{ $client_id }}';
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
}
</script>
@endsection
