/**
 * Form Picker
 */

"use strict";
$(document).ready(function () {
    $("#btnsavenewclient").prop("disabled", true);

    $("#tel1").inputmask("9999-9999");
    $("#tel2").inputmask("9999-9999");

    $("#ncredit").inputmask("999999-9");
    // Removida máscara fija de nitedit para permitir tanto DUI como NIT dinámicamente
    // $("#nitedit").inputmask("99999999-9");
    $("#tel1edit").inputmask("9999-9999");
    $("#tel2edit").inputmask("9999-9999");

                // Validación única para NIT (solo en blur para evitar duplicados)
    $("#nit").on('blur', function () {
        validateNitField($(this));
    });

    //si es extranjero
    $("#pasaporte").on('blur', function () {
        var key = $("#pasaporte").val();
        var esextranjero = $('#extranjero').is(':checked');

        // Siempre deshabilitar botón al cambiar
        $("#btnsavenewclient").prop("disabled", true);
        $("#pasaporte").removeClass("is-invalid is-valid");

        // Si está vacío o no es extranjero, no validar
        if (!key || key.trim() === '' || !esextranjero) {
            return;
        }

        // Limpiar el valor removiendo guiones y espacios para la validación
        var cleanKey = cleanMaskValue(key);

        // Si después de limpiar está vacío, no validar
        if (!cleanKey || cleanKey.trim() === '') {
            return;
        }

        var tpersona = "E";
        $.ajax({
            url: "/client/keyclient/" + btoa(cleanKey) + "/" + btoa(tpersona),
            method: "GET",
            success: function (response) {
                if (response.val) {
                    Swal.fire({
                        title: "Cliente Duplicado",
                        text: response.message,
                        icon: "warning",
                        confirmButtonText: "Entendido"
                    });
                    $("#pasaporte").addClass("is-invalid");
                    $("#btnsavenewclient").prop("disabled", true);
                } else {
                    $("#pasaporte").addClass("is-valid");
                    $("#btnsavenewclient").prop("disabled", false);
                }
            },
            error: function() {
                Swal.fire({
                    title: "Error",
                    text: "Error al validar el cliente",
                    icon: "error",
                    confirmButtonText: "Entendido"
                });
                $("#btnsavenewclient").prop("disabled", true);
            }
        });
    });

    // Validación única para NCR (solo en blur para evitar duplicados)
    $("#ncr").on('blur', function () {
        validateNcrField($(this));
    });

    // Validaciones para formulario de edición
    $("#nitedit").on('blur', function () {
        validateNitEditField($(this));
    });

    $("#ncredit").on('blur', function () {
        validateNcrEditField($(this));
    });

    $("#pasaporteedit").on('blur', function () {
        validatePasaporteEditField($(this));
    });

    //Get companies avaibles
    $.ajax({
        url: "/company/getCompany",
        method: "GET",
        success: function (response) {
            let companyselected = $("#companyselected").val();
            $.each(response, function (index, value) {
                $("#selectcompany").append(
                    '<option value="' +
                        value.id +
                        '">' +
                        value.name +
                        "</option>"
                );
            });
            $("#selectcompany option[value=" + companyselected + "]").attr(
                "selected",
                true
            );
        },
    });

    // Manejar cambio de tipo de persona
    $("#tpersona").change(function() {
        var tpersona = $(this).val();

        // Limpiar validaciones previas y deshabilitar botón
        $("#nit, #ncr, #pasaporte").removeClass("is-invalid is-valid");
        $("#btnsavenewclient").prop("disabled", true);

        // Mostrar/ocultar campos según tipo de persona
        if (tpersona === "N") {
            $("#siextranjeroduinit").show();
            $("#siextranjero").hide();
            $("#ncr").closest(".mb-3").show();
        } else if (tpersona === "J") {
            $("#siextranjeroduinit").show();
            $("#siextranjero").hide();
            $("#ncr").closest(".mb-3").show();
        }

        // Verificar si se puede habilitar el botón
        setTimeout(function() {
            checkFormReadiness();
        }, 100);
    });

    // Manejar cambio de extranjero
    $("#extranjero").change(function() {
        var esextranjero = $(this).is(':checked');

        // Limpiar validaciones previas y deshabilitar botón
        $("#nit, #ncr, #pasaporte").removeClass("is-invalid is-valid");
        $("#btnsavenewclient").prop("disabled", true);

        if (esextranjero) {
            $("#siextranjeroduinit").hide();
            $("#siextranjero").show();
            $("#ncr").closest(".mb-3").hide();
        } else {
            $("#siextranjeroduinit").show();
            $("#siextranjero").hide();
            var tpersona = $("#tpersona").val();
            if (tpersona === "J") {
                $("#ncr").closest(".mb-3").show();
            }
        }

        // Verificar si se puede habilitar el botón
        setTimeout(function() {
            checkFormReadiness();
        }, 100);
    });

    // Listeners para campos obligatorios
    $("#email, #tel1, #tel2, #firstname, #firstlastname, #comercial_name, #name_contribuyente, #country, #departament, #municipio, #address, #tipocontribuyente, #acteconomica, #contribuyente").on('input change', function() {
        setTimeout(function() {
            checkFormReadiness();
        }, 100);
    });

    if ($("#companyselected").val() == 0) {
        $("button.add-new").attr("disabled", true);
    }
    getpaises();
});





// Función para limpiar valores removiendo guiones y espacios
function cleanMaskValue(value) {
    return value ? value.replace(/[-\s]/g, '') : '';
}

