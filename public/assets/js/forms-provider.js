/**
 * Form Picker
 */

'use strict';
$(document).ready(function (){

    //input mask
    $("#ncr").inputmask("999999-9");



    $("#tel1").inputmask("9999-9999");
    $("#tel2").inputmask("9999-9999");

    $("#email").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toLowerCase());
    });

    // Validación en tiempo real para NCR
    $("#ncr").on("change blur", function() {
        validateNCR($(this).val());
    });

    // Validación en tiempo real para NIT
    $("#nit").on("change blur", function() {
        validateNIT($(this).val());
    });

    // Validación en tiempo real para NCR en edición
    $("#ncrupdate").on("change blur", function() {
        var providerId = $("#idupdate").val();
        validateNCR($(this).val(), providerId);
    });

    // Validación en tiempo real para NIT en edición
    $("#nitupdate").on("change blur", function() {
        var providerId = $("#idupdate").val();
        validateNIT($(this).val(), providerId);
    });

    // Validación cuando se limpian los campos
    $("#ncr, #nit").on("input", function() {
        if ($(this).val() === '') {
            // Si el campo se limpia, remover clases de error
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();

            // Actualizar estado de validación
            if ($(this).attr('id') === 'ncr') {
                window.ncrValid = true;
            } else if ($(this).attr('id') === 'nit') {
                window.nitValid = true;
            }
            checkFormValidity();
        }
    });

    $("#ncrupdate, #nitupdate").on("input", function() {
        if ($(this).val() === '') {
            // Si el campo se limpia, remover clases de error
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();

            // Actualizar estado de validación
            if ($(this).attr('id') === 'ncrupdate') {
                window.ncrUpdateValid = true;
            } else if ($(this).attr('id') === 'nitupdate') {
                window.nitUpdateValid = true;
            }
            checkFormValidity();
        }
    });

    // Variables globales para el estado de validación
    window.ncrValid = false;
    window.nitValid = false;
    window.ncrUpdateValid = false;
    window.nitUpdateValid = false;

    // Función para verificar si el formulario puede ser enviado
    window.checkFormValidity = function() {
        var createFormValid = window.ncrValid && window.nitValid;
        var updateFormValid = window.ncrUpdateValid && window.nitUpdateValid;

        // Habilitar/deshabilitar botón de crear
        if ($('#btnCrear').length) {
            $('#btnCrear').prop('disabled', !createFormValid);
            if (!createFormValid) {
                $('#btnCrear').attr('title', 'Complete la validación de NCR y NIT');
            } else {
                $('#btnCrear').attr('title', '');
            }
        }

        // Habilitar/deshabilitar botón de actualizar
        if ($('#btnActualizar').length) {
            $('#btnActualizar').prop('disabled', !updateFormValid);
            if (!updateFormValid) {
                $('#btnActualizar').attr('title', 'Complete la validación de NCR y NIT');
            } else {
                $('#btnActualizar').attr('title', '');
            }
        }
    }

    //input mask
    $("#ncredit").inputmask("999999-9");
    $("#nitedit").inputmask("99999999-9");
    $("#tel1edit").inputmask("9999-9999");
    $("#tel2edit").inputmask("9999-9999");

    $("#email-edit").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toLowerCase());
    });
    //Get companies avaibles
    var iduser = $('#iduser').val();
    $.ajax({
        url: "/company/getCompanybyuser/"+iduser,
        method: "GET",
        success: function(response){
            $('#company').append('<option value="0">Seleccione</option>');
            $.each(response, function(index, value) {
                $('#company').append('<option selected value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                $('#companyupdate').append('<option selected value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
              });
        }
    });
    getpaises();

    // Validación inicial para el formulario de crear
    checkFormValidity();

    // Cuando se abre el modal de crear, validar campos existentes
    $('#addProviderModal').on('shown.bs.modal', function() {
        // Validar campos que ya tengan valor
        if ($('#ncr').val()) {
            validateNCR($('#ncr').val());
        }
        if ($('#nit').val()) {
            validateNIT($('#nit').val());
        }
        checkFormValidity();
    });

    // Cuando se abre el modal de editar, validar campos existentes
    $('#updateProviderModal').on('shown.bs.modal', function() {
        // Validar campos que ya tengan valor
        if ($('#ncrupdate').val()) {
            var providerId = $("#idupdate").val();
            validateNCR($('#ncrupdate').val(), providerId);
        }
        if ($('#nitupdate').val()) {
            var providerId = $("#idupdate").val();
            validateNIT($('#nitupdate').val(), providerId);
        }
        checkFormValidity();
    });

    // Resetear estado de validación cuando se cierran los modales
    $('#addProviderModal, #updateProviderModal').on('hidden.bs.modal', function() {
        // Limpiar clases de error
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('.validating').removeClass('validating');

        // Resetear variables de validación
        window.ncrValid = true;
        window.nitValid = true;
        window.ncrUpdateValid = true;
        window.nitUpdateValid = true;

        // Habilitar botones
        $('#btnCrear, #btnActualizar').prop('disabled', false).attr('title', '');
    });
});

