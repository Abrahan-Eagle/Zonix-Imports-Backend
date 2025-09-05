<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar la tabla existente si existe
        Schema::dropIfExists('payment_methods');
        
        // Crear la nueva tabla unificada
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            
            // Relación polimórfica
            $table->morphs('payable'); // Crea payable_type y payable_id
            
            // Campos comunes
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null');
            $table->enum('type', [
                'card', 'mobile_payment', 'cash', 'paypal', 'stripe', 'mercadopago', 
                'digital_wallet', 'bank_transfer', 'other'
            ]);
            
            // Campos para tarjetas
            $table->string('brand')->nullable(); // Visa, Mastercard, etc.
            $table->string('last4', 4)->nullable(); // Últimos 4 dígitos
            $table->integer('exp_month')->nullable();
            $table->integer('exp_year')->nullable();
            $table->string('cardholder_name')->nullable();
            
            // Campos para pagos móviles y transferencias
            $table->string('account_number')->nullable();
            $table->string('phone')->nullable();
            
            // Campos para billeteras digitales
            $table->string('email')->nullable();
            
            // Campos adicionales
            $table->json('reference_info')->nullable(); // Información adicional en JSON
            $table->string('owner_name')->nullable();
            $table->string('owner_id')->nullable();
            
            // Estados
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Índices para optimizar consultas (Laravel ya crea el índice polimórfico automáticamente)
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
}; 