// Función para validar NIT sin afectar el foco
function validateNitField($field) {
    var key = $field.val();
    var tpersona = $("#tpersona").val();
    var esextranjero = $('#extranjero').is(':checked');



    // Siempre deshabilitar botón al validar
    $("#btnsavenewclient").prop("disabled", true);
    $field.removeClass("is-invalid is-valid");

    // Si está vacío, no validar
    if (!key || key.trim() === '') {
        return;
    }

    // Limpiar el valor removiendo guiones y espacios para la validación
    var cleanKey = cleanMaskValue(key);

    // Si después de limpiar está vacío, no validar
    if (!cleanKey || cleanKey.trim() === '') {
        return;
    }

    // Validar NIT para persona natural (no extranjero) O persona jurídica
    if ((tpersona === "N" && !esextranjero) || tpersona === "J") {
        var url = "/client/keyclient/" + btoa(cleanKey) + "/" + btoa(tpersona);

        // Para personas jurídicas, especificar que estamos validando NIT
        if (tpersona === "J") {
            url += "/" + btoa("nit");
        }

        $.ajax({
            url: url,
            method: "GET",
            success: function (response) {
                if (response.val) {
                    Swal.fire({
                        title: "Cliente Duplicado",
                        text: response.message,
                        icon: "warning",
                        confirmButtonText: "Entendido"
                    });
                    $field.addClass("is-invalid");
                    $("#btnsavenewclient").prop("disabled", true);
                                    } else {
                        $field.addClass("is-valid");
                        checkFormReadiness();
                    }
            },
            error: function() {
                Swal.fire({
                    title: "Error",
                    text: "Error al validar el cliente",
                    icon: "error",
                    confirmButtonText: "Entendido"
                });
                $("#btnsavenewclient").prop("disabled", true);
            }
        });
    }
}

// Función para validar NCR sin afectar el foco
function validateNcrField($field) {
    var key = $field.val();
    var tpersona = $("#tpersona").val();
    var esContribuyente = $('#contribuyente').is(':checked');



    // Siempre deshabilitar botón al validar
    $("#btnsavenewclient").prop("disabled", true);
    $field.removeClass("is-invalid is-valid");

    // Validar NRC si:
    // - Persona jurídica, o
    // - Persona natural contribuyente
    var debeValidarNcr = (tpersona === "J") || (tpersona === "N" && esContribuyente);

    // Si está vacío o no aplica la validación, salir
    if (!key || key.trim() === '' || !debeValidarNcr) {
        return;
    }

    // Limpiar el valor removiendo guiones y espacios para la validación
    var cleanKey = cleanMaskValue(key);

    // Si después de limpiar está vacío, no validar
    if (!cleanKey || cleanKey.trim() === '') {
        return;
    }

    // Construir URL; si es natural contribuyente, especificar que es NRC
    var url = "/client/keyclient/" + btoa(cleanKey) + "/" + btoa(tpersona);
    if (tpersona === "N") {
        url += "/" + btoa("ncr");
    }

    $.ajax({
        url: url,
        method: "GET",
        success: function (response) {
            if (response.val) {
                Swal.fire({
                    title: "Cliente Duplicado",
                    text: response.message,
                    icon: "warning",
                    confirmButtonText: "Entendido"
                });
                $field.addClass("is-invalid");
                $("#btnsavenewclient").prop("disabled", true);
                            } else {
                    $field.addClass("is-valid");
                    checkFormReadiness();
                }
        },
        error: function() {
            Swal.fire({
                title: "Error",
                text: "Error al validar el cliente",
                icon: "error",
                confirmButtonText: "Entendido"
            });
            $("#btnsavenewclient").prop("disabled", true);
        }
    });
}

// Función para validar NIT en edición sin afectar el foco
function validateNitEditField($field) {
    var key = $field.val();
    var tpersona = $("#tpersonaedit").val();
    var esextranjero = $('#extranjeroedit').is(':checked');
    var clientId = $("#idedit").val();

    // Siempre deshabilitar botón al validar
    $("#btnupdate").prop("disabled", true);
    $field.removeClass("is-invalid is-valid");

    // Si está vacío, no validar
    if (!key || key.trim() === '') {
        return;
    }

    // Limpiar el valor removiendo guiones y espacios para la validación
    var cleanKey = cleanMaskValue(key);

    // Si después de limpiar está vacío, no validar
    if (!cleanKey || cleanKey.trim() === '') {
        return;
    }

    // Validar NIT para persona natural (no extranjero) O persona jurídica
    if ((tpersona === "N" && !esextranjero) || tpersona === "J") {
        var url = "/client/keyclient/" + btoa(cleanKey) + "/" + btoa(tpersona);

        // Para personas jurídicas, especificar que estamos validando NIT
        if (tpersona === "J") {
            url += "/" + btoa("nit");
        } else {
            url += "/" + btoa("nit"); // Para natural también necesitamos especificar el campo
        }

        // Agregar clientId para excluir el cliente actual en edición
        url += "/" + btoa(clientId);

        $.ajax({
            url: url,
            method: "GET",
            success: function (response) {
                if (response.val) {
                    Swal.fire({
                        title: "Cliente Duplicado",
                        text: response.message,
                        icon: "warning",
                        confirmButtonText: "Entendido"
                    });
                    $field.addClass("is-invalid");
                    $("#btnupdate").prop("disabled", true);
                } else {
                    $field.addClass("is-valid");
                    checkEditFormReadiness();
                }
            },
            error: function() {
                Swal.fire({
                    title: "Error",
                    text: "Error al validar el cliente",
                    icon: "error",
                    confirmButtonText: "Entendido"
                });
                $("#btnupdate").prop("disabled", true);
            }
        });
    }
}