function nitDuiMask(inputField) {
    var separator = '-';
    var nitPattern;
    if (inputField.value.length == 9) {
        nitPattern = new Array(8, 1);
    } else {
        nitPattern = new Array(4, 6, 3, 1);
    }
    mask(inputField, separator, nitPattern, true);
}

function NRCMask(inputField) {
    var separator = '-';
    var nrcPattern;
    if (inputField.value.length == 6) {
        nrcPattern = new Array(5, 1);
    } else {
        nrcPattern = new Array(6, 1);
    }
    mask(inputField, separator, nrcPattern, true);
}

function mask(inputField, separator, pattern, nums) {
    var val;
    var largo;
    var val2;
    var r;
    var z;
    var val3;
    var s;
    var q;
    if (inputField.valant != inputField.value) {
        val = inputField.value;
        largo = val.length;
        val = val.split(separator);
        val2 = '';
        for (r = 0; r < val.length; r++) {
            val2 += val[r]
        }
        if (nums) {
            for (z = 0; z < val2.length; z++) {
                if (isNaN(val2.charAt(z))) {
                    letra = new RegExp(val2.charAt(z), "g")
                    val2 = val2.replace(letra, "")
                }
            }
        }
        val = ''
        val3 = new Array()
        for (s = 0; s < pattern.length; s++) {
            val3[s] = val2.substring(0, pattern[s])
            val2 = val2.substr(pattern[s])
        }
        for (q = 0; q < val3.length; q++) {
            if (q == 0) {
                val = val3[q]
            } else {
                if (val3[q] != "") {
                    val += separator + val3[q]
                }
            }
        }
        inputField.value = val
        inputField.valant = val
    }

}

function getpaises(selected="",type=""){
    if(type=='edit'){
        $.ajax({
            url: "/getcountry",
            method: "GET",
            success: function(response){
                $.each(response, function(index, value) {
                    if(selected!="" && value.id==selected){
                        $('#countryedit').append('<option value="'+value.id+'" selected>'+value.name.toUpperCase()+'</option>');
                    }else{
                        $('#countryedit').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                    }

                  });
            }
        });
    }else{
        $.ajax({
            url: "/getcountry",
            method: "GET",
            success: function(response){
                $.each(response, function(index, value) {
                    $('#country').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                  });
            }
        });
    }

}

(function () {
  // Flat Picker
  // --------------------------------------------------------------------
  const flatpickrDate = document.querySelector('#birthday')

  // Date
  if (flatpickrDate) {
    flatpickrDate.flatpickr({
      //monthSelectorType: 'static',
      dateFormat: 'd-m-yy'
    });
  }
})();

