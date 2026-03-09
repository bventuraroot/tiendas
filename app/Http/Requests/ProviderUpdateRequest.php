<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderUpdateRequest extends FormRequest
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
        $providerId = $this->input('idupdate');

        $rules = [
            'idupdate' => 'required|exists:providers,id',
            'razonsocialupdate' => 'required|string|max:255',
            'ncrupdate' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('providers', 'ncr')->ignore($providerId)->where(function ($query) {
                    return $query->whereNotNull('ncr');
                })
            ],
            'nitupdate' => [
                'nullable',
                'string',
                'max:25',
                Rule::unique('providers', 'nit')->ignore($providerId)->where(function ($query) {
                    return $query->whereNotNull('nit');
                })
            ],
            'emailupdate' => 'nullable|email|max:255',
            'companyupdate' => 'nullable|exists:companies,id',
            'countryedit' => 'required|exists:countries,id',
            'departamentedit' => 'required|exists:departments,id',
            'municipioedit' => 'required|exists:municipalities,id',
            'addressupdate' => 'required|string|max:255',
            'tel1update' => 'nullable|string|max:20',
            'tel2update' => 'nullable|string|max:20',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'idupdate.required' => 'El ID del proveedor es obligatorio.',
            'idupdate.exists' => 'El proveedor no existe.',
            'razonsocialupdate.required' => 'La razón social es obligatoria.',
            'razonsocialupdate.max' => 'La razón social no puede tener más de 255 caracteres.',
            'ncrupdate.unique' => 'Ya existe un proveedor con este NCR.',
            'ncrupdate.max' => 'El NCR no puede tener más de 15 caracteres.',
            'nitupdate.unique' => 'Ya existe un proveedor con este NIT.',
            'nitupdate.max' => 'El NIT no puede tener más de 25 caracteres.',
            'emailupdate.email' => 'El correo electrónico debe tener un formato válido.',
            'emailupdate.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
            'companyupdate.exists' => 'La empresa seleccionada no existe.',
            'countryedit.required' => 'El país es obligatorio.',
            'countryedit.exists' => 'El país seleccionado no existe.',
            'departamentedit.required' => 'El departamento es obligatorio.',
            'departamentedit.exists' => 'El departamento seleccionado no existe.',
            'municipioedit.required' => 'El municipio es obligatorio.',
            'municipioedit.exists' => 'El municipio seleccionado no existe.',
            'addressupdate.required' => 'La dirección es obligatoria.',
            'addressupdate.max' => 'La dirección no puede tener más de 255 caracteres.',
            'tel1update.max' => 'El teléfono 1 no puede tener más de 20 caracteres.',
            'tel2update.max' => 'El teléfono 2 no puede tener más de 20 caracteres.',
        ];
    }
}
