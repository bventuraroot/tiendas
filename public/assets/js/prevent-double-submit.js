/**
 * Helper para prevenir doble envío de formularios
 * 
 * Este script proporciona funciones para prevenir que los usuarios
 * envíen formularios múltiples veces haciendo clic repetidamente en botones.
 * 
 * Uso:
 * 1. Agregar data-prevent-double-submit="true" al formulario
 * 2. O llamar preventDoubleSubmit() manualmente en el evento submit
 */

(function() {
    'use strict';

    /**
     * Genera un token único de idempotencia
     */
    function generateIdempotencyKey() {
        return 'idempotency_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Deshabilita el botón de envío y muestra estado de carga
     */
    function disableSubmitButton(form) {
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            // Guardar el texto original si es un botón
            if (submitBtn.tagName === 'BUTTON') {
                submitBtn.dataset.originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Procesando...';
            }
            submitBtn.disabled = true;
            submitBtn.classList.add('disabled');
        }
    }

    /**
     * Habilita el botón de envío y restaura el texto original
     */
    function enableSubmitButton(form) {
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('disabled');
            if (submitBtn.tagName === 'BUTTON' && submitBtn.dataset.originalText) {
                submitBtn.innerHTML = submitBtn.dataset.originalText;
            }
        }
    }

    /**
     * Prevenir doble envío en un formulario específico
     */
    function preventDoubleSubmit(form) {
        let isSubmitting = false;

        form.addEventListener('submit', function(e) {
            // Si ya se está enviando, prevenir el envío
            if (isSubmitting) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }

            // Marcar como enviando
            isSubmitting = true;

            // Deshabilitar botón
            disableSubmitButton(form);

            // Agregar token de idempotencia si no existe
            let idempotencyInput = form.querySelector('input[name="_idempotency_key"]');
            if (!idempotencyInput) {
                idempotencyInput = document.createElement('input');
                idempotencyInput.type = 'hidden';
                idempotencyInput.name = '_idempotency_key';
                form.appendChild(idempotencyInput);
            }
            idempotencyInput.value = generateIdempotencyKey();

            // Si es un envío AJAX, manejar la respuesta
            if (form.dataset.ajaxSubmit === 'true' || form.classList.contains('ajax-form')) {
                // El manejo AJAX se hace en el código que llama a $.ajax
                // Aquí solo prevenimos el submit normal
                e.preventDefault();
                
                // Si hay un error, re-habilitar el botón después de un tiempo
                setTimeout(function() {
                    if (isSubmitting) {
                        isSubmitting = false;
                        enableSubmitButton(form);
                    }
                }, 5000); // 5 segundos timeout
            }
        });

        // Re-habilitar si hay errores de validación del lado del cliente
        form.addEventListener('invalid', function(e) {
            isSubmitting = false;
            enableSubmitButton(form);
        }, true);
    }

    /**
     * Prevenir doble clic en botones AJAX
     */
    function preventDoubleClick(button, ajaxFunction) {
        let isProcessing = false;

        button.addEventListener('click', function(e) {
            if (isProcessing) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }

            isProcessing = true;
            const originalText = button.innerHTML;
            button.disabled = true;
            button.classList.add('disabled');
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Procesando...';

            // Ejecutar la función AJAX
            if (typeof ajaxFunction === 'function') {
                const result = ajaxFunction();
                
                // Si retorna una promesa, manejar el resultado
                if (result && typeof result.then === 'function') {
                    result
                        .then(function() {
                            isProcessing = false;
                            button.disabled = false;
                            button.classList.remove('disabled');
                            button.innerHTML = originalText;
                        })
                        .catch(function() {
                            isProcessing = false;
                            button.disabled = false;
                            button.classList.remove('disabled');
                            button.innerHTML = originalText;
                        });
                } else {
                    // Si no es promesa, re-habilitar después de un tiempo
                    setTimeout(function() {
                        isProcessing = false;
                        button.disabled = false;
                        button.classList.remove('disabled');
                        button.innerHTML = originalText;
                    }, 3000);
                }
            }
        });
    }

    /**
     * Helper para formularios AJAX con jQuery
     */
    function setupAjaxFormPrevention(formSelector, ajaxOptions) {
        const form = typeof formSelector === 'string' 
            ? document.querySelector(formSelector) 
            : formSelector;

        if (!form) return;

        let isSubmitting = false;

        // Prevenir submit normal
        $(form).on('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            isSubmitting = true;
            disableSubmitButton(form);

            // Agregar token de idempotencia
            const idempotencyKey = generateIdempotencyKey();
            
            // Agregar a headers si es AJAX
            if (ajaxOptions && ajaxOptions.headers) {
                ajaxOptions.headers['X-Idempotency-Key'] = idempotencyKey;
            } else if (ajaxOptions) {
                ajaxOptions.headers = {
                    'X-Idempotency-Key': idempotencyKey
                };
            }

            // Agregar a datos del formulario
            if (ajaxOptions && ajaxOptions.data) {
                if (ajaxOptions.data instanceof FormData) {
                    ajaxOptions.data.append('_idempotency_key', idempotencyKey);
                } else if (typeof ajaxOptions.data === 'object') {
                    ajaxOptions.data._idempotency_key = idempotencyKey;
                }
            }

            // Ejecutar AJAX con callbacks para re-habilitar
            const originalSuccess = ajaxOptions.success;
            const originalError = ajaxOptions.error;
            const originalComplete = ajaxOptions.complete;

            ajaxOptions.success = function(response) {
                isSubmitting = false;
                enableSubmitButton(form);
                if (originalSuccess) originalSuccess(response);
            };

            ajaxOptions.error = function(xhr, status, error) {
                isSubmitting = false;
                enableSubmitButton(form);
                if (originalError) originalError(xhr, status, error);
            };

            ajaxOptions.complete = function(xhr, status) {
                // Asegurar que se re-habilite incluso si hay errores
                setTimeout(function() {
                    isSubmitting = false;
                    enableSubmitButton(form);
                }, 1000);
                if (originalComplete) originalComplete(xhr, status);
            };

            $.ajax(ajaxOptions);

            return false; // Prevenir submit normal
        });
    }

    // Auto-inicializar formularios con el atributo data-prevent-double-submit
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form[data-prevent-double-submit="true"]');
        forms.forEach(function(form) {
            preventDoubleSubmit(form);
        });

        // Auto-inicializar botones con data-prevent-double-click
        const buttons = document.querySelectorAll('button[data-prevent-double-click="true"], a[data-prevent-double-click="true"]');
        buttons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                if (this.disabled || this.classList.contains('disabled')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            });
        });
    });

    // Exportar funciones globalmente
    window.preventDoubleSubmit = preventDoubleSubmit;
    window.preventDoubleClick = preventDoubleClick;
    window.setupAjaxFormPrevention = setupAjaxFormPrevention;
    window.generateIdempotencyKey = generateIdempotencyKey;

})();
