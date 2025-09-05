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
        // Rename delivery_companies fields if they exist
        if (Schema::hasColumn('delivery_companies', 'nombre')) {
            Schema::table('delivery_companies', function (Blueprint $table) {
                $table->renameColumn('nombre', 'name');
            });
        }
        if (Schema::hasColumn('delivery_companies', 'ci')) {
            Schema::table('delivery_companies', function (Blueprint $table) {
                $table->renameColumn('ci', 'tax_id');
            });
        }
        if (Schema::hasColumn('delivery_companies', 'telefono')) {
            Schema::table('delivery_companies', function (Blueprint $table) {
                $table->renameColumn('telefono', 'phone');
            });
        }
        if (Schema::hasColumn('delivery_companies', 'direccion')) {
            Schema::table('delivery_companies', function (Blueprint $table) {
                $table->renameColumn('direccion', 'address');
            });
        }

        // Rename posts fields if they exist
        if (Schema::hasColumn('posts', 'descripcion')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->renameColumn('descripcion', 'description');
            });
        }

        // Rename delivery_agents fields if they exist
        if (Schema::hasColumn('delivery_agents', 'estado')) {
            Schema::table('delivery_agents', function (Blueprint $table) {
                $table->renameColumn('estado', 'status');
            });
        }
        if (Schema::hasColumn('delivery_agents', 'trabajando')) {
            Schema::table('delivery_agents', function (Blueprint $table) {
                $table->renameColumn('trabajando', 'working');
            });
        }

        // Drop estado column from orders since status already exists
        if (Schema::hasColumn('orders', 'estado')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }

        // Note: order_delivery already has 'status' column, no need to rename
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert delivery_companies fields
        if (Schema::hasColumn('delivery_companies', 'name')) {
            Schema::table('delivery_companies', function (Blueprint $table) {
                $table->renameColumn('name', 'nombre');
            });
        }
        if (Schema::hasColumn('delivery_companies', 'tax_id')) {
            Schema::table('delivery_companies', function (Blueprint $table) {
                $table->renameColumn('tax_id', 'ci');
            });
        }
        if (Schema::hasColumn('delivery_companies', 'phone')) {
            Schema::table('delivery_companies', function (Blueprint $table) {
                $table->renameColumn('phone', 'telefono');
            });
        }
        if (Schema::hasColumn('delivery_companies', 'address')) {
            Schema::table('delivery_companies', function (Blueprint $table) {
                $table->renameColumn('address', 'direccion');
            });
        }

        // Revert posts fields
        if (Schema::hasColumn('posts', 'description')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->renameColumn('description', 'descripcion');
            });
        }

        // Revert delivery_agents fields
        if (Schema::hasColumn('delivery_agents', 'status')) {
            Schema::table('delivery_agents', function (Blueprint $table) {
                $table->renameColumn('status', 'estado');
            });
        }
        if (Schema::hasColumn('delivery_agents', 'working')) {
            Schema::table('delivery_agents', function (Blueprint $table) {
                $table->renameColumn('working', 'trabajando');
            });
        }

        // Add back estado column to orders
        if (!Schema::hasColumn('orders', 'estado')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('estado')->default('pendiente')->after('status');
            });
        }
    }
};
