/**
 * Form Picker
 */

'use strict';
$(document).ready(function (){
    //Get providers avaibles
    var iduser = $('#iduser').val();
    $.ajax({
        url: "/provider/getproviders",
        method: "GET",
        success: function(response){
            $('#provider').append('<option value="0">Seleccione</option>');
            $.each(response, function(index, value) {
                $('#provider').append('<option value="'+value.id+'">'+value.razonsocial.toUpperCase()+'</option>');
                $('#provideredit').append('<option value="'+value.id+'">'+value.razonsocial.toUpperCase()+'</option>');
              });
        }
    });

    $.ajax({
        url: "/company/getCompanybyuser/" + iduser,
        method: "GET",
        success: function (response) {
            $("#company").append('<option value="0">Seleccione</option>');
            $.each(response, function (index, value) {
                $("#company").append(
                    '<option selected value="' +
                        value.id +
                        '">' +
                        value.name.toUpperCase() +
                        "</option>"
                );
                $("#companyedit").append(
                    '<option value="' +
                        value.id +
                        '">' +
                        value.name.toUpperCase() +
                        "</option>"
                );
            });
        },
    });

    initializePaymentTermsSection({
        paymentTypeSelector: '#payment_type',
        creditDaysContainerSelector: '#credit_days_container',
        creditDaysSelector: '#credit_days',
        dueDateSelector: '#payment_due_date',
        baseDateSelector: '#date'
    });

    initializePaymentTermsSection({
        paymentTypeSelector: '#payment_typeedit',
        creditDaysContainerSelector: '#credit_days_container_edit',
        creditDaysSelector: '#credit_daysedit',
        dueDateSelector: '#payment_due_dateedit',
        baseDateSelector: '#dateedit'
    });
    
    // Resetear estado cuando se cierre el modal de edición
    $('#updatePurchaseModal').on('hidden.bs.modal', function () {
        const paymentTypeEditEl = document.querySelector('#payment_typeedit');
        if (paymentTypeEditEl) {
            paymentTypeEditEl.dataset.initialized = 'false';
        }
    });
});

function calculaiva(monto){
    monto=parseFloat(monto*13/100).toFixed(5);
    $("#iva").val(monto);
    suma();
};
//edit
function calculaivaedit(monto){
    monto=parseFloat(monto*13/100).toFixed(5);
    $("#ivaedit").val(monto);
    suma();
};

function suma(){
    var gravada = $("#gravada").val();
    var iva = $("#iva").val();
    var exenta = $("#exenta").val();
    var otros = $("#others").val();
    var contrans = $("#contrans").val();
    var fovial = $("#fovial").val();
    var retencion_iva = $("#iretenido").val();

    gravada = parseFloat(gravada);
    iva = parseFloat(iva);
    exenta = parseFloat(exenta);
    otros = parseFloat(otros);
    contrans = parseFloat(contrans);
    fovial = parseFloat(fovial);
    retencion_iva = parseFloat(retencion_iva);
    // El IVA Retenido se SUMA al total
    $("#total").val(parseFloat(gravada+iva+exenta+otros+contrans+fovial+retencion_iva).toFixed(5));
};

function updateTotals() {
    // Llamar a la función suma para recalcular totales
    if (typeof suma === 'function') {
        suma();
    }
}

