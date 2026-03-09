<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <!--<h4 class="card-title">Reporte de Vencimiento de Productos</h4>-->
                <p class="card-text">Resumen de productos con fechas de vencimiento próximas o vencidas</p>
            </div>
            <div class="card-body">
                <!-- Resumen de estadísticas -->
                <div class="mb-4 row">
                    <div class="col-md-3">
                        <div class="text-white card bg-danger">
                            <div class="text-center card-body">
                                <h3 class="mb-0">{{ $expiringProducts['expired']->count() }}</h3>
                                <small>Vencidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-white card bg-warning">
                            <div class="text-center card-body">
                                <h3 class="mb-0">{{ $expiringProducts['critical']->count() }}</h3>
                                <small>Críticos (≤7 días)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-white card bg-info">
                            <div class="text-center card-body">
                                <h3 class="mb-0">{{ $expiringProducts['warning']->count() }}</h3>
                                <small>Advertencia (≤30 días)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-white card bg-success">
                            <div class="text-center card-body">
                                <h3 class="mb-0">{{ $expiringProducts['no_expiration']->count() }}</h3>
                                <small>Sin vencimiento</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos Vencidos -->
                @if($expiringProducts['expired']->count() > 0)
                <div class="mb-4">
                    <h5 class="text-danger">
                        <i class="ti ti-alert-triangle me-2"></i>
                        Productos Vencidos ({{ $expiringProducts['expired']->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-danger">
                                <tr>
                                    <th>Producto</th>
                                    <th>Lote</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Días Vencido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expiringProducts['expired'] as $detail)
                                <tr>
                                    <td>
                                        <strong>{{ $detail->product->name }}</strong><br>
                                        <small class="text-muted">{{ $detail->product->code }}</small>
                                    </td>
                                    <td>{{ $detail->batch_number ?? 'N/A' }}</td>
                                    <td>
                                        @if($detail->expiration_date)
                                            {{ \Carbon\Carbon::parse($detail->expiration_date)->format('d/m/Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $detail->quantity }}</td>
                                    <td>{{ $detail->purchase->provider->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-danger">
                                            {{ $detail->getDaysUntilExpiration() * -1 }} días
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Productos Críticos -->
                @if($expiringProducts['critical']->count() > 0)
                <div class="mb-4">
                    <h5 class="text-warning">
                        <i class="ti ti-alert-circle me-2"></i>
                        Productos Críticos (≤7 días) ({{ $expiringProducts['critical']->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-warning">
                                <tr>
                                    <th>Producto</th>
                                    <th>Lote</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Días Restantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expiringProducts['critical'] as $detail)
                                <tr>
                                    <td>
                                        <strong>{{ $detail->product->name }}</strong><br>
                                        <small class="text-muted">{{ $detail->product->code }}</small>
                                    </td>
                                    <td>{{ $detail->batch_number ?? 'N/A' }}</td>
                                    <td>
                                        @if($detail->expiration_date)
                                            {{ \Carbon\Carbon::parse($detail->expiration_date)->format('d/m/Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $detail->quantity }}</td>
                                    <td>{{ $detail->purchase->provider->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-warning">
                                            {{ $detail->getDaysUntilExpiration() }} días
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Productos con Advertencia -->
                @if($expiringProducts['warning']->count() > 0)
                <div class="mb-4">
                    <h5 class="text-info">
                        <i class="ti ti-info-circle me-2"></i>
                        Productos con Advertencia (≤30 días) ({{ $expiringProducts['warning']->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-info">
                                <tr>
                                    <th>Producto</th>
                                    <th>Lote</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Días Restantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expiringProducts['warning'] as $detail)
                                <tr>
                                    <td>
                                        <strong>{{ $detail->product->name }}</strong><br>
                                        <small class="text-muted">{{ $detail->product->code }}</small>
                                    </td>
                                    <td>{{ $detail->batch_number ?? 'N/A' }}</td>
                                    <td>
                                        @if($detail->expiration_date)
                                            {{ \Carbon\Carbon::parse($detail->expiration_date)->format('d/m/Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $detail->quantity }}</td>
                                    <td>{{ $detail->purchase->provider->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $detail->getDaysUntilExpiration() }} días
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Productos sin Vencimiento -->
                @if($expiringProducts['no_expiration']->count() > 0)
                <div class="mb-4">
                    <h5 class="text-success">
                        <i class="ti ti-check-circle me-2"></i>
                        Productos sin Vencimiento ({{ $expiringProducts['no_expiration']->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-success">
                                <tr>
                                    <th>Producto</th>
                                    <th>Lote</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expiringProducts['no_expiration'] as $detail)
                                <tr>
                                    <td>
                                        <strong>{{ $detail->product->name }}</strong><br>
                                        <small class="text-muted">{{ $detail->product->code }}</small>
                                    </td>
                                    <td>{{ $detail->batch_number ?? 'N/A' }}</td>
                                    <td>
                                        @if($detail->expiration_date)
                                            {{ \Carbon\Carbon::parse($detail->expiration_date)->format('d/m/Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $detail->quantity }}</td>
                                    <td>{{ $detail->purchase->provider->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-success">
                                            OK
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($expiringProducts['expired']->count() == 0 &&
                    $expiringProducts['critical']->count() == 0 &&
                    $expiringProducts['warning']->count() == 0)
                <div class="py-4 text-center">
                    <i class="ti ti-check-circle text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-success">¡Excelente!</h5>
                    <p class="text-muted">No hay productos con fechas de vencimiento próximas o vencidas.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
