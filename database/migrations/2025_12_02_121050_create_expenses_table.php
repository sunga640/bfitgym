<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable()
                ->constrained('expense_categories')->nullOnDelete();

            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('TZS');
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'expense_date']);
            $table->index(['expense_category_id', 'expense_date'], 'expense_category_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
