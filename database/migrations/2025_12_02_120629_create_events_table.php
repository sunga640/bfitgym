<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('type', ['public','paid','internal'])->default('public');
            $table->string('location')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('allow_non_members')->default(true);
            $table->enum('status', ['scheduled','completed','cancelled'])->default('scheduled');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'start_datetime']);
            $table->index(['type', 'start_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
