<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorización en middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'modality' => 'required|string|in:retail,wholesale,preorder,referral,dropshipping',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es requerido',
            'product_id.exists' => 'El producto no existe',
            'quantity.required' => 'La cantidad es requerida',
            'quantity.min' => 'La cantidad mínima es 1',
            'quantity.max' => 'La cantidad máxima es 1000',
            'modality.required' => 'La modalidad es requerida',
            'modality.in' => 'Modalidad inválida. Opciones: retail, wholesale, preorder, referral, dropshipping',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación',
            'errors' => $validator->errors()
        ], 422));
    }
}

