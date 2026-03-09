@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Detalle del Laboratorio')

@section('content')
    <div class="row">
        <!-- Información del Laboratorio -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Información del Laboratorio</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h4>{{ $pharmaceutical_laboratory->name }}</h4>
                        @if($pharmaceutical_laboratory->active)
                            <span class="badge bg-label-success">Activo</span>
                        @else
                            <span class="badge bg-label-danger">Inactivo</span>
                        @endif
                    </div>

                    @if($pharmaceutical_laboratory->code)
                    <div class="mb-3">
                        <label class="fw-bold">Código:</label>
                        <p class="mb-0">{{ $pharmaceutical_laboratory->code }}</p>
                    </div>
                    @endif

                    @if($pharmaceutical_laboratory->country)
                    <div class="mb-3">
                        <label class="fw-bold">País:</label>
                        <p class="mb-0">{{ $pharmaceutical_laboratory->country }}</p>
                    </div>
                    @endif

                    @if($pharmaceutical_laboratory->phone)
                    <div class="mb-3">
                        <label class="fw-bold">Teléfono:</label>
                        <p class="mb-0">{{ $pharmaceutical_laboratory->phone }}</p>
                    </div>
                    @endif

                    @if($pharmaceutical_laboratory->email)
                    <div class="mb-3">
                        <label class="fw-bold">Email:</label>
                        <p class="mb-0">{{ $pharmaceutical_laboratory->email }}</p>
                    </div>
                    @endif

                    @if($pharmaceutical_laboratory->website)
                    <div class="mb-3">
                        <label class="fw-bold">Sitio Web:</label>
                        <p class="mb-0">
                            <a href="{{ $pharmaceutical_laboratory->website }}" target="_blank">
                                {{ $pharmaceutical_laboratory->website }}
                            </a>
                        </p>
                    </div>
                    @endif

                    @if($pharmaceutical_laboratory->address)
                    <div class="mb-3">
                        <label class="fw-bold">Dirección:</label>
                        <p class="mb-0">{{ $pharmaceutical_laboratory->address }}</p>
                    </div>
                    @endif

                    @if($pharmaceutical_laboratory->description)
                    <div class="mb-3">
                        <label class="fw-bold">Descripción:</label>
                        <p class="mb-0">{{ $pharmaceutical_laboratory->description }}</p>
                    </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('pharmaceutical-laboratories.index') }}" class="btn btn-label-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Volver
                        </a>
                        <a href="{{ route('pharmaceutical-laboratories.edit', $pharmaceutical_laboratory->id) }}" 
                           class="btn btn-primary">
                            <i class="ti ti-edit me-1"></i> Editar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos del Laboratorio -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Productos del Laboratorio
                        <span class="badge bg-label-primary ms-2">{{ $pharmaceutical_laboratory->products_count }} productos</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($products->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Presentación</th>
                                        <th>Especialidad</th>
                                        <th>Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        <tr>
                                            <td><code>{{ $product->code }}</code></td>
                                            <td>{{ $product->name }}</td>
                                            <td>
                                                @if($product->presentation_type)
                                                    <span class="badge bg-label-info">{{ $product->presentation_type }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $product->specialty ?? '-' }}</small>
                                            </td>
                                            <td class="text-end">
                                                <strong>${{ number_format($product->price, 2) }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="mt-3">
                            {{ $products->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-package-off" style="font-size: 48px; color: #ccc;"></i>
                            <p class="mt-2 text-muted">No hay productos de este laboratorio</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection


