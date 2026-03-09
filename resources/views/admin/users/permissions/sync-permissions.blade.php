@extends('layouts.contentNavbarLayout')

@section('title', 'Sincronizar Permisos')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-sync me-2"></i>
                        Sincronizar Permisos desde Rutas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        <strong>¿Qué hace este proceso?</strong><br>
                        Este proceso leerá todas las rutas definidas en el sistema y creará automáticamente los permisos correspondientes en la base de datos. 
                        Los permisos que ya existen no se duplicarán.
                    </div>

                    <div id="sync-result" style="display: none;"></div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="btn-sync-permissions">
                            <i class="fa-solid fa-sync me-2"></i>
                            Sincronizar Permisos
                        </button>
                        <a href="{{ route('permission.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left me-2"></i>
                            Volver a Permisos
                        </a>
                        <a href="{{ route('rol.index') }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-user-shield me-2"></i>
                            Gestionar Roles
                        </a>
                    </div>

                    <div id="loading" style="display: none;" class="mt-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span class="ms-2">Sincronizando permisos, por favor espere...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnSync = document.getElementById('btn-sync-permissions');
    const loading = document.getElementById('loading');
    const resultDiv = document.getElementById('sync-result');

    btnSync.addEventListener('click', function() {
        // Deshabilitar botón
        btnSync.disabled = true;
        btnSync.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Sincronizando...';
        
        // Mostrar loading
        loading.style.display = 'block';
        resultDiv.style.display = 'none';

        // Hacer petición AJAX
        fetch('{{ route("permission.sync-all-permissions") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            if (data.success) {
                let html = '<div class="alert alert-success mt-3" role="alert">';
                html += '<h6><i class="fa-solid fa-check-circle me-2"></i>' + data.message + '</h6>';
                html += '<hr>';
                html += '<p><strong>Permisos creados:</strong> ' + data.created + '</p>';
                html += '<p><strong>Permisos existentes:</strong> ' + data.existing + '</p>';
                html += '<p><strong>Rutas ignoradas:</strong> ' + data.skipped + '</p>';
                html += '<p><strong>Total procesado:</strong> ' + data.total + '</p>';
                
                if (data.permissions_by_module && Object.keys(data.permissions_by_module).length > 0) {
                    html += '<hr><h6>Permisos creados por módulo:</h6><ul class="list-group mt-2">';
                    for (const [module, permissions] of Object.entries(data.permissions_by_module)) {
                        html += '<li class="list-group-item"><strong>' + module + ':</strong> ' + permissions.length + ' permisos</li>';
                    }
                    html += '</ul>';
                }
                
                html += '</div>';
                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger mt-3" role="alert">' +
                    '<i class="fa-solid fa-exclamation-circle me-2"></i>' +
                    '<strong>Error:</strong> ' + data.message +
                    '</div>';
            }
            
            resultDiv.style.display = 'block';
            
            // Restaurar botón
            btnSync.disabled = false;
            btnSync.innerHTML = '<i class="fa-solid fa-sync me-2"></i>Sincronizar Permisos';
        })
        .catch(error => {
            loading.style.display = 'none';
            resultDiv.innerHTML = '<div class="alert alert-danger mt-3" role="alert">' +
                '<i class="fa-solid fa-exclamation-circle me-2"></i>' +
                '<strong>Error:</strong> ' + error.message +
                '</div>';
            resultDiv.style.display = 'block';
            
            // Restaurar botón
            btnSync.disabled = false;
            btnSync.innerHTML = '<i class="fa-solid fa-sync me-2"></i>Sincronizar Permisos';
        });
    });
});
</script>
@endsection




