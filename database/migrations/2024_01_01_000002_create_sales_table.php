<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id');
            $table->date('order_date');
            $table->string('customer_id');
            $table->string('customer_name');
            $table->string('product_id');
            $table->string('product_name');
            $table->string('category');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 5, 4); // 0.1000 = 10%
            $table->decimal('total', 14, 2); // quantity * unit_price * (1 - discount)
            $table->string('country');
            $table->timestamps();

            // Índices pensados exactamente para las agregaciones del reporte:
            // total por import, top productos, agrupación por categoría y país.
            $table->index(['import_id', 'product_id']);
            $table->index(['import_id', 'category']);
            $table->index(['import_id', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
