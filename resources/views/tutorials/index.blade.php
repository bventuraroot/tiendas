@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Tutoriales')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Utilidades /</span> Tutoriales
    </h4>

    <div class="row">
        @foreach($tutorials as $tutorial)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="{{ $tutorial['icon'] ?? 'fa-solid fa-book' }}"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $tutorial['title'] }}</h5>
                            <small class="text-muted">{{ $tutorial['category'] ?? 'General' }}</small>
                        </div>
                    </div>
                    <p class="card-text">{{ $tutorial['description'] }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('tutorials.show', $tutorial['file']) }}" class="btn btn-primary w-100">
                        <i class="fa-solid fa-book-open me-2"></i>Ver Tutorial
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if(empty($tutorials))
    <div class="alert alert-info">
        <i class="fa-solid fa-info-circle me-2"></i>
        No hay tutoriales disponibles en este momento.
    </div>
    @endif
</div>
@endsection
