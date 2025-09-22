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
        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            
            // Número de teléfono completo
            $table->string('operator_code', 4); // Código directo (0412, 0424, etc.)
            $table->string('country_code', 4)->default('+58'); // Código de país
            $table->string('number', 7); // Número local
            
            // Configuración
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true); // Más claro que 'status'
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};
