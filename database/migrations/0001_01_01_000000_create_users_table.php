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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); // Nullable para usuarios que se autentican solo con Google
            $table->string('google_id')->nullable()->unique(); // Agregar este campo para el ID de Google
            $table->string('given_name')->nullable(); // Agregar campo para el nombre dado
            $table->string('family_name')->nullable(); // Agregar campo para el apellido
            $table->string('profile_pic')->nullable(); // Agregar campo para la foto de perfil
            $table->string('AccessToken')->nullable(); // Campo para guardar el token de acceso
            $table->boolean('completed_onboarding')->default(false);
            $table->enum('role', ['admin', 'users', 'commerce', 'delivery_company', 'delivery_agent', 'delivery'])->default('users');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
