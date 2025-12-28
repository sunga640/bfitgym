<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_control_device_id')
                ->constrained('access_control_devices')->cascadeOnDelete();
            $table->foreignId('access_identity_id')
                ->constrained('access_identities')->cascadeOnDelete();

            $table->enum('subject_type', ['member','staff']);
            $table->unsignedBigInteger('subject_id');

            $table->enum('direction', ['in','out','unknown'])->default('in');
            $table->dateTime('event_timestamp');
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'event_timestamp'], 'access_branch_time_index');
            $table->index(['subject_type', 'subject_id', 'event_timestamp'], 'access_subject_time_index');
            $table->index(['access_control_device_id', 'event_timestamp'], 'access_device_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
