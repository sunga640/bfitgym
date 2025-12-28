<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()
                ->constrained('payment_transactions')->nullOnDelete();

            $table->dateTime('booked_at');
            $table->enum('status', ['pending','confirmed','cancelled','attended','no_show'])->default('pending');
            $table->decimal('booking_fee_amount', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_session_id', 'status']);
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_bookings');
    }
};
