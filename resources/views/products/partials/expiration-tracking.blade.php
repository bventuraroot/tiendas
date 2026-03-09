<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Información del Producto</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <img src="{{ asset('assets/img/products/' . $product->image) }}" alt="Imagen del producto" class="img-fluid mb-3" style="max-height: 150px;">
                    </div>
                    <div class="col-md-9">
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%">Código:</th>
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
                                        <td>{{ $product->provider ? $product->provider->razonsocial : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Precio:</th>
                                        <td>$ {{ number_format($product->price, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($purchaseDetails->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Seguimiento de Vencimiento por Lotes</h5>
                </div>
                <div class="card-body">
                    <!-- Productos Vencidos -->
                    @if($expired->count() > 0)
                        <div class="mb-4">
                            <h6 class="text-danger mb-3">
                                <i class="ti ti-alert-triangle me-2"></i>
                                Productos Vencidos ({{ $expired->count() }})
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-danger">
                                    <thead>
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
                                                <td>{{ $detail->expiration_date ? \Carbon\Carbon::parse($detail->expiration_date)->format('d/m/Y') : 'N/A' }}</td>
                                                <td>{{ $detail->quantity }}</td>
                                                <td>{{ $detail->purchase->provider->razonsocial ?? 'N/A' }}</td>
                                                <td>
                                                    @if($detail->expiration_date)
                                                        <span class="badge bg-danger">{{ abs($detail->getDaysUntilExpiration()) }} días</span>
                                                    @else
                                                        N/A
                                                    @endif
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
                            <h6 class="text-warning mb-3">
                                <i class="ti ti-alert-circle me-2"></i>
                                Productos Críticos - Vencen en 7 días o menos ({{ $critical->count() }})
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-warning">
                                    <thead>
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
                                                <td>{{ $detail->expiration_date ? \Carbon\Carbon::parse($detail->expiration_date)->format('d/m/Y') : 'N/A' }}</td>
                                                <td>{{ $detail->quantity }}</td>
                                                <td>{{ $detail->purchase->provider->razonsocial ?? 'N/A' }}</td>
                                                <td>
                                                    @if($detail->expiration_date)
                                                        <span class="badge bg-warning">{{ $detail->getDaysUntilExpiration() }} días</span>
                                                    @else
                                                        N/A
                                                    @endif
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
                            <h6 class="text-info mb-3">
                                <i class="ti ti-info-circle me-2"></i>
                                Productos con Advertencia - Vencen en 8-30 días ({{ $warning->count() }})
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-info">
                                    <thead>
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
                                                <td>{{ $detail->expiration_date ? \Carbon\Carbon::parse($detail->expiration_date)->format('d/m/Y') : 'N/A' }}</td>
                                                <td>{{ $detail->quantity }}</td>
                                                <td>{{ $detail->purchase->provider->razonsocial ?? 'N/A' }}</td>
                                                <td>
                                                    @if($detail->expiration_date)
                                                        <span class="badge bg-info">{{ $detail->getDaysUntilExpiration() }} días</span>
                                                    @else
                                                        N/A
                                                    @endif
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
                            <h6 class="text-success mb-3">
                                <i class="ti ti-check-circle me-2"></i>
                                Productos OK - Vencen en más de 30 días ({{ $ok->count() }})
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-success">
                                    <thead>
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
                                                <td>{{ $detail->expiration_date ? \Carbon\Carbon::parse($detail->expiration_date)->format('d/m/Y') : 'N/A' }}</td>
                                                <td>{{ $detail->quantity }}</td>
                                                <td>{{ $detail->purchase->provider->razonsocial ?? 'N/A' }}</td>
                                                <td>
                                                    @if($detail->expiration_date)
                                                        <span class="badge bg-success">{{ $detail->getDaysUntilExpiration() }} días</span>
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Resumen de Vencimiento</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $expired->count() }}</h4>
                                    <p class="mb-0">Vencidos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h4>{{ $critical->count() }}</h4>
                                    <p class="mb-0">Críticos (≤7 días)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $warning->count() }}</h4>
                                    <p class="mb-0">Advertencia (8-30 días)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $ok->count() }}</h4>
                                    <p class="mb-0">OK (>30 días)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <i class="ti ti-info-circle text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">No hay registros de compra para este producto</h5>
                    <p class="text-muted">Este producto no tiene historial de compras con fechas de vencimiento registradas.</p>
                </div>
            </div>
        </div>
    </div>
@endif
