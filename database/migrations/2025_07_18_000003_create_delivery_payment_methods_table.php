<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_agent_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null');
            $table->enum('type', [
                'card', 'mobile_payment', 'cash', 'paypal', 'digital_wallet', 'bank_transfer', 'other'
            ]);
            $table->string('brand')->nullable();
            $table->string('account_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_payment_methods');
    }
}; 