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
        Schema::create('profiles', function (Blueprint $table) {
           $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Datos personales
            $table->string('firstName');
            $table->string('middleName')->nullable();
            $table->string('lastName');
            $table->string('secondLastName')->nullable();
            $table->string('photo_users')->nullable();
            $table->date('date_of_birth')->nullable();
            
            // Rol del usuario (simplificado para MVP)
            $table->enum('role', ['buyer', 'seller', 'admin'])->default('buyer');
            
            // Estado del perfil
            $table->enum('status', ['completeData', 'incompleteData', 'notverified'])->default('notverified');
            $table->boolean('is_verified')->default(false);
            
            // Contacto
            $table->string('phone')->nullable();
            
            // Datos para vendedores (requeridos según .cursorrules)
            $table->string('rif')->nullable(); // RIF del vendedor
            $table->string('bank_account')->nullable(); // Cuenta bancaria para pagos descentralizados
            
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
