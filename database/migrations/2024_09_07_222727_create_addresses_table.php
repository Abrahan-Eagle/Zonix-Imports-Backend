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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            
            // Dirección principal
            $table->string('street');
            $table->string('house_number');
            $table->string('address_line_2')->nullable(); // Segunda línea de dirección
            
            // Referencias importantes en Venezuela
            $table->text('reference')->nullable(); // Referencias del lugar
            
            // Código postal (opcional en Venezuela)
            $table->string('postal_code')->nullable();
            
            // Coordenadas (opcionales para MVP)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Estado y configuración
            $table->enum('status', ['completeData', 'incompleteData', 'notverified'])->default('notverified');
            $table->boolean('is_default')->default(false); // Dirección por defecto
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
