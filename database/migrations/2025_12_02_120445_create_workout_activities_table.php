<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workout_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_day_id')->constrained('workout_plan_days')->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()
                ->constrained('exercises')->nullOnDelete();

            $table->string('exercise_name', 150);
            $table->unsignedInteger('sets')->nullable();
            $table->unsignedInteger('reps')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workout_plan_day_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_activities');
    }
};
