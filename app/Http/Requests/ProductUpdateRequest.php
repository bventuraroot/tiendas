<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->input('idedit');

        $rules = [
            'idedit' => 'required|exists:products,id',
            // En edición el código NO es obligatorio; si viene, debe ser válido y único
            'codeedit' => 'nullable|string|max:255|min:1|unique:products,code,' . $productId,
            'nameedit' => 'required|string|max:255|min:1',
            'descriptionedit' => 'required|string|min:1',
            'marcaredit' => 'nullable|exists:marcas,id',
            'provideredit' => 'nullable|exists:providers,id',
            'pharmaceutical_laboratoryedit' => 'nullable|exists:pharmaceutical_laboratories,id',
            'categoryedit' => 'nullable|string|max:255',
            'presentation_typeedit' => 'required|string|max:50',
            'specialtyedit' => 'nullable|string|max:255',
            'registration_numberedit' => 'nullable|string|max:100',
            'formulaedit' => 'nullable|string',
            'unit_measureedit' => 'nullable|string|max:50',
            'sale_formedit' => 'nullable|string|max:50',
            'product_typeedit' => 'nullable|string|max:50',
            'cfiscaledit' => 'required|in:gravado,exento',
            'typeedit' => 'required|in:directo,tercero',
            'imageedit' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp,gif',
                'max:5120', // 5MB
                'dimensions:min_width=100,min_height=100,max_width=5000,max_height=5000'
            ]
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'idedit.required' => 'El ID del producto es obligatorio.',
            'idedit.exists' => 'El producto no existe.',

            'codeedit.required' => 'El código del producto es obligatorio.',
            'codeedit.string' => 'El código debe ser texto.',
            'codeedit.max' => 'El código no puede tener más de 255 caracteres.',
            'codeedit.min' => 'El código debe tener al menos 1 carácter.',
            'codeedit.unique' => 'El código del producto ya existe. Por favor, ingrese un código diferente.',

            'nameedit.required' => 'El nombre del producto es obligatorio.',
            'nameedit.string' => 'El nombre debe ser texto.',
            'nameedit.max' => 'El nombre no puede tener más de 255 caracteres.',
            'nameedit.min' => 'El nombre debe tener al menos 1 carácter.',

            'descriptionedit.required' => 'La descripción del producto es obligatoria.',
            'descriptionedit.string' => 'La descripción debe ser texto.',
            'descriptionedit.min' => 'La descripción debe tener al menos 1 carácter.',

            'marcaredit.exists' => 'La marca seleccionada no existe.',
            'provideredit.exists' => 'El proveedor seleccionado no existe.',

            'categoryedit.string' => 'La categoría debe ser texto.',
            'categoryedit.max' => 'La categoría no puede tener más de 255 caracteres.',

            'presentation_typeedit.required' => 'La presentación del producto es obligatoria.',

            'cfiscaledit.required' => 'La clasificación fiscal es obligatoria.',
            'cfiscaledit.in' => 'La clasificación fiscal debe ser "gravado" o "exento".',

            'typeedit.required' => 'El tipo es obligatorio.',
            'typeedit.in' => 'El tipo debe ser "directo" o "tercero".',

            'imageedit.file' => 'El archivo debe ser válido.',
            'imageedit.image' => 'El archivo debe ser una imagen.',
            'imageedit.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, webp o gif.',
            'imageedit.max' => 'La imagen no puede ser mayor a 5MB.',
            'imageedit.dimensions' => 'La imagen debe tener dimensiones entre 100x100 y 5000x5000 píxeles.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'idedit' => 'ID del producto',
            'codeedit' => 'código',
            'nameedit' => 'nombre',
            'descriptionedit' => 'descripción',
            'marcaredit' => 'marca',
            'provideredit' => 'proveedor',
            'categoryedit' => 'categoría',
            'presentation_typeedit' => 'presentación',
            'cfiscaledit' => 'clasificación fiscal',
            'typeedit' => 'tipo',
            'imageedit' => 'imagen',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $data = [
            'codeedit' => trim($this->codeedit ?? ''),
            'nameedit' => trim($this->nameedit ?? ''),
            'descriptionedit' => trim($this->descriptionedit ?? ''),
            'categoryedit' => $this->categoryedit ? trim($this->categoryedit) : null,
        ];

        // Marca y proveedor: convertir "" o "0" a null
        $data['marcaredit'] = $this->filled('marcaredit') && $this->marcaredit !== '' && $this->marcaredit !== '0' ? $this->marcaredit : null;
        $data['provideredit'] = $this->filled('provideredit') && $this->provideredit !== '' && $this->provideredit !== '0' ? $this->provideredit : null;

        $this->merge($data);
    }
}
