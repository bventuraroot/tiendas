/**
 * Validaciones para formularios de productos
 */

class ProductValidator {
    constructor() {
        this.initializeValidations();
        this.setupCodeValidation();
    }

    initializeValidations() {
        // Validación del formulario de crear producto
        this.setupFormValidation('#addproductForm');

        // Validación del formulario de editar producto
        this.setupFormValidation('#editproductForm');

        // Limpiar errores cuando el usuario empiece a escribir
        this.setupInputListeners();

        // Inicializar estado de botones
        this.initializeButtonStates();
    }

    initializeButtonStates() {
        const forms = ['#addproductForm', '#editproductForm'];

        forms.forEach(formSelector => {
            const form = document.querySelector(formSelector);
            if (form) {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('disabled');
                }
            }
        });
    }

    setupCodeValidation() {
        // Validación en tiempo real del código para crear producto
        const codeInput = document.getElementById('code');
        if (codeInput) {
            this.setupCodeInputValidation(codeInput);
        }

        // En edición validar también (habilitado)
        const codeEditInput = document.getElementById('codeedit');
        if (codeEditInput) {
            this.setupCodeInputValidation(codeEditInput, true);
        }

        // Configurar validación en tiempo real para todos los campos requeridos
        this.setupRealTimeValidation();
    }

    setupRealTimeValidation() {
        // Obtener todos los formularios
        const forms = ['#addproductForm', '#editproductForm'];

        forms.forEach(formSelector => {
            const form = document.querySelector(formSelector);
            if (!form) return;

                    // Habilitar botón inicialmente
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('disabled');
        }

            // Agregar listeners para todos los campos requeridos
            const requiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');
            requiredFields.forEach(field => {
                field.addEventListener('input', () => this.checkFormValidation(form));
                field.addEventListener('change', () => this.checkFormValidation(form));
                field.addEventListener('blur', () => this.checkFormValidation(form));
            });

            // Para campos select específicos
            const selectFields = form.querySelectorAll('select[name*="cfiscal"], select[name*="type"]');
            selectFields.forEach(field => {
                field.addEventListener('change', () => this.checkFormValidation(form));
            });

            // Para campos de unidades de medida
            const unitFields = form.querySelectorAll('select[name*="sale_type"], input[name*="weight_per_unit"], input[name*="volume_per_unit"], input[name*="price"]');
            unitFields.forEach(field => {
                field.addEventListener('input', () => this.checkFormValidation(form));
                field.addEventListener('change', () => this.checkFormValidation(form));
            });
        });
    }

    checkFormValidation(form) {
        // Mantener el botón siempre habilitado
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('disabled');
        }
    }

    setupCodeInputValidation(input, isEdit = false) {
        // Validar en onchange
        input.addEventListener('change', (e) => {
            const code = e.target.value.trim();

            // Limpiar validación anterior
            this.clearCodeValidation(input);

            if (code.length === 0) {
                this.markCodeAsInvalid(input, 'El código del producto es obligatorio');
                this.disableSubmitButton(input);
                this.checkFormValidation(input.closest('form'));
                return;
            }

            // Validar inmediatamente en onchange
            this.checkCodeExists(code, input, isEdit);
        });

        // También validar cuando el campo pierda el foco
        input.addEventListener('blur', () => {
            const code = input.value.trim();
            if (code.length > 0) {
                this.checkCodeExists(code, input, isEdit);
            }
        });
    }

    async checkCodeExists(code, input, isEdit = false) {
        try {
            const data = {
                code: code,
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };

            // Si es edición, incluir el ID del producto para excluirlo
            if (isEdit) {
                const productId = document.getElementById('idedit')?.value;
                if (productId) {
                    data.exclude_id = productId;
                }
            }

            const response = await fetch('/product/check-code-exists', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.exists) {
                this.markCodeAsInvalid(input, 'El código del producto ya existe. Por favor, ingrese un código diferente.');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Código Duplicado', text: 'El código del producto ya existe en el sistema. Por favor, ingrese un código diferente.', confirmButtonText: 'Entendido', confirmButtonColor: '#ffc107' });
                }
            } else {
                if (typeof Swal !== 'undefined' && Swal.isVisible && Swal.isVisible()) { Swal.close(); }
                this.markCodeAsValid(input, 'Código disponible');
            }

        } catch (error) {
            this.markCodeAsInvalid(input, 'Error al verificar el código. Intente nuevamente.');
        }
    }

    markCodeAsInvalid(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        // Ocultar cualquier mensaje de éxito previo
        const prevSuccessDivs = input.parentNode.querySelectorAll('.valid-feedback, .valid-feedback.d-block');
        prevSuccessDivs.forEach(div => {
            div.style.display = 'none';
        });

        // Mostrar mensaje de error
        let errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback d-block';
            input.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';

        // Mantener botón habilitado
        this.enableSubmitButton(input);
    }

    markCodeAsValid(input, message) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');

        // Ocultar cualquier mensaje de error previo (incluyendo variaciones)
        const prevErrorDivs = input.parentNode.querySelectorAll('.invalid-feedback, .invalid-feedback.d-block');
        prevErrorDivs.forEach(div => {
            div.style.display = 'none';
        });

        // Mostrar mensaje de éxito
        let successDiv = input.parentNode.querySelector('.valid-feedback');
        if (!successDiv) {
            successDiv = document.createElement('div');
            successDiv.className = 'valid-feedback d-block';
            input.parentNode.appendChild(successDiv);
        }
        successDiv.textContent = message;
        successDiv.style.display = 'block';

        // Ocultar mensaje de error si existe
        const errorDiv = input.parentNode.querySelector('.invalid-feedback.d-block');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }

        // Verificar si se puede habilitar el botón
        const form = input.closest('form');
        if (form && this.checkAllFormValidations(form)) {
            this.enableSubmitButton(input);
        }
    }

        clearCodeValidation(input) {
        input.classList.remove('is-invalid', 'is-valid');

        // Ocultar mensajes de error y éxito
        const errorDiv = input.parentNode.querySelector('.invalid-feedback.d-block');
        const successDiv = input.parentNode.querySelector('.valid-feedback.d-block');

        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
        if (successDiv) {
            successDiv.style.display = 'none';
        }
    }

    disableSubmitButton(input) {
        const form = input.closest('form');
        if (form) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('disabled');
                submitButton.title = 'Haga clic para guardar el producto';
            }
        }
    }

    enableSubmitButton(input) {
        const form = input.closest('form');
        if (form) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('disabled');
                submitButton.title = 'Haga clic para guardar el producto';
            }
        }
    }

    checkAllFormValidations(form) {
        // En edición, el código no se valida como requisito
        const codeField = form.id === 'editproductForm' ? null : form.querySelector('[name*="code"]');
        const nameField = form.querySelector('[name*="name"]');
        const descriptionField = form.querySelector('[name*="description"]');
        const cfiscalField = form.querySelector('[name*="cfiscal"]');
        const typeField = form.querySelector('[name*="type"]');

        // Verificar que todos los campos requeridos estén completos y válidos
        const isCodeValid = codeField ? (codeField.value.trim().length > 0 && codeField.classList.contains('is-valid')) : true;
        const isNameValid = nameField && nameField.value.trim().length > 0;
        const isDescriptionValid = descriptionField && descriptionField.value.trim().length > 0;
        const isCfiscalValid = cfiscalField && cfiscalField.value && cfiscalField.value !== 'Seleccione' && cfiscalField.value !== '';
        const isTypeValid = typeField && typeField.value && typeField.value !== 'Seleccione' && typeField.value !== '';

        // Para el formulario de crear, también validar precio
        let isPriceValid = true;
        if (form.id === 'addproductForm') {
            const priceField = form.querySelector('[name="price"]');
            isPriceValid = priceField && priceField.value.trim().length > 0 && !isNaN(parseFloat(priceField.value)) && parseFloat(priceField.value) >= 0;
        }

        // Validar campos de unidades de medida
        let isUnitsValid = true;
        if (typeof window.validateProductForm === 'function' && form.id === 'addproductForm') {
            isUnitsValid = window.validateProductForm();
        } else if (typeof window.validateProductEditForm === 'function' && form.id === 'editproductForm') {
            isUnitsValid = window.validateProductEditForm();
        }

        return isCodeValid && isNameValid && isDescriptionValid && isCfiscalValid && isTypeValid && isPriceValid && isUnitsValid;
    }

    setupFormValidation(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });
    }

    validateForm(form) {
        let isValid = true;
        const errorMessages = [];
        const formId = form.id;

        // Validar código solo en creación
        const codeField = form.id === 'addproductForm' ? form.querySelector('[name*="code"]') : null;
        if (codeField) {
            // Validar que no esté vacío
            if (!this.validateRequiredField(codeField, 'El código del producto es obligatorio')) {
                isValid = false;
                errorMessages.push('El código del producto es obligatorio');
            } else {
                // Validar que no tenga errores de validación (código duplicado)
                if (codeField.classList.contains('is-invalid')) {
                    isValid = false;
                    const errorDiv = codeField.parentNode.querySelector('.invalid-feedback.d-block');
                    if (errorDiv) {
                        errorMessages.push(errorDiv.textContent);
                    } else {
                        errorMessages.push('El código del producto ya existe');
                    }
                }
            }
        }

        // Validar nombre
        const nameField = form.querySelector('[name*="name"]');
        if (nameField && !this.validateRequiredField(nameField, 'El nombre del producto es obligatorio')) {
            isValid = false;
            errorMessages.push('El nombre del producto es obligatorio');
        }

        // Validar descripción
        const descriptionField = form.querySelector('[name*="description"]');
        if (descriptionField && !this.validateRequiredField(descriptionField, 'La descripción del producto es obligatoria')) {
            isValid = false;
            errorMessages.push('La descripción del producto es obligatoria');
        }

        // Validar clasificación fiscal
        const cfiscalField = form.querySelector('[name*="cfiscal"]');
        if (cfiscalField && !this.validateSelectField(cfiscalField, 'Debe seleccionar una clasificación fiscal')) {
            isValid = false;
            errorMessages.push('Debe seleccionar una clasificación fiscal');
        }

        // Validar tipo
        const typeField = form.querySelector('[name*="type"]');
        if (typeField && !this.validateSelectField(typeField, 'Debe seleccionar un tipo')) {
            isValid = false;
            errorMessages.push('Debe seleccionar un tipo');
        }

        // Validar presentación
        const presentationField = form.querySelector('[name="presentation_type"], [name="presentation_typeedit"]');
        if (presentationField && !this.validateSelectField(presentationField, 'Debe seleccionar una presentación')) {
            isValid = false;
            errorMessages.push('Debe seleccionar una presentación');
        }

        // Validar precio (solo en formulario de crear)
        if (formId === 'addproductForm') {
            const priceField = form.querySelector('[name="price"]');
            if (priceField && !this.validatePriceField(priceField)) {
                isValid = false;
                errorMessages.push('El precio debe ser un número válido mayor o igual a 0');
            }
        }

        if (!isValid) {
            this.showValidationErrors(errorMessages);
        }

        return isValid;
    }

    validateRequiredField(field, errorMessage) {
        const value = field.value.trim();
        if (!value) {
            this.markFieldAsInvalid(field, errorMessage);
            return false;
        }
        this.markFieldAsValid(field);
        return true;
    }

    validateSelectField(field, errorMessage) {
        const value = field.value;
        if (!value || value === 'Seleccione') {
            this.markFieldAsInvalid(field, errorMessage);
            return false;
        }
        this.markFieldAsValid(field);
        return true;
    }

    validatePriceField(field) {
        const value = field.value.trim();
        if (!value) {
            this.markFieldAsInvalid(field, 'El precio es obligatorio');
            return false;
        }

        const price = parseFloat(value);
        if (isNaN(price) || price < 0) {
            this.markFieldAsInvalid(field, 'El precio debe ser un número válido mayor o igual a 0');
            return false;
        }

        this.markFieldAsValid(field);
        return true;
    }

    markFieldAsInvalid(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');

        // Mostrar mensaje de error
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback d-block';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    markFieldAsValid(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');

        // Ocultar mensaje de error
        const errorDiv = field.parentNode.querySelector('.invalid-feedback.d-block');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    setupInputListeners() {
        // Limpiar clases de error cuando el usuario empiece a escribir
        document.addEventListener('input', (e) => {
            if (e.target.matches('input, textarea, select')) {
                e.target.classList.remove('is-invalid');
                e.target.classList.remove('is-valid');
            }
        });

        // Limpiar clases de error cuando el usuario cambie un select
        document.addEventListener('change', (e) => {
            if (e.target.matches('select')) {
                e.target.classList.remove('is-invalid');
                e.target.classList.remove('is-valid');
            }
        });
    }

    showValidationErrors(messages) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: messages.join('<br>'),
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#dc3545'
            });
        } else {
            // Fallback si SweetAlert no está disponible
            alert('Error de validación:\n' + messages.join('\n'));
        }
    }

    // Método para validar campos individuales en tiempo real
    validateField(field) {
        const fieldName = field.name;

        if (fieldName.includes('code') || fieldName.includes('name')) {
            return this.validateRequiredField(field, 'Este campo es obligatorio');
        }

        if (fieldName.includes('description')) {
            return this.validateRequiredField(field, 'La descripción es obligatoria');
        }

        if (fieldName.includes('cfiscal')) {
            return this.validateSelectField(field, 'Debe seleccionar una clasificación fiscal');
        }

        if (fieldName.includes('type')) {
            return this.validateSelectField(field, 'Debe seleccionar un tipo');
        }

        if (fieldName === 'price') {
            return this.validatePriceField(field);
        }

        return true;
    }
}

// Inicializar validaciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    new ProductValidator();
});

// Exportar para uso global si es necesario
window.ProductValidator = ProductValidator;
