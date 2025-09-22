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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('method', ['stripe', 'paypal', 'binance', 'pago_movil', 'zelle']);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded', 'cancelled'])->default('pending');
            
            // Referencias y IDs externos
            $table->string('reference')->nullable(); // intent id, approval id, tx id, ref manual
            $table->string('external_id')->nullable(); // ID del proveedor (Stripe, PayPal, etc.)
            
            // Moneda y timestamps
            $table->string('currency', 3)->default('USD'); // Moneda del pago
            $table->timestamp('processed_at')->nullable(); // Cuándo se procesó
            
            // Comprobantes y debugging
            $table->string('receipt_url')->nullable(); // comprobante manual
            $table->text('failure_reason')->nullable(); // Razón del fallo si aplica
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Tabla para idempotencia de webhooks (registro de event_id procesados)
        Schema::create('processed_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // stripe|paypal|binance
            $table->string('event_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_webhook_events');
        Schema::dropIfExists('payments');
    }
};


