<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
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
            'payment_method' => 'required|in:stripe,paypal,binance,pago_movil,zelle',
            'currency' => 'nullable|string|size:3',
            'crypto_currency' => 'nullable|string|in:USDT,BTC,BNB'
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
            'payment_method.in' => 'El método de pago no es válido',
            'currency.size' => 'La moneda debe tener 3 caracteres',
            'crypto_currency.in' => 'La criptomoneda debe ser USDT, BTC o BNB'
        ];
    }
}

