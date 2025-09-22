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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commerce_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // Categoría del producto
            
            // Identificación
            $table->string('name');
            $table->string('sku')->nullable(); // Código único del producto
            $table->text('description')->nullable();
            
            // Modalidad del producto: detal, mayor, pre-order, referidos, dropshipping
            $table->enum('modality', ['retail', 'wholesale', 'preorder', 'referral', 'dropshipping'])->default('retail');

            // Precios y stock
            $table->decimal('base_price', 10, 2); // precio_unitario base (detal)
            $table->unsignedInteger('stock')->default(0);

            // Mayor (wholesale)
            $table->unsignedInteger('min_wholesale_quantity')->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();

            // Pre-order
            $table->date('preorder_eta')->nullable();

            // Dropshipping interno: referencia a producto origen
            $table->foreignId('origin_product_id')->nullable()->constrained('products')->nullOnDelete();

            // Medios
            $table->text('image')->nullable(); // imagen principal opcional
            $table->string('video_url')->nullable();

            // Logística (para cálculo de envío)
            $table->decimal('weight', 8, 2)->nullable(); // Peso en kg
            $table->json('dimensions')->nullable(); // {length, width, height} en cm

            // Estado de disponibilidad
            $table->boolean('available')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
