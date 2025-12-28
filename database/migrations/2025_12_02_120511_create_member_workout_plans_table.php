<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('workout_plan_id')->constrained('workout_plans')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active','completed','cancelled'])->default('active');
            $table->unsignedInteger('current_day_index')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['member_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_workout_plans');
    }
};