// Función para validar NCR en edición sin afectar el foco
function validateNcrEditField($field) {
    var key = $field.val();
    var tpersona = $("#tpersonaedit").val();
    var esContribuyente = $('#contribuyenteedit').is(':checked');
    var clientId = $("#idedit").val();

    // Siempre deshabilitar botón al validar
    $("#btnupdate").prop("disabled", true);
    $field.removeClass("is-invalid is-valid");

    // Validar NCR si:
    // - Persona jurídica, o
    // - Persona natural contribuyente
    var debeValidarNcr = (tpersona === "J") || (tpersona === "N" && esContribuyente);

    // Si está vacío o no aplica la validación, salir
    if (!key || key.trim() === '' || !debeValidarNcr) {
        return;
    }

    // Limpiar el valor removiendo guiones y espacios para la validación
    var cleanKey = cleanMaskValue(key);

    // Si después de limpiar está vacío, no validar
    if (!cleanKey || cleanKey.trim() === '') {
        return;
    }

    // Construir URL; si es natural contribuyente, especificar que es NRC
    var url = "/client/keyclient/" + btoa(cleanKey) + "/" + btoa(tpersona);
    if (tpersona === "N") {
        url += "/" + btoa("ncr");
    } else {
        url += "/" + btoa("ncr"); // Para jurídica también especificar campo
    }

    // Agregar clientId para excluir el cliente actual en edición
    url += "/" + btoa(clientId);

    $.ajax({
        url: url,
        method: "GET",
        success: function (response) {
            if (response.val) {
                Swal.fire({
                    title: "Cliente Duplicado",
                    text: response.message,
                    icon: "warning",
                    confirmButtonText: "Entendido"
                });
                $field.addClass("is-invalid");
                $("#btnupdate").prop("disabled", true);
            } else {
                $field.addClass("is-valid");
                checkEditFormReadiness();
            }
        },
        error: function() {
            Swal.fire({
                title: "Error",
                text: "Error al validar el cliente",
                icon: "error",
                confirmButtonText: "Entendido"
            });
            $("#btnupdate").prop("disabled", true);
        }
    });
}

// Función para validar pasaporte en edición sin afectar el foco
function validatePasaporteEditField($field) {
    var key = $field.val();
    var esextranjero = $('#extranjeroedit').is(':checked');
    var clientId = $("#idedit").val();

    // Siempre deshabilitar botón al validar
    $("#btnupdate").prop("disabled", true);
    $field.removeClass("is-invalid is-valid");

    // Si está vacío o no es extranjero, no validar
    if (!key || key.trim() === '' || !esextranjero) {
        return;
    }

    // Limpiar el valor removiendo guiones y espacios para la validación
    var cleanKey = cleanMaskValue(key);

    // Si después de limpiar está vacío, no validar
    if (!cleanKey || cleanKey.trim() === '') {
        return;
    }

    var tpersona = "E";
    var url = "/client/keyclient/" + btoa(cleanKey) + "/" + btoa(tpersona) + "/" + btoa("pasaporte") + "/" + btoa(clientId);

    $.ajax({
        url: url,
        method: "GET",
        success: function (response) {
            if (response.val) {
                Swal.fire({
                    title: "Cliente Duplicado",
                    text: response.message,
                    icon: "warning",
                    confirmButtonText: "Entendido"
                });
                $field.addClass("is-invalid");
                $("#btnupdate").prop("disabled", true);
            } else {
                $field.addClass("is-valid");
                checkEditFormReadiness();
            }
        },
        error: function() {
            Swal.fire({
                title: "Error",
                text: "Error al validar el cliente",
                icon: "error",
                confirmButtonText: "Entendido"
            });
            $("#btnupdate").prop("disabled", true);
        }
    });
}

// Función para verificar si el formulario de edición está listo
function checkEditFormReadiness() {
    // Si hay campos marcados como inválidos por duplicados, no permitir
    if ($(".is-invalid").length > 0) {
        $("#btnupdate").prop("disabled", true);
        return;
    }

    // Si todos los campos están bien, habilitar el botón
    $("#btnupdate").prop("disabled", false);
}

