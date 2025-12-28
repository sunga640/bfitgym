<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->enum('payer_type', ['member', 'insurer', 'other'])->default('member');
            $table->foreignId('payer_member_id')->nullable()
                ->constrained('members')->nullOnDelete();
            $table->foreignId('payer_insurer_id')->nullable()
                ->constrained('insurers')->nullOnDelete();

            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('TZS');
            $table->enum('payment_method', ['cash','card','mobile_money','bank_transfer','other'])->default('cash');
            $table->string('reference', 100)->nullable();
            $table->dateTime('paid_at');
            $table->enum('status', ['pending','paid','failed','refunded'])->default('paid');

            $table->enum('revenue_type', ['membership','class_booking','event','pos','insurance','other']);
            $table->nullableMorphs('payable');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'paid_at']);
            $table->index(['revenue_type', 'paid_at']);
            $table->index(['payer_member_id', 'paid_at']);
            $table->index(['payer_insurer_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
