<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Seguimiento de Vencimiento</h4>
                <p class="card-text">Detalles de vencimiento para: <strong>{{ $product->name }}</strong></p>
            </div>
            <div class="card-body">
                <!-- Información del Producto -->
                <div class="mb-4 row">
                    <div class="col-md-2">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="rounded img-fluid" style="max-width: 100px;">
                        @else
                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                <i class="ti ti-package text-muted" style="font-size: 2rem;"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-10">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="150">Código:</th>
                                <td>{{ $product->code }}</td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td>{{ $product->name }}</td>
                            </tr>
                            <tr>
                                <th>Descripción:</th>
                                <td>{{ $product->description }}</td>
                            </tr>
                            <tr>
                                <th>Proveedor:</th>
                                <td>{{ $product->provider->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Precio:</th>
                                <td>${{ number_format($product->price, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Resumen de Estados -->
                <div class="mb-4 row">
                    <div class="col-md-3">
                        <div class="text-white card bg-danger">
                            <div class="text-center card-body">
                                <h3 class="mb-0">{{ $expired->count() }}</h3>
                                <small>Vencidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-white card bg-warning">
                            <div class="text-center card-body">
                                <h3 class="mb-0">{{ $critical->count() }}</h3>
                                <small>Críticos (≤7 días)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-white card bg-info">
                            <div class="text-center card-body">
                                <h3 class="mb-0">{{ $warning->count() }}</h3>
                                <small>Advertencia (≤30 días)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-white card bg-success">
                            <div class="text-center card-body">
                                <h3 class="mb-0">{{ $ok->count() }}</h3>
                                <small>OK</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos Vencidos -->
                @if($expired->count() > 0)
                <div class="mb-4">
                    <h5 class="text-danger">
                        <i class="ti ti-alert-triangle me-2"></i>
                        Productos Vencidos ({{ $expired->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-danger">
                                <tr>
                                    <th>Lote</th>
                                    <th>Fecha Compra</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Días Vencido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expired as $detail)
                                <tr>
                                    <td>{{ $detail->batch_number ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($detail->purchase->date)->format('d/m/Y') }}</td>
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
                @if($critical->count() > 0)
                <div class="mb-4">
                    <h5 class="text-warning">
                        <i class="ti ti-alert-circle me-2"></i>
                        Productos Críticos (≤7 días) ({{ $critical->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-warning">
                                <tr>
                                    <th>Lote</th>
                                    <th>Fecha Compra</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Días Restantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($critical as $detail)
                                <tr>
                                    <td>{{ $detail->batch_number ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($detail->purchase->date)->format('d/m/Y') }}</td>
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
                @if($warning->count() > 0)
                <div class="mb-4">
                    <h5 class="text-info">
                        <i class="ti ti-info-circle me-2"></i>
                        Productos con Advertencia (≤30 días) ({{ $warning->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-info">
                                <tr>
                                    <th>Lote</th>
                                    <th>Fecha Compra</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Días Restantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warning as $detail)
                                <tr>
                                    <td>{{ $detail->batch_number ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($detail->purchase->date)->format('d/m/Y') }}</td>
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

                <!-- Productos OK -->
                @if($ok->count() > 0)
                <div class="mb-4">
                    <h5 class="text-success">
                        <i class="ti ti-check-circle me-2"></i>
                        Productos OK ({{ $ok->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-success">
                                <tr>
                                    <th>Lote</th>
                                    <th>Fecha Compra</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Días Restantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ok as $detail)
                                <tr>
                                    <td>{{ $detail->batch_number ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($detail->purchase->date)->format('d/m/Y') }}</td>
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

                @if($purchaseDetails->count() == 0)
                <div class="py-4 text-center">
                    <i class="ti ti-package text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Sin datos de vencimiento</h5>
                    <p class="text-muted">No hay registros de compra con fechas de vencimiento para este producto.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