// Manejar envío del formulario de edición
$(document).ready(function() {
    // Interceptar envío del formulario de edición
    $("#offcanvasUpdateClient form").on('submit', function(e) {
        e.preventDefault();

        // Asegurar que los campos ocultos estén actualizados antes de enviar
        if ($("#contribuyenteedit").is(":checked")) {
            $("#contribuyenteeditvalor").val("1");
        } else {
            $("#contribuyenteeditvalor").val("0");
        }

        if ($("#agente_retencionedit").is(":checked")) {
            $("#agente_retencionedit_hidden").val("1");
        } else {
            $("#agente_retencionedit_hidden").val("0");
        }

        // Debug: Verificar valores antes de enviar

        // Verificar si hay campos inválidos
        if ($(".is-invalid").length > 0) {
            Swal.fire({
                title: "Error de Validación",
                text: "Por favor corrija los campos marcados en rojo antes de continuar",
                icon: "error",
                confirmButtonText: "Entendido"
            });
            return;
        }

        // Mostrar loading
        Swal.fire({
            title: "Actualizando...",
            text: "Por favor espere mientras se actualiza el cliente",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Enviar formulario con AJAX
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                // Usar el mensaje del servidor si está disponible, sino usar uno por defecto
                const message = response.message || "Cliente actualizado exitosamente";
                Swal.fire({
                    title: "¡Éxito!",
                    text: message,
                    icon: "success",
                    confirmButtonText: "Entendido"
                }).then(() => {
                    // Recargar la página para mostrar los cambios
                    location.reload();
                });
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Error al actualizar el cliente';

                if (xhr.responseJSON && xhr.responseJSON.messages) {
                    // Si hay mensajes de validación específicos
                    errorMessage = xhr.responseJSON.messages.join('\n');
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    // Si hay un error general
                    errorMessage = xhr.responseJSON.error;
                }

                Swal.fire({
                    title: "Error de Validación",
                    text: errorMessage,
                    icon: "error",
                    confirmButtonText: "Entendido"
                });
            }
        });
    });

    // Interceptar envío del formulario de NUEVO cliente (agregar) para enviar por AJAX y mostrar SWAL
    $("#addNewClientForm").on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);

        // Validar campos obligatorios ANTES de enviar (siempre mostrar alerta cuando falten)
        var requiredErrors = validateRequiredFields($form);
        if (requiredErrors && requiredErrors.length > 0) {
            Swal.fire({
                title: "Campos obligatorios",
                html: requiredErrors.join("<br>"),
                icon: "warning",
                confirmButtonText: "Entendido"
            });
            return;
        }

        if ($form.find(".is-invalid").length > 0) {
            Swal.fire({
                title: "Error de validación",
                text: "Corrija los campos marcados en rojo (por ejemplo, NIT/NRC o pasaporte duplicado) antes de continuar.",
                icon: "error",
                confirmButtonText: "Entendido"
            });
            return;
        }

        Swal.fire({
            title: "Guardando...",
            text: "Por favor espere mientras se guarda el cliente",
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                var message = (response && response.message) ? response.message : "Cliente agregado correctamente.";
                Swal.fire({
                    title: "¡Éxito!",
                    text: message,
                    icon: "success",
                    confirmButtonText: "Entendido"
                }).then(function() {
                    if (response && response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        location.reload();
                    }
                });
            },
            error: function(xhr, status, error) {
                var errorMessage = "Error al agregar el cliente.";

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.messages && xhr.responseJSON.messages.length) {
                        errorMessage = xhr.responseJSON.messages.join("\n");
                    } else if (xhr.responseJSON.errors && typeof xhr.responseJSON.errors === 'object') {
                        var errs = xhr.responseJSON.errors;
                        errorMessage = Object.keys(errs).map(function(k) {
                            return (Array.isArray(errs[k]) ? errs[k].join(' ') : errs[k]);
                        }).join("\n");
                    } else if (xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                        if (xhr.responseJSON.message) {
                            errorMessage += ": " + xhr.responseJSON.message;
                        }
                    } else if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                } else if (xhr.responseText) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.message) errorMessage = data.message;
                        else if (data.error) errorMessage = data.error;
                    } catch (err) {}
                }

                Swal.fire({
                    title: "Error",
                    text: errorMessage,
                    icon: "error",
                    confirmButtonText: "Entendido"
                });
            }
        });
    });
});

// Función para validar campos obligatorios antes de enviar.
// $formContext: opcional, jQuery del formulario #addNewClientForm para validar solo sus campos.
function validateRequiredFields($formContext) {
    var find = function(sel) {
        return ($formContext && $formContext.length) ? $formContext.find(sel) : $(sel);
    };

    var errors = [];
    var tpersona = find("#tpersona").val();
    var esextranjero = find("#extranjero").is(':checked');
    var escontribuyente = find("#contribuyente").is(':checked');

    // Limpiar clases de validación previas solo dentro del formulario
    find("#email, #tel1, #tel2, #firstname, #firstlastname, #comercial_name, #name_contribuyente, #pasaporte, #tpersona, #country, #departament, #municipio, #address, #nit, #ncr, #tipocontribuyente, #acteconomica").removeClass("is-invalid");

    // Campos SIEMPRE obligatorios
    if (!tpersona || tpersona === "0") {
        find("#tpersona").addClass("is-invalid");
        errors.push("Debe seleccionar un tipo de cliente");
    }

    if (!find("#email").val() || find("#email").val().trim() === '') {
        find("#email").addClass("is-invalid");
        errors.push("El correo electrónico es obligatorio");
    }

    // Al menos un teléfono
    var tel1 = find("#tel1").val();
    var tel2 = find("#tel2").val();
    if ((!tel1 || String(tel1).trim() === '') && (!tel2 || String(tel2).trim() === '')) {
        find("#tel1").addClass("is-invalid");
        find("#tel2").addClass("is-invalid");
        errors.push("Debe ingresar al menos un teléfono");
    }

    // Ubicación obligatoria (incluir "Seleccione" cuando el option no tiene value)
    var countryVal = find("#country").val();
    if (!countryVal || countryVal === "0" || countryVal === "" || countryVal === "Seleccione") {
        find("#country").addClass("is-invalid");
        errors.push("El país es obligatorio");
    }
    var deptVal = find("#departament").val();
    if (!deptVal || deptVal === "0" || deptVal === "" || deptVal === "Seleccione") {
        find("#departament").addClass("is-invalid");
        errors.push("El departamento es obligatorio");
    }
    var muniVal = find("#municipio").val();
    if (!muniVal || muniVal === "0" || muniVal === "" || muniVal === "Seleccione") {
        find("#municipio").addClass("is-invalid");
        errors.push("El municipio es obligatorio");
    }
    if (!find("#address").val() || find("#address").val().trim() === '') {
        find("#address").addClass("is-invalid");
        errors.push("La dirección es obligatoria");
    }

    // Validaciones específicas por tipo
    if (esextranjero) {
        if (!find("#pasaporte").val() || find("#pasaporte").val().trim() === '') {
            find("#pasaporte").addClass("is-invalid");
            errors.push("El pasaporte es obligatorio para extranjeros");
        }
    } else if (tpersona === "N") {
        if (!find("#firstname").val() || find("#firstname").val().trim() === '') {
            find("#firstname").addClass("is-invalid");
            errors.push("El primer nombre es obligatorio");
        }
        if (!find("#firstlastname").val() || find("#firstlastname").val().trim() === '') {
            find("#firstlastname").addClass("is-invalid");
            errors.push("El primer apellido es obligatorio");
        }
        if (!find("#nit").val() || find("#nit").val().trim() === '') {
            find("#nit").addClass("is-invalid");
            errors.push("El DUI/NIT es obligatorio");
        }
        if (escontribuyente) {
            if (!find("#ncr").val() || find("#ncr").val().trim() === '') {
                find("#ncr").addClass("is-invalid");
                errors.push("El NRC es obligatorio para contribuyentes");
            }
        var tipocon = find("#tipocontribuyente").val();
        if (!tipocon || tipocon === "0" || tipocon === "Seleccione") {
            find("#tipocontribuyente").addClass("is-invalid");
            errors.push("El tipo de contribuyente es obligatorio");
        }
        var acteco = find("#acteconomica").val();
        if (!acteco || acteco === "0" || acteco === "Seleccione") {
            find("#acteconomica").addClass("is-invalid");
            errors.push("La actividad económica es obligatoria");
        }
    }
} else if (tpersona === "J") {
        if (!find("#name_contribuyente").val() || find("#name_contribuyente").val().trim() === '') {
            find("#name_contribuyente").addClass("is-invalid");
            errors.push("El nombre contribuyente es obligatorio");
        }
        if (!find("#ncr").val() || find("#ncr").val().trim() === '') {
            find("#ncr").addClass("is-invalid");
            errors.push("El NRC es obligatorio para personas jurídicas");
        }
        var tipoconJ = find("#tipocontribuyente").val();
        if (!tipoconJ || tipoconJ === "0" || tipoconJ === "Seleccione") {
            find("#tipocontribuyente").addClass("is-invalid");
            errors.push("El tipo de contribuyente es obligatorio");
        }
        var actecoJ = find("#acteconomica").val();
        if (!actecoJ || actecoJ === "0" || actecoJ === "Seleccione") {
            find("#acteconomica").addClass("is-invalid");
            errors.push("La actividad económica es obligatoria");
        }
    }

    return errors;
}

