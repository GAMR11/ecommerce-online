<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Asegura que cualquiera pueda acceder
    }

    public function rules()
    {
        // 'marca',
        // 'modelo',
        // 'color',
        // 'imagen',
        // 'descripcion',
        // 'precio_original',
        // 'precio_contado',
        // 'precio_credito',
        // 'categoria_id',
        return [
            'nombre' => 'required|string|min:3|max:50',
            'marca' => 'required|string|min:1|max:50',
            'modelo' => 'required|string|min:1|max:50',
            'color' => 'required|string|min:1|max:50',
            'preciooriginal' => 'required|numeric|min:1|max:10000',
            'preciocontado' => 'required|numeric|min:1|max:10000',
            'preciocredito' => 'required|numeric|min:1|max:10000',
            'categoria' => 'required|string|min:1|max:50',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

            'preciooriginal.required' => 'El precio original es requerido.',
            'preciooriginal.numeric' => 'El precio original debe ser un número.',
            'preciooriginal.min' => 'El precio original debe ser mayor a 0.',
            'preciooriginal.max' => 'El precio original no puede ser mayor a 10,000.',

            'preciocontado.required' => 'El precio de contado es requerido.',
            'preciocontado.numeric' => 'El precio de contado debe ser un número.',
            'preciocontado.min' => 'El precio de contado debe ser mayor a 0.',
            'preciocontado.max' => 'El precio de contado no puede ser mayor a 10,000.',

            'preciocredito.required' => 'El precio a crédito es requerido.',
            'preciocredito.numeric' => 'El precio a crédito debe ser un número.',
            'preciocredito.min' => 'El precio a crédito debe ser mayor a 0.',
            'preciocredito.max' => 'El precio a crédito no puede ser mayor a 10,000.',

            'categoria.required' => 'La categoría es requerida.',

            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe estar en formato JPEG, PNG, JPG o GIF.',
            'imagen.max' => 'El tamaño máximo permitido para la imagen es 2MB.',
        ];
    }

}
