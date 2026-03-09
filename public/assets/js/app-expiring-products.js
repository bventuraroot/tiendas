/**
 * Page Expiring Products - Productos Próximos a Vencer
 */

'use strict';

// ========================================
// VARIABLES GLOBALES
// ========================================

let expiringProducts = [];
let filteredProducts = [];

// ========================================
// INICIALIZACIÓN
// ========================================

$(document).ready(function() {
    // Prevenir que DataTables se inicialice automáticamente en esta tabla
    preventDataTablesInitialization();

    loadExpiringProducts();
    loadProviders();
});

// ========================================
// FUNCIONES DE PROTECCIÓN
// ========================================

function preventDataTablesInitialization() {
    // Asegurar que DataTables no se aplique a esta tabla
    const table = $('#expiringProductsTable');

    if (table.length) {
        // Si ya tiene DataTables, destruirlo
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(table[0])) {
            table.DataTable().destroy();
        }

        // Marcar la tabla para excluir de futuras inicializaciones automáticas
        table.addClass('no-datatables');
        table.attr('data-exclude-datatables', 'true');
    }

    // Protección adicional: verificar si hay múltiples instancias de DataTable
    $('.dataTable').each(function() {
        if ($(this).attr('id') === 'expiringProductsTable' || $(this).hasClass('no-datatables')) {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
                $(this).DataTable().destroy();
            }
        }
    });
}

// ========================================
// FUNCIONES PRINCIPALES
// ========================================

function loadExpiringProducts() {
    $.get('/purchase/expiring-products', function(response) {
        if (response.success) {
            expiringProducts = [];

            // Combinar productos vencidos, críticos y de advertencia
            if (response.data.expired) {
                response.data.expired.forEach(product => {
                    product.status = 'expired';
                    expiringProducts.push(product);
                });
            }

            if (response.data.critical) {
                response.data.critical.forEach(product => {
                    product.status = 'critical';
                    expiringProducts.push(product);
                });
            }

            if (response.data.warning) {
                response.data.warning.forEach(product => {
                    product.status = 'warning';
                    expiringProducts.push(product);
                });
            }

            if (response.data.no_expiration) {
                response.data.no_expiration.forEach(product => {
                    product.status = 'no_expiration';
                    expiringProducts.push(product);
                });
            }

            // Inicializar filtrados con todos los productos
            filteredProducts = [...expiringProducts];

            // Renderizar tabla
            renderTable();

            // Proteger tabla después de renderizar
            preventDataTablesInitialization();

            // Actualizar estadísticas
            updateStats();

        } else {
            showError('Error al cargar productos: ' + (response.message || 'Error desconocido'));
        }
    }).fail(function(xhr, status, error) {
        showError('Error al cargar productos próximos a vencer');
    });
}