function getdepartamentos(pais, type="", selected){
    //Get countrys avaibles
    if(type=='edit'){
       $.ajax({
           url: "/getdepartment/"+btoa(pais),
           method: "GET",
           success: function(response){
               $('#departamentedit').find('option:not(:first)').remove();
               $.each(response, function(index, value) {
                   if(selected!="" && value.id==selected){
                       $('#departamentedit').append('<option value="'+value.id+'" selected>'+value.name+'</option>');
                   }else{
                       $('#departamentedit').append('<option value="'+value.id+'">'+value.name+'</option>');
                   }

                 });
           }
       });
    }else{
       $.ajax({
           url: "/getdepartment/"+btoa(pais),
           method: "GET",
           success: function(response){
               $('#departament').find('option:not(:first)').remove();
               $.each(response, function(index, value) {
                   $('#departament').append('<option value="'+value.id+'">'+value.name+'</option>');
                 });
           }
       });
    }
   }

   function getmunicipio(dep, type="", selected){
    if(type=='edit'){
//Get countrys avaibles
$.ajax({
    url: "/getmunicipality/"+btoa(dep),
    method: "GET",
    success: function(response){
     $('#municipioedit').find('option:not(:first)').remove();
        $.each(response, function(index, value) {
            if(selected!=="" && value.id==selected){
                $('#municipioedit').append('<option value="'+value.id+'" selected>'+value.name+'</option>');
            }else{
                $('#municipioedit').append('<option value="'+value.id+'">'+value.name+'</option>');
            }

          });
    }
});
    }else{
//Get countrys avaibles
$.ajax({
    url: "/getmunicipality/"+btoa(dep),
    method: "GET",
    success: function(response){
     $('#municipio').find('option:not(:first)').remove();
        $.each(response, function(index, value) {
            $('#municipio').append('<option value="'+value.id+'">'+value.name+'</option>');
          });
    }
});
    }
   }

   function llamarselected(pais, departamento, municipio){
    getpaises(pais,'edit');
    getdepartamentos(pais,'edit', departamento);
    getmunicipio(departamento, 'edit', municipio);
    }

   function editProvider(id){
    //Get data edit providers
    $.ajax({
        url: "getproviderid/"+btoa(id),
        method: "GET",
        success: function(response){
            llamarselected(response[0]['paisid'],response[0]['departamentoid'],response[0]['municipioid']);
            $.each(response[0], function(index, value) {
                    $('#'+index+'update').val(value);
                    if(index=='companyid'){
                        $("#companyupdate option[value='"+ value  +"']").attr("selected", true);
                    }

              });
              $("#updateProviderModal").modal("show");
        }
    });
   }

   function deleteProvider(id){
    const swalWithBootstrapButtons = Swal.mixin({
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
      }).then((result) => {
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
                              }).then((result) => {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                  location.reload();
                                }
                              })

                        }else if(response.res==0){
                            swalWithBootstrapButtons.fire(
                                'No se puede eliminar',
                                response.message || 'El proveedor tiene registros asociados (productos o compras).',
                                'warning'
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
            'No hemos hecho ninguna accion :)',
            'error'
          )
        }
      })
   }

      // Función para validar NCR único
   function validateNCR(ncr, providerId = null) {
       if (!ncr || ncr.trim() === '') {
           // Si el campo está vacío, se considera válido
           if (providerId) {
               window.ncrUpdateValid = true;
           } else {
               window.ncrValid = true;
           }
           checkFormValidity();
           return;
       }

       // Mostrar indicador de validación
       var inputField = providerId ? '#ncrupdate' : '#ncr';
       $(inputField).addClass('validating');

       // Deshabilitar botón temporalmente durante la validación
       if (providerId) {
           window.ncrUpdateValid = false;
       } else {
           window.ncrValid = false;
       }
       checkFormValidity();

       $.ajax({
           url: '/provider/validate-ncr',
           method: 'POST',
           data: {
               ncr: ncr,
               provider_id: providerId,
               _token: $('meta[name="csrf-token"]').attr('content')
           },
           success: function(response) {
               var inputField = providerId ? '#ncrupdate' : '#ncr';
               var feedbackField = providerId ? '#ncrupdate-feedback' : '#ncr-feedback';

               // Remover indicador de validación
               $(inputField).removeClass('validating');

               if (!response.valid) {
                   $(inputField).addClass('is-invalid');
                   if (!$(feedbackField).length) {
                       $(inputField).after('<div class="invalid-feedback d-block" id="' + (providerId ? 'ncrupdate-feedback' : 'ncr-feedback') + '">' + response.message + '</div>');
                   } else {
                       $(feedbackField).text(response.message);
                   }
                   Swal.fire({
                       icon: 'error',
                       title: 'NCR duplicado',
                       text: response.message || 'Ya existe un proveedor con este NCR.',
                       confirmButtonText: 'Entendido'
                   });

                   // Actualizar estado de validación
                   if (providerId) {
                       window.ncrUpdateValid = false;
                   } else {
                       window.ncrValid = false;
                   }
               } else {
                   $(inputField).removeClass('is-invalid');
                   $(feedbackField).remove();

                   // Actualizar estado de validación
                   if (providerId) {
                       window.ncrUpdateValid = true;
                   } else {
                       window.ncrValid = true;
                   }
               }

               // Verificar estado del formulario
               checkFormValidity();
           },
           error: function() {
               // En caso de error, considerar como inválido
               var inputField = providerId ? '#ncrupdate' : '#ncr';
               $(inputField).removeClass('validating');

               if (providerId) {
                   window.ncrUpdateValid = false;
               } else {
                   window.ncrValid = false;
               }
               checkFormValidity();
           }
       });
   }

      // Función para validar NIT único
   function validateNIT(nit, providerId = null) {
       if (!nit || nit.trim() === '') {
           // Si el campo está vacío, se considera válido
           if (providerId) {
               window.nitUpdateValid = true;
           } else {
               window.nitValid = true;
           }
           checkFormValidity();
           return;
       }

       // Mostrar indicador de validación
       var inputField = providerId ? '#nitupdate' : '#nit';
       $(inputField).addClass('validating');

       // Deshabilitar botón temporalmente durante la validación
       if (providerId) {
           window.nitUpdateValid = false;
       } else {
           window.nitValid = false;
       }
       checkFormValidity();

       $.ajax({
           url: '/provider/validate-nit',
           method: 'POST',
           data: {
               nit: nit,
               provider_id: providerId,
               _token: $('meta[name="csrf-token"]').attr('content')
           },
           success: function(response) {
               var inputField = providerId ? '#nitupdate' : '#nit';
               var feedbackField = providerId ? '#nitupdate-feedback' : '#nit-feedback';

               // Remover indicador de validación
               $(inputField).removeClass('validating');

               if (!response.valid) {
                   $(inputField).addClass('is-invalid');
                   if (!$(feedbackField).length) {
                       $(inputField).after('<div class="invalid-feedback d-block" id="' + (providerId ? 'nitupdate-feedback' : 'nit-feedback') + '">' + response.message + '</div>');
                   } else {
                       $(feedbackField).text(response.message);
                   }
                   Swal.fire({
                       icon: 'error',
                       title: 'NIT duplicado',
                       text: response.message || 'Ya existe un proveedor con este NIT.',
                       confirmButtonText: 'Entendido'
                   });

                   // Actualizar estado de validación
                   if (providerId) {
                       window.nitUpdateValid = false;
                   } else {
                       window.nitValid = false;
                   }
               } else {
                   $(inputField).removeClass('is-invalid');
                   $(feedbackField).remove();

                   // Actualizar estado de validación
                   if (providerId) {
                       window.nitUpdateValid = true;
                   } else {
                       window.nitValid = true;
                   }
               }

               // Verificar estado del formulario
               checkFormValidity();
           },
           error: function() {
               // En caso de error, considerar como inválido
               var inputField = providerId ? '#nitupdate' : '#nit';
               $(inputField).removeClass('validating');

               if (providerId) {
                   window.nitUpdateValid = false;
               } else {
                   window.nitValid = false;
               }
               checkFormValidity();
           }
       });
   }

    // Prevenir submit si no está validado
    $('#addProviderModal form').on('submit', function(e) {
        if (!window.ncrValid || !window.nitValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validación requerida',
                text: 'Debe validar NCR y NIT antes de crear el proveedor.',
                confirmButtonText: 'Entendido'
            });
            return false;
        }
    });

    $('#updateProviderModal form').on('submit', function(e) {
        if (!window.ncrUpdateValid || !window.nitUpdateValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validación requerida',
                text: 'Debe validar NCR y NIT antes de actualizar el proveedor.',
                confirmButtonText: 'Entendido'
            });
            return false;
        }
    });