// Función para verificar si el formulario está listo para enviar
function checkFormReadiness() {
    var tpersona = $("#tpersona").val();
    var esextranjero = $('#extranjero').is(':checked');
    var escontribuyente = $('#contribuyente').is(':checked');



    // Si hay campos marcados como inválidos por duplicados, no permitir
    if ($(".is-invalid").length > 0) {

        $("#btnsavenewclient").prop("disabled", true);
        return;
    }

    // Verificar campos básicos obligatorios
    var email = $("#email").val();
    var tel1 = $("#tel1").val();
    var tel2 = $("#tel2").val();
    var country = $("#country").val();
    var departament = $("#departament").val();
    var municipio = $("#municipio").val();
    var address = $("#address").val();

    // Validaciones básicas
    if (!tpersona || tpersona === "0") {
        $("#btnsavenewclient").prop("disabled", true);
        return;
    }

    if (!email || email.trim() === '') {
        $("#btnsavenewclient").prop("disabled", true);
        return;
    }

    // Al menos un teléfono
    if ((!tel1 || tel1.trim() === '') && (!tel2 || tel2.trim() === '')) {
        $("#btnsavenewclient").prop("disabled", true);
        return;
    }

    // Ubicación
    if (!country || country === "0" || !departament || departament === "0" ||
        !municipio || municipio === "0" || !address || address.trim() === '') {
        $("#btnsavenewclient").prop("disabled", true);
        return;
    }

    // Validaciones específicas por tipo
    var canSubmit = true;

    if (esextranjero) {
        var pasaporte = $("#pasaporte").val();
        if (!pasaporte || pasaporte.trim() === '') {
            canSubmit = false;
        } else if ($("#pasaporte").hasClass("is-invalid")) {
            canSubmit = false;
        }
    } else if (tpersona === "N") {
        var firstname = $("#firstname").val();
        var firstlastname = $("#firstlastname").val();
        var nit = $("#nit").val();

        if (!firstname || firstname.trim() === '' || !firstlastname || firstlastname.trim() === '' ||
            !nit || nit.trim() === '') {
            canSubmit = false;
        } else if ($("#nit").hasClass("is-invalid")) {
            canSubmit = false;
        }

        // Si es contribuyente, validar campos adicionales
        if (escontribuyente && canSubmit) {
            var ncr = $("#ncr").val();
            var tipocontribuyente = $("#tipocontribuyente").val();
            var acteconomica = $("#acteconomica").val();

            if (!ncr || ncr.trim() === '' || !tipocontribuyente || tipocontribuyente === "0" ||
                !acteconomica || acteconomica === "0") {
                canSubmit = false;
            } else if ($("#ncr").hasClass("is-invalid")) {
                canSubmit = false;
            }
        }

    } else if (tpersona === "J") {
        var name_contribuyente = $("#name_contribuyente").val();
        var ncr = $("#ncr").val();
        var tipocontribuyente = $("#tipocontribuyente").val();
        var acteconomica = $("#acteconomica").val();

        if (!name_contribuyente || name_contribuyente.trim() === '' || !ncr || ncr.trim() === '' ||
            !tipocontribuyente || tipocontribuyente === "0" || !acteconomica || acteconomica === "0") {
            canSubmit = false;
        } else if ($("#ncr").hasClass("is-invalid")) {
            canSubmit = false;
        }

        // Validar NIT si tiene valor
        var nit = $("#nit").val();
        if (nit && nit.trim() !== '' && $("#nit").hasClass("is-invalid")) {
            canSubmit = false;
        }
    }


    $("#btnsavenewclient").prop("disabled", !canSubmit);
}