//edit
function sumaedit(){
    var gravada = $("#gravadaedit").val();
    var iva = $("#ivaedit").val();
    var exenta = $("#exentaedit").val();
    var otros = $("#othersedit").val();
    var contrans = $("#contransedit").val();
    var fovial = $("#fovialedit").val();
    var retencion_iva = $("#iretenidoedit").val();

    gravada = parseFloat(gravada);
    iva = parseFloat(iva);
    exenta = parseFloat(exenta);
    otros = parseFloat(otros);
    contrans = parseFloat(contrans);
    fovial = parseFloat(fovial);
    retencion_iva = parseFloat(retencion_iva);
    // El IVA Retenido se SUMA al total
    $("#totaledit").val(parseFloat(gravada+iva+exenta+otros+contrans+fovial+retencion_iva).toFixed(5));
};
   function editpurchase(id){
    //Get data edit Products
    $.ajax({
        url: "getpurchaseid/"+btoa(id),
        method: "GET",
        success: function(response){
            $.each(response, function(index, value) {
                    if(value==null) {
                        value = "0.00";
                    }
                    $('#'+index+'edit').val(value);
                    if(index=='provider_id'){
                        $("#provideredit").val(value).trigger('change');
                    }
                    if(index=='company_id'){
                        $("#companyedit").val(value).trigger('change');
                    }
                    if(index=='periodo'){
                        $("#periodedit").val(value).trigger('change');
                    }
                    if(index=='document_id'){
                        $("#documentedit").val(value).trigger('change');
                    }
                    if(index=='date'){
                        const formattedDate = value ? value.toString().substring(0, 10) : '';
                        value = formattedDate;
                        $('#dateedit').val(formattedDate);
                    }

              });

              // Cargar los detalles de productos de la compra
              if (typeof loadPurchaseDetails === 'function') {
                  loadPurchaseDetails(id);
              }

              $("#updatePurchaseModal").modal("show");
        }
    });
   }



   (function () {
    // Flat Picker
    // --------------------------------------------------------------------
    const flatpickrDate = document.querySelector('date')
    const flatpickrDateedit = document.querySelector('#dateedit')

    // Date
    if (flatpickrDate) {
      flatpickrDate.flatpickr({
        //monthSelectorType: 'static',
        dateFormat: 'd-m-Y'
      });
    }

    //date edit
    if (flatpickrDateedit) {
        flatpickrDateedit.flatpickr({
          //monthSelectorType: 'static',
          dateFormat: 'd-m-Y'
        });
      }
  })();

// ========================================
// FUNCIONES DE FORMULARIOS Y CÁLCULOS
// ========================================

// Función para calcular IVA en formulario de nueva compra
function calculaiva(monto){
    monto=parseFloat(monto*13/100).toFixed(5);
    $("#iva").val(monto);
    suma();
}

// Función para calcular IVA en formulario de edición
function calculaivaedit(monto){
    monto=parseFloat(monto*13/100).toFixed(5);
    $("#ivaedit").val(monto);
    sumaedit();
}

// Función para sumar totales en formulario de nueva compra
function suma(){
    var gravada = $("#gravada").val();
    var iva = $("#iva").val();
    var exenta = $("#exenta").val();
    var otros = $("#others").val();
    var contrans = $("#contrans").val();
    var fovial = $("#fovial").val();
    var retencion_iva = $("#iretenido").val();

    gravada = parseFloat(gravada);
    iva = parseFloat(iva);
    exenta = parseFloat(exenta);
    otros = parseFloat(otros);
    contrans = parseFloat(contrans);
    fovial = parseFloat(fovial);
    retencion_iva = parseFloat(retencion_iva);
    // El IVA Retenido se SUMA al total
    $("#total").val(parseFloat(gravada+iva+exenta+otros+contrans+fovial+retencion_iva).toFixed(5));
}

// Función para sumar totales en formulario de edición
function sumaedit(){
    var gravada = $("#gravadaedit").val();
    var iva = $("#ivaedit").val();
    var exenta = $("#exentaedit").val();
    var otros = $("#othersedit").val();
    var contrans = $("#contransedit").val();
    var fovial = $("#fovialedit").val();
    var retencion_iva = $("#iretenidoedit").val();

    gravada = parseFloat(gravada);
    iva = parseFloat(iva);
    exenta = parseFloat(exenta);
    otros = parseFloat(otros);
    contrans = parseFloat(contrans);
    fovial = parseFloat(fovial);
    retencion_iva = parseFloat(retencion_iva);
    // El IVA Retenido se SUMA al total
    $("#totaledit").val(parseFloat(gravada+iva+exenta+otros+contrans+fovial+retencion_iva).toFixed(5));
}

// ========================================
// FUNCIONES DE EDICIÓN Y ELIMINACIÓN
// ========================================

