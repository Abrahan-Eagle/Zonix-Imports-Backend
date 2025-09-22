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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            
            // Tipo de documento (simplificado para MVP)
            $table->enum('type', ['ci', 'passport', 'rif'])->nullable();
            
            // Número del documento (más genérico)
            $table->string('document_number')->nullable();
            
            // URLs de documentos
            $table->string('rif_url')->nullable(); // URL del RIF
            $table->string('front_image')->nullable(); // Imagen del frente
            $table->string('back_image')->nullable(); // Imagen del reverso
            
            // Fechas
            $table->date('issued_at')->nullable(); // Fecha de emisión
            $table->date('expires_at')->nullable(); // Fecha de expiración
            
            // Estado y verificación
            $table->boolean('approved')->default(false); // Si el documento está aprobado
            $table->timestamp('verified_at')->nullable(); // Cuándo se verificó
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
