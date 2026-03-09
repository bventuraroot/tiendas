<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        $rules = [
            'code' => 'required|string|max:255|min:1|unique:products,code',
            'name' => 'required|string|max:255|min:1',
            'description' => 'required|string|min:1',
            'marca' => 'nullable|exists:marcas,id',
            'provider' => 'nullable|exists:providers,id',
            'pharmaceutical_laboratory' => 'nullable|exists:pharmaceutical_laboratories,id',
            'category' => 'nullable|string|max:255',
            'presentation_type' => 'required|string|max:50',
            'specialty' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'formula' => 'nullable|string',
            'unit_measure' => 'nullable|string|max:50',
            'sale_form' => 'nullable|string|max:50',
            'product_type' => 'nullable|string|max:50',
            'cfiscal' => 'required|in:gravado,exento',
            'type' => 'required|in:directo,tercero',
            'price' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'image' => [
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
            'code.required' => 'El código del producto es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede tener más de 255 caracteres.',
            'code.min' => 'El código debe tener al menos 1 carácter.',
            'code.unique' => 'El código del producto ya existe. Por favor, ingrese un código diferente.',

            'name.required' => 'El nombre del producto es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.min' => 'El nombre debe tener al menos 1 carácter.',

            'description.required' => 'La descripción del producto es obligatoria.',
            'description.string' => 'La descripción debe ser texto.',
            'description.min' => 'La descripción debe tener al menos 1 carácter.',

            'marca.exists' => 'La marca seleccionada no existe.',
            'provider.exists' => 'El proveedor seleccionado no existe.',

            'category.string' => 'La categoría debe ser texto.',
            'category.max' => 'La categoría no puede tener más de 255 caracteres.',

            'presentation_type.required' => 'La presentación del producto es obligatoria.',

            'cfiscal.required' => 'La clasificación fiscal es obligatoria.',
            'cfiscal.in' => 'La clasificación fiscal debe ser "gravado" o "exento".',

            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo debe ser "directo" o "tercero".',

            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
            'price.regex' => 'El precio debe tener un formato válido (ej: 10.50).',

            'image.file' => 'El archivo debe ser válido.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, webp o gif.',
            'image.max' => 'La imagen no puede ser mayor a 5MB.',
            'image.dimensions' => 'La imagen debe tener dimensiones entre 100x100 y 5000x5000 píxeles.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'marca' => 'marca',
            'provider' => 'proveedor',
            'category' => 'categoría',
            'presentation_type' => 'presentación',
            'cfiscal' => 'clasificación fiscal',
            'type' => 'tipo',
            'price' => 'precio',
            'image' => 'imagen',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $data = [
            'code' => trim($this->code ?? ''),
            'name' => trim($this->name ?? ''),
            'description' => trim($this->description ?? ''),
            'category' => $this->category ? trim($this->category) : null,
        ];

        // Marca y proveedor: convertir "" o "0" a null para que nullable funcione correctamente
        $data['marca'] = $this->filled('marca') && $this->marca !== '' && $this->marca !== '0' ? $this->marca : null;
        $data['provider'] = $this->filled('provider') && $this->provider !== '' && $this->provider !== '0' ? $this->provider : null;

        $this->merge($data);
    }
}