// Función para editar compra
function editpurchase(id){
    //Get data edit Products
    $.ajax({
        url: "getpurchaseid/"+btoa(id),
        method: "GET",
        success: function(response){
            $.each(response, function(index, value) {
                    if(value==null) {
                        if (['payment_type', 'payment_due_date', 'credit_days'].includes(index)) {
                            value = '';
                        } else {
                            value = "0.00";
                        }
                    }

                    if(index === 'payment_due_date' && value){
                        value = value.toString().substring(0, 10);
                    }

                    $('#'+index+'edit').val(value);
                    if(index=='provider_id'){
                        $("#provideredit").val(value).trigger('change');
                    }
                    if(index=='company_id'){
                        $("#companyedit").val(value).trigger('change');
                    }
                    if(index=='periodo'){
                        $("#periodedit").val(value).trigger('change');
                    }
                    if(index=='document_id'){
                        $("#documentedit").val(value).trigger('change');
                    }
                    if(index=='payment_type'){
                        $("#payment_typeedit").val(value || 'contado').trigger('change');
                    }
                    if(index=='credit_days'){
                        $("#credit_daysedit").val(value).trigger('change');
                    }
                    if(index=='payment_due_date'){
                        $("#payment_due_dateedit").val(value);
                        document.querySelector('#payment_due_dateedit')?.setAttribute('data-manual-override', '1');
                    }

              });

              // Actualizar visibilidad de campos de crédito y recalcular fecha después de cargar valores
              setTimeout(() => {
                  const paymentType = $("#payment_typeedit").val() || 'contado';
                  const creditDaysContainer = $("#credit_days_container_edit");
                  
                  // Mostrar/ocultar campo de días de crédito
                  if (paymentType === 'credito') {
                      creditDaysContainer.show();
                  } else {
                      creditDaysContainer.hide();
                  }
                  
                  // Recalcular fecha de pago
                  const dueDateEl = document.querySelector('#payment_due_dateedit');
                  const paymentTypeEl = document.querySelector('#payment_typeedit');
                  const creditDaysEl = document.querySelector('#credit_daysedit');
                  const baseDateEl = document.querySelector('#dateedit');
                  
                  if (dueDateEl && paymentTypeEl && baseDateEl) {
                      // Solo recalcular si no se estableció manualmente
                      const manualOverride = dueDateEl.dataset.manualOverride === '1';
                      
                      if (!manualOverride && baseDateEl.value) {
                          let baseDateValue = baseDateEl.value;
                          
                          // Convertir DD-MM-YYYY a YYYY-MM-DD si es necesario (formato de flatpickr)
                          if (baseDateValue.match(/^\d{2}-\d{2}-\d{4}$/)) {
                              const parts = baseDateValue.split('-');
                              baseDateValue = `${parts[2]}-${parts[1]}-${parts[0]}`;
                          }
                          
                          const baseDate = new Date(baseDateValue);
                          if (!isNaN(baseDate.getTime())) {
                              if (paymentTypeEl.value === 'credito' && creditDaysEl && creditDaysEl.value) {
                                  const days = parseInt(creditDaysEl.value, 10);
                                  if (!isNaN(days) && days > 0) {
                                      baseDate.setDate(baseDate.getDate() + days);
                                  }
                              }
                              // Si es contado, la fecha de pago es la misma que la fecha de compra
                              dueDateEl.value = baseDate.toISOString().split('T')[0];
                          }
                      }
                  }
                  
                      // Resetear el atributo initialized para permitir reinicialización
                  const paymentTypeEditEl = document.querySelector('#payment_typeedit');
                  if (paymentTypeEditEl) {
                      paymentTypeEditEl.dataset.initialized = 'false';
                  }
                  
                  // Reinicializar la lógica de condiciones de pago para el formulario de edición
                  // Esto asegura que los event listeners funcionen correctamente
                  initializePaymentTermsSection({
                      paymentTypeSelector: '#payment_typeedit',
                      creditDaysContainerSelector: '#credit_days_container_edit',
                      creditDaysSelector: '#credit_daysedit',
                      dueDateSelector: '#payment_due_dateedit',
                      baseDateSelector: '#dateedit'
                  });
              }, 300);

              // Cargar los detalles de productos de la compra
              if (typeof loadPurchaseDetails === 'function') {
                  loadPurchaseDetails(id);
              }

              $("#updatePurchaseModal").modal("show");
        }
    });
}

