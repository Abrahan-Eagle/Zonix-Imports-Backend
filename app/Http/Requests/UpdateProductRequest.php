<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'nullable|integer|exists:categories,id',
            'modality' => 'nullable|in:retail,wholesale,preorder,referral,dropshipping',
            'base_price' => 'sometimes|required|numeric|min:0|max:999999.99',
            'stock' => 'nullable|integer|min:0',
            'min_wholesale_quantity' => 'nullable|integer|min:1',
            'wholesale_price' => 'nullable|numeric|min:0|max:999999.99',
            'preorder_eta' => 'nullable|date',
            'origin_product_id' => 'nullable|integer|exists:products,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'video_url' => 'nullable|url',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'available' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The product name is required',
            'name.max' => 'The name cannot exceed 255 characters',
            'description.required' => 'The description is required',
            'description.max' => 'The description cannot exceed 1000 characters',
            'base_price.required' => 'The price is required',
            'base_price.numeric' => 'The price must be a number',
            'base_price.min' => 'The price cannot be negative',
            'base_price.max' => 'The price cannot exceed 999,999.99',
            'available.boolean' => 'The availability must be true or false',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'The image must be jpeg, png, jpg or gif',
            'image.max' => 'The image cannot exceed 2MB',
            'stock.integer' => 'The stock must be an integer',
            'stock.min' => 'The stock cannot be negative'
        ];
    }
}
