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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->timestamp('payment_validated_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('delivery_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'reference_number',
                'payment_validated_at',
                'cancellation_reason',
                'delivery_address',
            ]);
        });
    }
};
