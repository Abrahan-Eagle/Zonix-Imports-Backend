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
        Schema::create('operator_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique(); // Ejemplo: 0412
            $table->string('name'); // Nombre del operador, por ejemplo: "Movilnet"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operator_codes');
    }
};
