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
        Schema::create('commerces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            
            // Datos del comercio
            $table->string('business_name')->nullable();
            $table->string('business_type')->nullable();
            $table->text('image')->nullable();
            $table->string('phone')->nullable();
            
            // Datos fiscales (requeridos para vendedores)
            $table->string('rif')->unique(); // RIF del comercio (obligatorio)
            $table->string('bank_account')->nullable(); // Cuenta bancaria del vendedor
            
            // Estado y verificación
            $table->boolean('is_verified')->default(false); // Verificación del comercio
            $table->boolean('open')->default(false);
            
            // Métodos de pago habilitados por vendedor (pagos descentralizados)
            $table->json('payment_methods')->nullable(); // ['stripe', 'paypal', 'binance', 'pago_movil', 'zelle']
            
            $table->json('schedule')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerces');
    }
};
