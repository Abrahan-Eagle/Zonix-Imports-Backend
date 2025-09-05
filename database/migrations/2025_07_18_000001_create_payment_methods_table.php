<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commerce_id')->constrained()->onDelete('cascade');
            // Tipo de método de pago
            // card: Tarjeta de crédito/débito (Visa, Mastercard, Amex, etc.)
            // mobile_payment: Pago móvil venezolano
            // cash: Efectivo
            // paypal, stripe, mercadopago: Pasarelas
            // digital_wallet: Apple Pay, Google Pay, etc.
            // bank_transfer: Transferencia bancaria
            // other: Otro método
            $table->enum('type', [
                'card', 'mobile_payment', 'cash', 'paypal', 'stripe', 'mercadopago', 'digital_wallet', 'bank_transfer', 'other'
            ]);
            // Comunes
            $table->boolean('is_default')->default(false); // Si es el método principal
            $table->boolean('is_active')->default(true);  // Si está activo
            // Tarjeta (card, stripe, mercadopago)
            $table->string('brand')->nullable(); // Visa, Mastercard, Amex, PayPal, etc.
            $table->string('last4', 4)->nullable(); // Últimos 4 dígitos
            $table->integer('exp_month')->nullable();
            $table->integer('exp_year')->nullable();
            $table->string('cardholder_name')->nullable();
            // Pago móvil (mobile_payment)
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null'); // Relación con bancos
            $table->string('phone')->nullable(); // Teléfono asociado
            // Transferencia bancaria (bank_transfer)
            $table->string('account_number')->nullable(); // Número de cuenta
            // Billetera digital (digital_wallet, paypal)
            $table->string('email')->nullable(); // Email asociado
            // Otros datos adicionales (JSON): referencia, cédula, etc.
            $table->json('reference_info')->nullable();
            $table->string('owner_name')->nullable(); // Nombre del titular
            $table->string('owner_id')->nullable();   // Cédula/RIF del titular
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
}; 