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
        Schema::table('order_delivery', function (Blueprint $table) {
            $table->string('status', 32)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_delivery', function (Blueprint $table) {
            $table->string('status', 10)->change(); // Asumimos que antes era 10
        });
    }
};
