<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualPaymentRequest extends FormRequest
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
            'order_id' => 'required|integer|exists:orders,id',
            'payment_method' => 'required|in:pago_movil,zelle',
            'receipt_url' => 'required|string',
            'reference' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'account' => 'nullable|string|max:50'
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
            'order_id.required' => 'El ID de la orden es obligatorio',
            'order_id.exists' => 'La orden no existe',
            'payment_method.required' => 'El método de pago es obligatorio',
            'payment_method.in' => 'El método de pago debe ser pago_movil o zelle',
            'receipt_url.required' => 'El comprobante de pago es obligatorio',
            'receipt_url.string' => 'El comprobante debe ser una URL válida',
            'reference.max' => 'La referencia no puede exceder 50 caracteres',
            'bank.max' => 'El nombre del banco no puede exceder 100 caracteres',
            'phone.max' => 'El teléfono no puede exceder 20 caracteres',
            'account.max' => 'La cuenta no puede exceder 50 caracteres'
        ];
    }
}

