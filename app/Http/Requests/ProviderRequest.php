<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderRequest extends FormRequest
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
        $providerId = $this->route('provider') ? $this->route('provider')->id : null;

        $rules = [
            'razonsocial' => 'required|string|max:255',
            'ncr' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('providers')->ignore($providerId)->where(function ($query) {
                    return $query->whereNotNull('ncr');
                })
            ],
            'nit' => [
                'nullable',
                'string',
                'max:25',
                Rule::unique('providers')->ignore($providerId)->where(function ($query) {
                    return $query->whereNotNull('nit');
                })
            ],
            'email' => 'nullable|email|max:255',
            'company' => 'nullable|exists:companies,id',
            'country' => 'required|exists:countries,id',
            'departament' => 'required|exists:departments,id',
            'municipio' => 'required|exists:municipalities,id',
            'address' => 'required|string|max:255',
            'tel1' => 'nullable|string|max:20',
            'tel2' => 'nullable|string|max:20',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'razonsocial.required' => 'La razón social es obligatoria.',
            'razonsocial.max' => 'La razón social no puede tener más de 255 caracteres.',
            'ncr.unique' => 'Ya existe un proveedor con este NCR.',
            'ncr.max' => 'El NCR no puede tener más de 15 caracteres.',
            'nit.unique' => 'Ya existe un proveedor con este NIT.',
            'nit.max' => 'El NIT no puede tener más de 25 caracteres.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
            'company.exists' => 'La empresa seleccionada no existe.',
            'country.required' => 'El país es obligatorio.',
            'country.exists' => 'El país seleccionado no existe.',
            'departament.required' => 'El departamento es obligatorio.',
            'departament.exists' => 'El departamento seleccionado no existe.',
            'municipio.required' => 'El municipio es obligatorio.',
            'municipio.exists' => 'El municipio seleccionado no existe.',
            'address.required' => 'La dirección es obligatoria.',
            'address.max' => 'La dirección no puede tener más de 255 caracteres.',
            'tel1.max' => 'El teléfono 1 no puede tener más de 20 caracteres.',
            'tel2.max' => 'El teléfono 2 no puede tener más de 20 caracteres.',
        ];
    }
}
