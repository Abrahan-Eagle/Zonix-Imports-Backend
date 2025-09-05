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
            $table->foreignId('commerce_id')->constrained()->onDelete('cascade');
            $table->enum('delivery_type', ['pickup', 'delivery']);
            $table->enum('status', ['pending_payment', 'paid', 'preparing', 'on_way', 'delivered', 'cancelled']);
            $table->decimal('total', 10, 2);
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
