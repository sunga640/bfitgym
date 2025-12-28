<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()
                ->constrained('members')->nullOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()
                ->constrained('payment_transactions')->nullOnDelete();

            $table->string('sale_number', 100)->unique();
            $table->dateTime('sale_datetime');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['draft','completed','refunded'])->default('completed');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'sale_datetime']);
            $table->index(['member_id', 'sale_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};
