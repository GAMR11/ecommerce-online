<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true; // Asegura que cualquiera pueda acceder
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|min:3|max:50',
            'marca' => 'required|string|min:1|max:50',
            'modelo' => 'required|string|min:1|max:50',
            'color' => 'required|string|min:1|max:50',
            'precio_original' => 'required|numeric|min:1|max:10000',
            'precio_contado' => 'required|numeric|min:1|max:10000',
            'precio_credito' => 'required|numeric|min:1|max:10000',
            'categoria_id' => 'required|string|min:1|max:50',
            // 'imagenes' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'descripcion' => 'nullable|string|max:400',
            'imagenes' => 'array', // Asegura que 'imagenes' es un array
            'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es requerido.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',

            'marca.required' => 'La marca es requerida.',
            'marca.min' => 'La marca debe tener al menos 1 carácter.',
            'marca.max' => 'La marca no puede tener más de 50 caracteres.',

            'modelo.required' => 'El modelo es requerido.',
            'modelo.min' => 'El modelo debe tener al menos 1 carácter.',
            'modelo.max' => 'El modelo no puede tener más de 50 caracteres.',

            'color.required' => 'El color es requerido.',
            'color.min' => 'El color debe tener al menos 1 carácter.',
            'color.max' => 'El color no puede tener más de 50 caracteres.',

            'precio_original.required' => 'El precio original es requerido.',
            'precio_original.numeric' => 'El precio original debe ser un número.',
            'precio_original.min' => 'El precio original debe ser mayor a 0.',
            'precio_original.max' => 'El precio original no puede ser mayor a 10,000.',

            'precio_contado.required' => 'El precio de contado es requerido.',
            'precio_contado.numeric' => 'El precio de contado debe ser un número.',
            'precio_contado.min' => 'El precio de contado debe ser mayor a 0.',
            'precio_contado.max' => 'El precio de contado no puede ser mayor a 10,000.',

            'precio_credito.required' => 'El precio a crédito es requerido.',
            'precio_credito.numeric' => 'El precio a crédito debe ser un número.',
            'precio_credito.min' => 'El precio a crédito debe ser mayor a 0.',
            'precio_credito.max' => 'El precio a crédito no puede ser mayor a 10,000.',

            'categoria_id.required' => 'La categoría es requerida.',

            'imagenes.array' => 'El campo imágenes debe ser un conjunto de archivos.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.mimes' => 'Las imágenes deben estar en formato JPEG, PNG, JPG o GIF.',
            'imagenes.*.max' => 'Cada imagen no puede superar los 2MB.',

            'descripcion.max' => 'El nombre no puede tener más de 400 caracteres.',
        ];
    }
}
