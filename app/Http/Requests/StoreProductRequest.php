<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitir a cualquier usuario autenticado (rol commerce) crear productos
        return auth()->check() && auth()->user()->role === 'commerce';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'available' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            // Campos adicionales si los tienes
            'stock' => 'nullable|integer|min:0',
            'category' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The product name is required',
            'name.max' => 'The name cannot exceed 255 characters',
            'description.required' => 'The description is required',
            'description.max' => 'The description cannot exceed 1000 characters',
            'price.required' => 'The price is required',
            'price.numeric' => 'The price must be a number',
            'price.min' => 'The price cannot be negative',
            'price.max' => 'The price cannot exceed 999,999.99',
            'available.boolean' => 'The availability must be true or false',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'The image must be jpeg, png, jpg or gif',
            'image.max' => 'The image cannot exceed 2MB',
            'stock.integer' => 'The stock must be an integer',
            'stock.min' => 'The stock cannot be negative'
        ];
    }
}