// Función para enviar el formulario con validación (llamada desde el botón Guardar)
function submitClientForm() {
    var $form = $("#addNewClientForm");

    // Verificar campos obligatorios (scoped al formulario de nuevo cliente)
    var requiredFieldsErrors = validateRequiredFields($form);

    if (requiredFieldsErrors && requiredFieldsErrors.length > 0) {
        Swal.fire({
            title: "Campos obligatorios",
            html: requiredFieldsErrors.join("<br>"),
            icon: "warning",
            confirmButtonText: "Entendido"
        });
        return;
    }

    if ($form.find(".is-invalid").length > 0) {
        Swal.fire({
            title: "Error de validación",
            text: "Corrija los campos marcados en rojo (por ejemplo, NIT/NRC o pasaporte duplicado) antes de continuar.",
            icon: "error",
            confirmButtonText: "Entendido"
        });
        return;
    }

    // Disparar submit para que el handler lo envíe por AJAX
    $form.submit();
}

function getpaises(selected = "", type = "") {
    if (type == "edit") {
        $.ajax({
            url: "/getcountry",
            method: "GET",
            success: function (response) {
                $.each(response, function (index, value) {
                    if (selected != "" && value.id == selected) {
                        $("#countryedit").append(
                            '<option value="' +
                                value.id +
                                '" selected>' +
                                value.name.toUpperCase() +
                                "</option>"
                        );
                    } else {
                        $("#countryedit").append(
                            '<option value="' +
                                value.id +
                                '">' +
                                value.name.toUpperCase() +
                                "</option>"
                        );
                    }
                });
            },
        });
    } else {
        $.ajax({
            url: "/getcountry",
            method: "GET",
            success: function (response) {
                $.each(response, function (index, value) {
                    $("#country").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name.toUpperCase() +
                            "</option>"
                    );
                });
            },
        });
    }
}

(function () {
    // Flat Picker
    // --------------------------------------------------------------------
    const flatpickrDate = document.querySelector("#a");

    // Date
    if (flatpickrDate) {
        flatpickrDate.flatpickr({
            //monthSelectorType: 'static',
            dateFormat: "d-m-Y",
        });
    }
})();

function getdepartamentos(pais, type = "", selected, selectedact) {
    //Get countrys avaibles
    if (type == "edit") {
        $.ajax({
            url: "/getdepartment/" + btoa(pais),
            method: "GET",
            success: function (response) {
                $("#departamentedit").find("option:not(:first)").remove();
                $.each(response, function (index, value) {
                    if (selected != "" && value.id == selected) {
                        $("#departamentedit").append(
                            '<option value="' +
                                value.id +
                                '" selected>' +
                                value.name +
                                "</option>"
                        );
                    } else {
                        $("#departamentedit").append(
                            '<option value="' +
                                value.id +
                                '">' +
                                value.name +
                                "</option>"
                        );
                    }
                });
            },
        });

        //Get acteconomica
        // Limpiar el select de actividad económica antes de cargar (mantener solo la opción inicial)
        $("#acteconomicaedit").find("option:not(:first)").remove();
        $.ajax({
            url: "/geteconomicactivity/" + btoa(pais),
            method: "GET",
            success: function (response) {
                $.each(response, function (index, value) {
                    if (selectedact !== "" && value.id == selectedact) {
                        $("#acteconomicaedit").append(
                            '<option value="' +
                                value.id +
                                '" selected>' +
                                value.name +
                                "</option>"
                        );
                    } else {
                        $("#acteconomicaedit").append(
                            '<option value="' +
                                value.id +
                                '">' +
                                value.name +
                                "</option>"
                        );
                    }
                });
                // Establecer el valor seleccionado después de cargar todas las opciones
                if (selectedact !== "" && selectedact !== null && selectedact !== "0") {
                    $("#acteconomicaedit").val(selectedact).trigger('change');
                }
            },
        });
    } else {
        $.ajax({
            url: "/getdepartment/" + btoa(pais),
            method: "GET",
            success: function (response) {
                $("#departament").find("option:not(:first)").remove();
                $.each(response, function (index, value) {
                    $("#departament").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name +
                            "</option>"
                    );
                });
            },
        });
        //Get acteconomica
        $.ajax({
            url: "/geteconomicactivity/" + btoa(pais),
            method: "GET",
            success: function (response) {
                $.each(response, function (index, value) {
                    $("#acteconomica").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name +
                            "</option>"
                    );
                });
            },
        });
    }
}

function getmunicipio(dep, type = "", selected) {
    if (type == "edit") {
        //Get countrys avaibles
        $.ajax({
            url: "/getmunicipality/" + btoa(dep),
            method: "GET",
            success: function (response) {
                $("#municipioedit").find("option:not(:first)").remove();
                $.each(response, function (index, value) {
                    if (selected !== "" && value.id == selected) {
                        $("#municipioedit").append(
                            '<option value="' +
                                value.id +
                                '" selected>' +
                                value.name +
                                "</option>"
                        );
                    } else {
                        $("#municipioedit").append(
                            '<option value="' +
                                value.id +
                                '">' +
                                value.name +
                                "</option>"
                        );
                    }
                });
            },
        });
    } else {
        //Get countrys avaibles
        $.ajax({
            url: "/getmunicipality/" + btoa(dep),
            method: "GET",
            success: function (response) {
                $("#municipio").find("option:not(:first)").remove();
                $.each(response, function (index, value) {
                    $("#municipio").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name +
                            "</option>"
                    );
                });
            },
        });
    }
}

