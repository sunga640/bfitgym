<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workout_plan_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained('workout_plans')->cascadeOnDelete();

            $table->unsignedInteger('day_index'); // 1..N
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 1-7
            $table->string('label', 100)->nullable();
            $table->boolean('is_rest_day')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['workout_plan_id', 'day_index'], 'plan_day_index_unique');
            $table->index(['workout_plan_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_plan_days');
    }
};
