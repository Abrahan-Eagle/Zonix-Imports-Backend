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
        Schema::create('delivery_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('delivery_companies')->onDelete('cascade');
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            $table->enum('estado', ['activo', 'inactivo', 'suspendido'])->default('activo');
            $table->boolean('trabajando')->default(false);
            $table->decimal('rating', 3, 2)->nullable();
                // Campos específicos para delivery
                $table->string('vehicle_type')->nullable();
                $table->string('phone', 20)->nullable(); // Agregado para evitar duplicación
                $table->string('license_number')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_agents');
    }
};