function typeperson(type) {
    $('#fields_with_option').css('display', '');
    if (type == "N") {
        $("#fields_natural").css("display", "");
        $("#fields_juridico").css("display", "none");
        $("#contribuyentelabel").css("display", "");
        $("#extranjerolabel").css("display", "");
        $("#siescontri").css("display", "none");
        $("#nacimientof").css("display", "");
        $("#dui_fields").css("display", "");
    } else {
        $("#contribuyentelabel").css("display", "none");
        $("#extranjerolabel").css("display", "none");
        $("#siescontri").css("display", "");
    }
    if (type == "J") {
        $("#fields_juridico").css("display", "");
        $("#fields_natural").css("display", "none");
        $("#nacimientof").css("display", "none");
        $("#dui_fields").css("display", "");
    }
}
function typepersonedit(type) {
    if (type == "N") {
        $("#contribuyentelabeledit").css("display", "");
        $("#extranjerolabeledit").css("display", "");
        $("#siescontriedit").css("display", "none");
        validarchecked();
        $("#nacimientof").css("display", "");
        $("#dui_fields").css("display", "");
        $("#DOB_field").css("display", "");
    } else {
        $("#contribuyentelabeledit").css("display", "none");
        $("#extranjerolabeledit").css("display", "none");
        $("#siescontriedit").css("display", "");
        $("#nacimientof").css("display", "none");
        $("#dui_fields").css("display", "");
        $("#DOB_field").css("display", "none");
    }
}

function escontri() {
    if ($("#contribuyente").is(":checked")) {
        $("#siescontri").css("display", "");
    } else {
        $("#siescontri").css("display", "none");
    }
}

function esextranjero() {
    if ($("#extranjero").is(":checked")) {
        $("#siextranjero").css("display", "");
        $("#siextranjeroduinit").css("display", "none");
    } else {
        $("#siextranjero").css("display", "none");
        $("#siextranjeroduinit").css("display", "");
    }
}

function escontriedit() {
    if ($("#contribuyenteedit").is(":checked")) {
        $("#siescontriedit").css("display", "");
        $("#contribuyenteeditvalor").val("1");
    } else {
        $("#siescontriedit").css("display", "none");
        $("#contribuyenteeditvalor").val("0");
    }
}

function validarchecked() {
    if ($("#contribuyenteedit").is(":checked")) {
        $("#contribuyenteeditvalor").val("1");
    } else {
        $("#contribuyenteeditvalor").val("0");
    }
}

/**
 * Actualizar campo oculto de agente de retención en edición
 */
function updateAgenteRetencionEdit() {
    if ($("#agente_retencionedit").is(":checked")) {
        $("#agente_retencionedit_hidden").val("1");
    } else {
        $("#agente_retencionedit_hidden").val("0");
    }
}

/**
 * Manejar el campo de extranjero en edición
 */
function esextranjeroedit() {
    if ($("#extranjeroedit").is(":checked")) {
        $("#siextranjeroedit").css("display", "");
        $("#siextranjeroduinitedit").css("display", "none");
    } else {
        $("#siextranjeroedit").css("display", "none");
        $("#siextranjeroduinitedit").css("display", "");
    }
}

function llamarselected(pais, departamento, municipio, acteconomica) {
    getpaises(pais, "edit");
    getdepartamentos(pais, "edit", departamento, acteconomica);
    getmunicipio(departamento, "edit", municipio);
}

function editClient(id) {
    //Get data edit companies
    //alert('entro');
    $.ajax({
        url: "/client/getClientid/" + btoa(id),
        method: "GET",
        success: function (response) {
            llamarselected(
                response[0]["country"],
                response[0]["departament"],
                response[0]["municipio"],
                response[0]["acteconomica"]
            );
            $.each(response[0], function (index, value) {
                if (index == "phone") {
                    $("#tel1edit").val(value);
                } else if (index == "phone_fijo") {
                    $("#tel2edit").val(value);
                }
                if (index == "phone_id") {
                    $("#phoneeditid").val(value);
                }
                if (index == "address_id") {
                    $("#addresseditid").val(value);
                }

                if (index == "contribuyente") {
                    if (value == "1") {
                        $("#contribuyenteedit").prop("checked", true);
                        $("#contribuyenteeditvalor").val("1");
                        $("#contribuyentelabeledit").css("display", "");
                        validarchecked();
                    } else if (value == "0") {
                        $("#contribuyenteedit").prop("checked", false);
                        $("#contribuyenteeditvalor").val("0");
                        $("#contribuyentelabeledit").css("display", "");
                    }
                    escontriedit();
                    if ($("#tpersonaedit").val() == "J") {
                        $("#contribuyentelabeledit").css("display", "none");
                        $("#siescontriedit").css("display", "");
                    }
                }

                // Cargar el valor de agente_retencion
                if (index == "agente_retencion") {
                    if (value == "1") {
                        $("#agente_retencionedit").prop("checked", true);
                        $("#agente_retencionedit_hidden").val("1");
                    } else {
                        $("#agente_retencionedit").prop("checked", false);
                        $("#agente_retencionedit_hidden").val("0");
                    }
                }

                // Cargar el valor de extranjero
                if (index == "extranjero") {
                    if (value == "1") {
                        $("#extranjeroedit").prop("checked", true);
                        $("#extranjerolabeledit").css("display", "");
                    } else {
                        $("#extranjeroedit").prop("checked", false);
                        $("#extranjerolabeledit").css("display", "");
                    }
                    esextranjeroedit();
                }

                if (index == "tpersona") {
                    var selectedN = "";
                    var selectedJ = "";
                    if (value == "J") {
                        selectedJ = "selected";
                        $("#fields_natural_edit").css("display", "none");
                        $("#fields_juridico_edit").css("display", "");
                        $("#dui_fields").css("display", "");
                        $("#DOB_field").css("display", "none");
                    } else if (value == "N") {
                        selectedN = "selected";
                        $("#contribuyentelabeledit").css("display", "");
                        $("#fields_natural_edit").css("display", "");
                        $("#fields_juridico_edit").css("display", "none");
                        $("#dui_fields").css("display", "");
                        $("#DOB_field").css("display", "");
                    }
                    $("#tpersonaedit").empty();
                    $("#tpersonaedit").append(
                        '<option value="N" ' + selectedN + ">NATURAL</option>"
                    );
                    $("#tpersonaedit").append(
                        '<option value="J" ' + selectedJ + ">JURIDICO</option>"
                    );
                }

                if (index == "tipoContribuyente") {
                    $(
                        "#tipocontribuyenteedit option[value='" + value + "']"
                    ).attr("selected", true);
                }
                // Excluir acteconomica del loop general porque se establece después de cargar las opciones
                if (index !== "acteconomica") {
                    $("#" + index + "edit").val(value);
                }
            });
            const bsOffcanvas = new bootstrap.Offcanvas(
                "#offcanvasUpdateClient"
            ).show();
        },
    });
}

