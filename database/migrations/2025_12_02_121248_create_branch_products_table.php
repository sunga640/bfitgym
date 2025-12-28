<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('price', 10, 2);
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_level')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'product_id'], 'branch_product_unique');
            $table->index(['branch_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_products');
    }
};
