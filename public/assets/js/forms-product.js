/**
 * Form Picker
 */

'use strict';
$(document).ready(function (){

    $("#name").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toUpperCase());
    });

    $("#name-edit").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toUpperCase());
    });

    //Get providers disponibles - vaciar antes para evitar duplicados
    var iduser = $('#iduser').val();
    $.ajax({
        url: "/provider/getproviders",
        method: "GET",
        success: function(response){
            $('#provider').empty().append('<option value="">Seleccione</option>');
            $('#provideredit').empty().append('<option value="">Seleccione</option>');
            $.each(response, function(index, value) {
                $('#provider').append('<option value="'+value.id+'">'+value.razonsocial.toUpperCase()+'</option>');
                $('#provideredit').append('<option value="'+value.id+'">'+value.razonsocial.toUpperCase()+'</option>');
            });
        }
    });
    //Get marcas disponibles - vaciar antes para evitar duplicados
    $.ajax({
        url: "/marcas/getmarcas",
        method: "GET",
        success: function(response){
            $('#marca').empty().append('<option value="">Seleccione</option>');
            $('#marcaredit').empty().append('<option value="">Seleccione</option>');
            $.each(response, function(index, value) {
                $('#marca').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                $('#marcaredit').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
            });
        }
    });

    //Get pharmaceutical laboratories available
    $.ajax({
        url: "/pharmaceutical-laboratories/get/laboratories",
        method: "GET",
        success: function(response){
            $('#pharmaceutical_laboratory').append('<option value="">Seleccione</option>');
            $('#pharmaceutical_laboratoryedit').append('<option value="">Seleccione</option>');
            $.each(response, function(index, value) {
                $('#pharmaceutical_laboratory').append('<option value="'+value.id+'">'+value.name+'</option>');
                $('#pharmaceutical_laboratoryedit').append('<option value="'+value.id+'">'+value.name+'</option>');
              });
        }
    });

    // Inicializar componente cuando se muestre el modal de crear
    $('#addProductModal').on('shown.bs.modal', function () {
        if(typeof initializeImageUpload === 'function') {
            initializeImageUpload('image');
        }
    });

    // Limpiar modal de crear cuando se cierre
    $('#addProductModal').on('hidden.bs.modal', function () {
        if(typeof clearImageUpload === 'function') {
            clearImageUpload('image');
        }
    });

    // Limpiar modal de editar cuando se cierre
    $('#updateProductModal').on('hidden.bs.modal', function () {
        if(typeof clearImageUpload === 'function') {
            clearImageUpload('imageedit');
        }
        // Eliminar el campo oculto de imagen original
        $('#editproductForm #imageeditoriginal').remove();
    });

    // Calcular resumen de conversiones para crear producto
    $('#pastillas_per_blister, #blisters_per_caja').on('input', function() {
        updateConversionSummary();
    });

    // Calcular resumen de conversiones para editar producto
    $('#pastillas_per_blisteredit, #blisters_per_cajaedit').on('input', function() {
        updateConversionSummaryEdit();
    });

    // Función para actualizar resumen de conversiones (crear)
    function updateConversionSummary() {
        var pastillasPerBlister = parseInt($('#pastillas_per_blister').val()) || 0;
        var blistersPerCaja = parseInt($('#blisters_per_caja').val()) || 0;
        var totalPastillas = pastillasPerBlister * blistersPerCaja;

        $('#summary-pastillas-per-blister').text(pastillasPerBlister || '-');
        $('#summary-blisters-per-caja').text(blistersPerCaja || '-');
        $('#summary-total-pastillas').text(totalPastillas || '-');
    }

    // Función para actualizar resumen de conversiones (editar)
    function updateConversionSummaryEdit() {
        var pastillasPerBlister = parseInt($('#pastillas_per_blisteredit').val()) || 0;
        var blistersPerCaja = parseInt($('#blisters_per_cajaedit').val()) || 0;
        var totalPastillas = pastillasPerBlister * blistersPerCaja;

        $('#summary-pastillas-per-blister-edit').text(pastillasPerBlister || '-');
        $('#summary-blisters-per-caja-edit').text(blistersPerCaja || '-');
        $('#summary-total-pastillas-edit').text(totalPastillas || '-');
    }

    // Inicializar resumen al cargar
    updateConversionSummary();

    // Manejar envío del formulario de AGREGAR producto con AJAX (errores en Swal)
    $('#addproductForm').on('submit', function(e) {
        e.preventDefault();

        if ($('.is-invalid', this).length > 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error de validación',
                    text: 'Por favor corrija los campos marcados en rojo antes de continuar',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            } else {
                alert('Por favor corrija los campos marcados en rojo antes de continuar');
            }
            return;
        }

        const form = this;
        const formData = new FormData(form);
        const actionUrl = $(form).attr('action');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Creando producto...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }

        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                const message = (response && response.message) ? response.message : 'Producto creado exitosamente';
                const $modal = $('#addProductModal');
                if ($modal.length && $modal.hasClass('show')) {
                    $modal.modal('hide');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: message,
                        icon: 'success',
                        confirmButtonText: 'Entendido'
                    }).then(() => { location.reload(); });
                } else {
                    alert(message);
                    location.reload();
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error al crear el producto';

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    const messages = [];
                    Object.keys(errors).forEach(function(key) {
                        if (Array.isArray(errors[key])) {
                            messages.push(errors[key].join(', '));
                        }
                    });
                    if (messages.length > 0) {
                        errorMessage = messages.join('\n');
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    alert(errorMessage);
                }
            }
        });
    });

    // Manejar envío del formulario de edición de producto con AJAX
    $('#editproductForm').on('submit', function(e) {
        e.preventDefault();

        // Si hay campos inválidos marcados por las validaciones, no continuar
        if ($('.is-invalid', this).length > 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error de validación',
                    text: 'Por favor corrija los campos marcados en rojo antes de continuar',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            } else {
                alert('Por favor corrija los campos marcados en rojo antes de continuar');
            }
            return;
        }

        const form = this;
        const formData = new FormData(form);
        const actionUrl = $(form).attr('action');

        // Mostrar loading
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Actualizando producto...',
                text: 'Por favor espere mientras se guardan los cambios',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                const message = (response && response.message)
                    ? response.message
                    : 'Producto actualizado exitosamente';

                // Cerrar el modal primero para que el Swal no quede detrás
                const showSuccess = () => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: message,
                            icon: 'success',
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        alert(message);
                        location.reload();
                    }
                };

                const $modal = $('#updateProductModal');
                if ($modal.length && $modal.hasClass('show')) {
                    $modal.one('hidden.bs.modal', function() {
                        showSuccess();
                    });
                    $modal.modal('hide');
                } else {
                    showSuccess();
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error al actualizar el producto';

                // Errores de validación de Laravel (422)
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    const messages = [];
                    Object.keys(errors).forEach(function(key) {
                        if (Array.isArray(errors[key])) {
                            messages.push(errors[key].join('\n'));
                        }
                    });
                    if (messages.length > 0) {
                        errorMessage = messages.join('\n');
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    // Mensaje general del controlador
                    errorMessage = xhr.responseJSON.message;
                }

                // Cerrar el modal primero para que el Swal no quede detrás
                const showError = () => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        alert(errorMessage);
                    }
                };

                const $modal = $('#updateProductModal');
                if ($modal.length && $modal.hasClass('show')) {
                    $modal.one('hidden.bs.modal', function() {
                        showError();
                    });
                    $modal.modal('hide');
                } else {
                    showError();
                }
            }
        });
    });
});

   function editproduct(id){
    // Limpiar el componente de imagen antes de cargar datos
    if(typeof clearImageUpload === 'function') {
        clearImageUpload('imageedit');
    }

    //Get data edit Products
    $.ajax({
        url: "getproductid/"+btoa(id),
        method: "GET",
        success: function(response){
            $.each(response[0], function(index, value) {
                    // Excluir campos de archivo y campos especiales
                    if(index !== 'image' && index !== 'provider_id' && index !== 'cfiscal' && index !== 'type' && index !== 'category') {
                        $('#'+index+'edit').val(value);
                    }

                    if(index=='image'){
                        // Agregar o actualizar el campo oculto para la imagen original
                        let hiddenField = $('#imageeditoriginal');
                        if(hiddenField.length === 0) {
                            // Si no existe, crear el campo oculto
                            $('<input>').attr({
                                type: 'hidden',
                                id: 'imageeditoriginal',
                                name: 'imageeditoriginal',
                                value: value
                            }).appendTo('#editproductForm');
                        } else {
                            // Si existe, actualizar su valor
                            hiddenField.val(value);
                        }
                        // Limpiar el preview anterior
                        const currentImageContainer = document.getElementById('current-image-imageedit');
                        if(currentImageContainer) {
                            currentImageContainer.innerHTML = '';
                        }
                        // Mostrar la imagen actual si existe
                        if(value && value !== 'null' && value !== '') {
                            if(currentImageContainer) {
                                currentImageContainer.innerHTML = `
                                    <img src="/assets/img/products/${value}" alt="Imagen actual" class="img-thumbnail" style="max-height: 100px;">
                                    <div class="mt-1">
                                        <small class="text-muted">${value}</small>
                                    </div>
                                `;
                                currentImageContainer.style.display = 'block';
                            }
                        } else {
                            if(currentImageContainer) {
                                currentImageContainer.style.display = 'none';
                            }
                        }
                        // Limpiar el campo de imagen sin establecer valor
                        if($('#imageedit').length) {
                            $('#imageedit').val('');
                        }
                    }
                    if(index=='marca_id'){
                        //$("#marcaredit option[value='"+ value  +"']").attr("selected", true);
                        $("#marcaredit").val(value).trigger('change');
                    }
                    if(index=='provider_id'){
                        $("#provideredit").val(value).trigger('change');
                    }
                    if(index=='cfiscal'){
                        $("#cfiscaledit option[value='"+ value  +"']").attr("selected", true);
                    }
                    if(index=='type'){
                        $("#typeedit option[value='"+ value  +"']").attr("selected", true);
                    }
                    if(index=='category'){
                        //$("#categoryedit option[value='"+ value  +"']").attr("selected", true);
                        $("#categoryedit").val(value).trigger('change');
                    }
                    if(index=='pharmaceutical_laboratory_id'){
                        $("#pharmaceutical_laboratoryedit").val(value).trigger('change');
                    }
                    if(index=='presentation_type'){
                        $("#presentation_typeedit").val(value);
                    }
                    if(index=='specialty'){
                        $("#specialtyedit").val(value);
                    }
                    if(index=='registration_number'){
                        $("#registration_numberedit").val(value);
                    }
                    if(index=='formula'){
                        $("#formulaedit").val(value);
                    }
                    if(index=='unit_measure'){
                        $("#unit_measureedit").val(value);
                    }
                    if(index=='sale_form'){
                        $("#sale_formedit").val(value);
                    }
                    if(index=='product_type'){
                        $("#product_typeedit").val(value);
                    }
                    if(index=='pastillas_per_blister'){
                        $("#pastillas_per_blisteredit").val(value || '');
                    }
                    if(index=='blisters_per_caja'){
                        $("#blisters_per_cajaedit").val(value || '');
                    }

              });

              // Actualizar resumen de conversiones después de cargar todos los datos
              setTimeout(function() {
                  updateConversionSummaryEdit();
              }, 200);

              // Cargar datos de unidades de medida después de cargar los datos básicos
              if(typeof window.loadProductDataForEdit === 'function') {
                  window.loadProductDataForEdit(response[0]);
              }

              $("#updateProductModal").modal("show");

              // Inicializar el componente de imagen después de mostrar el modal
              setTimeout(() => {
                  if(typeof initializeImageUpload === 'function') {
                      initializeImageUpload('imageedit');
                  }
              }, 300);
        }
    });
   }

   function toggleState(id, newState){
    var stateText = newState == 1 ? 'activar' : 'desactivar';
    var swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
      })

      swalWithBootstrapButtons.fire({
        title: '¿Cambiar estado?',
        text: '¿Está seguro que desea ' + stateText + ' este producto?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, ' + stateText + '!',
        cancelButtonText: 'No, Cancelar!',
        reverseButtons: true
      }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "toggleState/"+btoa(id),
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    state: newState
                },
                success: function(response){
                        if(response.res==1){
                            Swal.fire({
                                title: 'Estado actualizado',
                                text: 'Producto ' + stateText + 'do correctamente',
                                icon: 'success',
                                confirmButtonText: 'Ok'
                              }).then(function(result) {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                  location.reload();
                                }
                              })

                        }else if(response.res==0){
                            swalWithBootstrapButtons.fire(
                                'Problemas!',
                                'Algo sucedió y no pudo cambiar el estado del producto, favor comunicarse con el administrador.',
                                'error'
                              )
                        }
            }
            });
        } else if (
          /* Read more about handling dismissals below */
          result.dismiss === Swal.DismissReason.cancel
        ) {
          swalWithBootstrapButtons.fire(
            'Cancelado',
            'No hemos hecho ninguna acción :)',
            'info'
          )
        }
      })
   }

   function deleteproduct(id){
    var swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
      })

      swalWithBootstrapButtons.fire({
        title: '¿Eliminar?',
        text: "Esta accion no tiene retorno",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, Eliminarlo!',
        cancelButtonText: 'No, Cancelar!',
        reverseButtons: true
      }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "destroy/"+btoa(id),
                method: "GET",
                success: function(response){
                        if(response.res==1){
                            Swal.fire({
                                title: 'Eliminado',
                                icon: 'success',
                                confirmButtonText: 'Ok'
                              }).then(function(result) {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                  location.reload();
                                }
                              })

                        }else if(response.res==0){
                            swalWithBootstrapButtons.fire(
                                'Problemas!',
                                'Algo sucedió y no pudo eliminar el cliente, favor comunicarse con el administrador.',
                                'success'
                              )
                        }
            }
            });
        } else if (
          /* Read more about handling dismissals below */
          result.dismiss === Swal.DismissReason.cancel
        ) {
          swalWithBootstrapButtons.fire(
            'Cancelado',
            'No hemos hecho ninguna acción :)',
            'error'
          )
        }
      })
   }