function deleteClient(id) {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-danger",
        },
        buttonsStyling: false,
    });

    swalWithBootstrapButtons
        .fire({
            title: "¿Eliminar?",
            text: "Esta accion no tiene retorno",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Si, Eliminarlo!",
            cancelButtonText: "No, Cancelar!",
            reverseButtons: true,
        })
        .then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/client/destroy/" + btoa(id),
                    method: "GET",
                    success: function (response) {
                        if (response.res == 1) {
                            Swal.fire({
                                title: "Eliminado",
                                text: response.message || "Cliente eliminado exitosamente",
                                icon: "success",
                                confirmButtonText: "Ok",
                            }).then((result) => {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                        } else if (response.res == 0) {
                            swalWithBootstrapButtons.fire(
                                "No se puede eliminar",
                                response.message || "Algo sucedió y no pudo eliminar el cliente, favor comunicarse con el administrador.",
                                "error"
                            );
                        }
                    },
                    error: function (xhr) {
                        var errorMessage = "Algo sucedió y no pudo eliminar el cliente, favor comunicarse con el administrador.";

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.res === 0 && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        swalWithBootstrapButtons.fire(
                            "No se puede eliminar",
                            errorMessage,
                            "error"
                        );
                    }
                });
            } else if (
                /* Read more about handling dismissals below */
                result.dismiss === Swal.DismissReason.cancel
            ) {
                swalWithBootstrapButtons.fire(
                    "Cancelado",
                    "No hemos hecho ninguna accion :)",
                    "error"
                );
            }
        });
}

function nitDuiMask(inputField) {
    var separator = "-";
    var nitPattern;
    // Contar solo los dígitos (sin guiones ni otros caracteres)
    var digitsOnly = inputField.value.replace(/[^0-9]/g, '');
    var digitCount = digitsOnly.length;

    // DUI tiene 9 dígitos (8 + 1 dígito verificador)
    // NIT tiene 14 dígitos (4-6-3-1)
    if (digitCount <= 9) {
        nitPattern = new Array(8, 1); // Formato DUI: xxxxxxxx-x
    } else {
        nitPattern = new Array(4, 6, 3, 1); // Formato NIT: xxxx-xxxxxx-xxx-x
    }
    mask(inputField, separator, nitPattern, true);
}

function pasaporteMask(inputField) {
    var separator = "-";
    var pasaportePattern;
    var cleanValue = inputField.value.replace(/-/g, "").toUpperCase(); // Eliminar guiones y convertir a mayúsculas

    // Solo permitir letras y números
    cleanValue = cleanValue.replace(/[^A-Z0-9]/g, "");
    inputField.value = cleanValue; // Actualiza el campo sin caracteres no permitidos

    if (/^[0-9]{9}$/.test(cleanValue)) {
        pasaportePattern = [9]; // Solo números (EE.UU., México, Brasil)
    } else if (/^[A-Z][0-9]{7,9}$/.test(cleanValue)) {
        pasaportePattern = [1, 7]; // Letra + 7-9 números (Reino Unido, Alemania)
    } else if (/^[A-Z]{2}[0-9]{7,8}$/.test(cleanValue)) {
        pasaportePattern = [2, 8]; // Dos letras + 7-8 números (España, Argentina, Italia)
    } else if (/^[0-9]{4}[0-9]{6}[0-9]{3}[A-Z]$/.test(cleanValue)) {
        pasaportePattern = [4, 6, 3, 1]; // Formato XXXX-XXXXXX-XXX-X (Centroamérica)
    } else if (/^[A-Z]{2}[0-9]{8}[A-Z]{2}$/.test(cleanValue)) {
        pasaportePattern = [2, 8, 2]; // Dos letras al inicio y fin (Algunos países europeos y asiáticos)
    } else {
        pasaportePattern = [cleanValue.length]; // Si no coincide, deja el formato sin guiones
    }

    mask(inputField, separator, pasaportePattern, true);
}


function NRCMask(inputField) {
    var separator = "-";
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
        val2 = "";
        for (r = 0; r < val.length; r++) {
            val2 += val[r];
        }
        if (nums) {
            for (z = 0; z < val2.length; z++) {
                if (isNaN(val2.charAt(z))) {
                    letra = new RegExp(val2.charAt(z), "g");
                    val2 = val2.replace(letra, "");
                }
            }
        }
        val = "";
        val3 = new Array();
        for (s = 0; s < pattern.length; s++) {
            val3[s] = val2.substring(0, pattern[s]);
            val2 = val2.substr(pattern[s]);
        }
        for (q = 0; q < val3.length; q++) {
            if (q == 0) {
                val = val3[q];
            } else {
                if (val3[q] != "") {
                    val += separator + val3[q];
                }
            }
        }
        inputField.value = val;
        inputField.valant = val;
    }
}
