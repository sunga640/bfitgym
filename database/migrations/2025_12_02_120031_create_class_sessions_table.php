<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_type_id')->constrained('class_types')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('main_instructor_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week')->nullable(); // 1-7
            $table->date('specific_date')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity_override')->nullable();
            $table->boolean('is_recurring')->default(true);
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'day_of_week']);
            $table->index(['branch_id', 'specific_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
