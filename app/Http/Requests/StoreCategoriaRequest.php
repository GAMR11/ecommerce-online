<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoriaRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Asegura que cualquiera pueda acceder
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|min:1|max:30',
            'descripcion' => 'nullable|string|max:400',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es requerido.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',

            'descripcion.max' => 'El nombre no puede tener más de 400 caracteres.',
        ];
    }
}
