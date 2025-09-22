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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            // Vendedor (comercio) responsable del pedido
            $table->foreignId('commerce_id')->constrained()->onDelete('cascade');

            // Modalidad del pedido, alineada a la modalidad del/los productos
            $table->enum('modality', ['retail', 'wholesale', 'preorder', 'referral', 'dropshipping'])->default('retail');

            // Entrega/retira
            $table->enum('delivery_type', ['pickup', 'delivery']);

            // Estados con enfoque de e-commerce
            $table->enum('status', [
                'pending_payment',    // creado, esperando pago
                'partially_paid',     // pre-order con abonos
                'paid',               // pagado 100%
                'preparing',
                'on_way',
                'delivered',
                'cancelled'
            ])->default('pending_payment');

            // Totales
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Direcciones separadas para envío y facturación
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses')->nullOnDelete();

            // Logística y seguimiento
            $table->string('tracking_number')->nullable(); // Para seguimiento de envío
            $table->timestamp('estimated_delivery')->nullable(); // Fecha estimada de entrega

            // Pago manual (comprobante)
            $table->text('receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
