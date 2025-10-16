<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateCheckoutRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_address_id' => 'required|integer|exists:addresses,id',
            'delivery_type' => 'required|in:pickup,delivery',
            'billing_address_id' => 'nullable|integer|exists:addresses,id'
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
            'shipping_address_id.required' => 'La dirección de envío es obligatoria',
            'shipping_address_id.exists' => 'La dirección de envío no existe',
            'delivery_type.required' => 'El tipo de entrega es obligatorio',
            'delivery_type.in' => 'El tipo de entrega debe ser "pickup" o "delivery"',
            'billing_address_id.exists' => 'La dirección de facturación no existe'
        ];
    }
}

