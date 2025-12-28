<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('has_booking_fee')->default(false);
            $table->decimal('booking_fee', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
        });

        // MySQL 8+ check constraint (SQLite cannot ALTER TABLE ADD CONSTRAINT).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                ALTER TABLE class_types
                ADD CONSTRAINT class_types_booking_fee_check CHECK (
                    (has_booking_fee = 0 AND booking_fee IS NULL)
                    OR (has_booking_fee = 1 AND booking_fee IS NOT NULL)
                )
            ');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_types');
    }
};
