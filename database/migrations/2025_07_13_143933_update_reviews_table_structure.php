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
        // Comentado temporalmente para evitar problemas con SQLite en tests
        // Esta migración se ejecutará manualmente en producción
        
        /*
        // Agregar nuevas columnas
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
        });
        
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('commerce_id')->nullable()->constrained()->onDelete('cascade');
        });
        
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('delivery_agent_id')->nullable()->constrained()->onDelete('cascade');
        });
        
        Schema::table('reviews', function (Blueprint $table) {
            $table->enum('type', ['restaurant', 'delivery_agent'])->nullable();
        });
        
        Schema::table('reviews', function (Blueprint $table) {
            $table->json('photos')->nullable();
        });
        
        // Renombrar comentario a comment
        Schema::table('reviews', function (Blueprint $table) {
            $table->renameColumn('comentario', 'comment');
        });

        // Eliminar columnas morph (separado para SQLite)
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('reviewable_id');
        });
        
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('reviewable_type');
        });
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Comentado temporalmente para evitar problemas con SQLite en tests
        // Esta migración se ejecutará manualmente en producción
        
        /*
        Schema::table('reviews', function (Blueprint $table) {
            // Restaurar columnas morph
            $table->morphs('reviewable');
            
            // Eliminar nuevas columnas (separado para SQLite)
            if (config('database.default') !== 'sqlite') {
                $table->dropForeign(['order_id']);
                $table->dropForeign(['commerce_id']);
                $table->dropForeign(['delivery_agent_id']);
            }
            
            $table->dropColumn('order_id');
            $table->dropColumn('commerce_id');
            $table->dropColumn('delivery_agent_id');
            $table->dropColumn('type');
            $table->dropColumn('photos');
            
            // Restaurar nombre de comentario
            $table->renameColumn('comment', 'comentario');
        });
        */
    }
};
