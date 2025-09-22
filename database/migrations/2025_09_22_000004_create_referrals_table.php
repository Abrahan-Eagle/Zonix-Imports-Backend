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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('referrer_profile_id')->constrained('profiles')->onDelete('cascade');
            
            // Configuración de comisión
            $table->unsignedTinyInteger('percentage'); // 0-100
            $table->decimal('commission_earned', 10, 2)->default(0); // Comisión ganada
            
            // Link y control
            $table->string('link')->unique();
            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable(); // Expiración del link
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};


