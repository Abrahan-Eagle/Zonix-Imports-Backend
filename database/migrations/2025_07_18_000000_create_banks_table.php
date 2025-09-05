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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del banco (ej: Banesco)
            $table->string('code', 10)->unique(); // Código bancario (ej: 0134)
            $table->string('type')->nullable(); // Público, privado, internacional, etc.
            $table->string('swift_code')->nullable(); // Opcional, para transferencias internacionales
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
}; 