// Función para eliminar compra
function deletepurchase(id){
    // Verificar que SweetAlert2 esté disponible
    if (typeof Swal === 'undefined') {
        alert('Error: SweetAlert2 no está disponible');
        return;
    }

    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
    });

    swalWithBootstrapButtons.fire({
        title: '¿Eliminar Compra?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, Eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "destroy/"+btoa(id),
                method: "GET",
                success: function(response){
                    if(response.success === true){
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: response.message || 'La compra ha sido eliminada correctamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'No se pudo eliminar la compra. Por favor, contacta al administrador.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al eliminar la compra. Por favor, intenta de nuevo.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire({
                title: 'Cancelado',
                text: 'No se ha eliminado ninguna compra',
                icon: 'info',
                confirmButtonText: 'Aceptar'
            });
        }
    });
}

function initializePaymentTermsSection(config) {
    const paymentTypeEl = document.querySelector(config.paymentTypeSelector);
    const creditDaysContainer = document.querySelector(config.creditDaysContainerSelector);
    const creditDaysEl = document.querySelector(config.creditDaysSelector);
    const dueDateEl = document.querySelector(config.dueDateSelector);
    const baseDateEl = document.querySelector(config.baseDateSelector);

    if (!paymentTypeEl || !dueDateEl || !baseDateEl) {
        return;
    }

    // Evitar duplicar event listeners usando un atributo de datos
    if (paymentTypeEl.dataset.initialized === 'true') {
        return;
    }
    paymentTypeEl.dataset.initialized = 'true';

    if (!dueDateEl.dataset.manualOverride) {
        dueDateEl.dataset.manualOverride = '0';
    }

    const toggleCreditFields = () => {
        if (!creditDaysContainer) {
            return;
        }

        if (paymentTypeEl.value === 'credito') {
            creditDaysContainer.style.display = '';
            // Si no hay valor seleccionado, establecer 30 días por defecto
            if (creditDaysEl && !creditDaysEl.value) {
                creditDaysEl.value = '30';
            }
        } else {
            creditDaysContainer.style.display = 'none';
            if (creditDaysEl) {
                creditDaysEl.value = '';
            }
        }
    };

    const recalculateDueDate = (force = false) => {
        if (!dueDateEl || !baseDateEl || !baseDateEl.value) {
            return;
        }

        if (!force && dueDateEl.dataset.manualOverride === '1') {
            return;
        }

        // Manejar formato DD-MM-YYYY (flatpickr) o YYYY-MM-DD (input date)
        let baseDateValue = baseDateEl.value;
        let baseDate;
        
        // Si el formato es DD-MM-YYYY, convertirlo a YYYY-MM-DD
        if (baseDateValue.match(/^\d{2}-\d{2}-\d{4}$/)) {
            const parts = baseDateValue.split('-');
            baseDateValue = `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
        
        baseDate = new Date(baseDateValue);
        if (isNaN(baseDate.getTime())) {
            return;
        }

        if (paymentTypeEl.value === 'credito' && creditDaysEl && creditDaysEl.value) {
            const days = parseInt(creditDaysEl.value, 10);
            if (!isNaN(days) && days > 0) {
                baseDate.setDate(baseDate.getDate() + days);
            }
        }
        // Si es contado, la fecha de pago es la misma que la fecha de compra (ya está en baseDate)

        dueDateEl.value = baseDate.toISOString().split('T')[0];
    };

    // Event listeners
    paymentTypeEl.addEventListener('change', () => {
        dueDateEl.dataset.manualOverride = '0';
        toggleCreditFields();
        recalculateDueDate(true);
    });

    if (creditDaysEl) {
        creditDaysEl.addEventListener('change', () => {
            dueDateEl.dataset.manualOverride = '0';
            recalculateDueDate(true);
        });
    }

    baseDateEl.addEventListener('change', () => {
        dueDateEl.dataset.manualOverride = '0';
        recalculateDueDate(true);
    });

    dueDateEl.addEventListener('input', () => {
        dueDateEl.dataset.manualOverride = '1';
    });

    // Inicializar estado inicial
    toggleCreditFields();
    recalculateDueDate(true);
}