function loadProviders() {
    $.ajax({
        url: '/provider/getproviders',
        method: 'GET',
        success: function(response) {
            const select = $('#providerFilter');
            if (select.length) {
                select.empty();
                select.append('<option value="">Todos</option>');

                if (Array.isArray(response)) {
                    response.forEach(provider => {
                        select.append(`<option value="${provider.id}">${provider.razonsocial}</option>`);
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            // Silencioso - no mostrar errores de proveedores
        }
    });
}

function renderTable() {
    const tbody = $('#expiringProductsTableBody');
    tbody.empty();

    if (filteredProducts.length === 0) {
        tbody.append('<tr><td colspan="9" class="text-center">No hay productos próximos a vencer</td></tr>');
        return;
    }

    filteredProducts.forEach(product => {
        // Calcular días restantes manualmente ya que getDaysUntilExpiration no está disponible en el frontend
        let daysUntilExpiration = null;
        if (product.expiration_date) {
            // Usar fecha local para evitar problemas de zona horaria
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Establecer a inicio del día

            // Si la fecha viene en formato Y-m-d, convertir directamente
            let expirationDate;
            if (typeof product.expiration_date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(product.expiration_date)) {
                const [year, month, day] = product.expiration_date.split('-');
                expirationDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
            } else {
                expirationDate = new Date(product.expiration_date);
            }
            expirationDate.setHours(0, 0, 0, 0); // Establecer a inicio del día

            daysUntilExpiration = Math.ceil((expirationDate - today) / (1000 * 60 * 60 * 24));

        }

        const statusColor = getStatusColor(product.status);
        const statusText = getStatusText(product.status);

        const row = `
            <tr>
                <td>
                    <strong>${product.product ? product.product.name : 'N/A'}</strong>
                    <br><small class="text-muted">${product.product ? product.product.code : 'N/A'}</small>
                </td>
                <td>${product.product && product.product.provider ? product.product.provider.razonsocial : 'N/A'}</td>
                <td>
                    <span class="badge bg-primary">${product.quantity}</span>
                </td>
                <td>
                    ${product.expiration_date ? formatDateForDisplay(product.expiration_date) : 'N/A'}
                </td>
                <td>
                    <span class="badge bg-${statusColor}">
                        ${daysUntilExpiration !== null ? Math.abs(daysUntilExpiration) + ' días' : 'N/A'}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${statusColor}">${statusText}</span>
                </td>
                <td>${product.batch_number || 'N/A'}</td>
                <!--<td>${product.location || 'N/A'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="showProductDetails(${product.id})">
                        <i class="ti ti-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="showInventoryAdjustment(${product.id})">
                        <i class="ti ti-edit"></i>
                    </button>
                </td>-->
            </tr>
        `;

        tbody.append(row);
    });

    // Proteger la tabla después de agregar contenido
    preventDataTablesInitialization();
}

// ========================================
// FUNCIONES DE UTILIDAD
// ========================================

function formatDateForDisplay(dateString) {
    if (!dateString) return 'N/A';

    try {
        // Si la fecha ya viene en formato Y-m-d, usarla directamente
        if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
            const [year, month, day] = dateString.split('-');
            return `${day}/${month}/${year}`;
        }

        // Si viene como objeto Date o string ISO, convertir
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'N/A';

        // Usar fecha local para evitar problemas de zona horaria
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return `${day}/${month}/${year}`; // Formato DD/MM/YYYY
    } catch (error) {
        return 'N/A';
    }
}

function getStatusColor(status) {
    return {
        'critical': 'danger',
        'warning': 'warning',
        'expired': 'secondary',
        'no_expiration': 'info'
    }[status] || 'secondary';
}

function getStatusText(status) {
    return {
        'critical': 'Crítico',
        'warning': 'Advertencia',
        'expired': 'Vencido',
        'no_expiration': 'Sin Fecha'
    }[status] || 'Desconocido';
}

function updateStats() {
    const critical = expiringProducts.filter(p => p.status === 'critical').length;
    const warning = expiringProducts.filter(p => p.status === 'warning').length;
    const expired = expiringProducts.filter(p => p.status === 'expired').length;
    const noExpiration = expiringProducts.filter(p => p.status === 'no_expiration').length;

    $('#criticalCount').text(critical);
    $('#warningCount').text(warning);
    $('#expiredCount').text(expired);
    $('#noExpirationCount').text(noExpiration);
    $('#totalCount').text(expiringProducts.length);
}

function showError(message) {
    Swal.fire({
        title: 'Error',
        text: message,
        icon: 'error',
        confirmButtonText: 'Ok'
    });
}

function showSuccess(message) {
    Swal.fire({
        title: 'Éxito',
        text: message,
        icon: 'success',
        confirmButtonText: 'Ok'
    });
}

// ========================================
// FUNCIONES DE ACCIONES
// ========================================

function refreshData() {
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Actualizando datos...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Recargar datos
    loadExpiringProducts();
    loadProviders();

    // Cerrar indicador después de un momento
    setTimeout(() => {
        Swal.close();
    }, 1500);
}

function testManual() {
    Swal.fire({
        title: 'Recargando datos...',
        text: 'Verificando conexión con el servidor',
        icon: 'info',
        timer: 1500,
        showConfirmButton: false
    });

    // Simplemente recargar los datos
    loadExpiringProducts();
    loadProviders();
}

function generateExpirationDates() {
    Swal.fire({
        title: 'Generar Fechas de Expiración',
        text: '¿Deseas generar fechas de expiración automáticamente para productos que no las tienen?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Generando fechas...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar petición
            $.post('/purchase/generate-expiration-dates', {
                _token: $('meta[name="csrf-token"]').attr('content')
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Éxito',
                        text: response.message || 'Fechas generadas correctamente',
                        icon: 'success'
                    });
                    loadExpiringProducts(); // Recargar datos
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message || 'Error al generar fechas',
                        icon: 'error'
                    });
                }
            })
            .fail(function(xhr) {
                Swal.fire({
                    title: 'Error',
                    text: 'Error de conexión al generar fechas',
                    icon: 'error'
                });
            });
        }
    });
}

function exportToExcel() {
    // Aquí puedes implementar la exportación a Excel
    alert('Funcionalidad de exportación en desarrollo');
}

// ========================================
// FUNCIONES DE FILTROS
// ========================================

function filterByStatus() {
    const status = $('#statusFilter').val();

    if (status === '') {
        filteredProducts = [...expiringProducts];
    } else {
        filteredProducts = expiringProducts.filter(product => product.status === status);
    }

    renderTable();
}

function filterByProvider() {
    const providerId = $('#providerFilter').val();

    if (providerId === '') {
        filteredProducts = [...expiringProducts];
    } else {
        filteredProducts = expiringProducts.filter(product =>
            product.product && product.product.provider &&
            product.product.provider.id == providerId
        );
    }

    renderTable();
}

function applyFilters() {
    const status = $('#statusFilter').val();
    const providerId = $('#providerFilter').val();

    filteredProducts = expiringProducts.filter(product => {
        let matchesStatus = true;
        let matchesProvider = true;

        if (status !== '') {
            matchesStatus = product.status === status;
        }

        if (providerId !== '') {
            matchesProvider = product.product && product.product.provider &&
                            product.product.provider.id == providerId;
        }

        return matchesStatus && matchesProvider;
    });

    renderTable();
}

// ========================================
// FUNCIONES PLACEHOLDER
// ========================================

function showProductDetails(productId) {
    alert('Ver detalles del producto ID: ' + productId + ' - Funcionalidad en desarrollo');
}

function showInventoryAdjustment(productId) {
    alert('Ajustar inventario del producto ID: ' + productId + ' - Funcionalidad en desarrollo');
}

// ========================================
// INICIALIZACIÓN COMPLETA
// ========================================
