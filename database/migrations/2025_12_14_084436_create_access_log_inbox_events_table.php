<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_log_inbox_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');

            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('access_control_device_id')->index();

            // idempotency
            $table->string('device_event_uid', 150)->nullable();
            $table->string('device_user_id', 100)->nullable()->index();

            $table->enum('subject_type', ['member', 'staff'])->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();

            $table->enum('direction', ['in', 'out', 'unknown'])->default('in');
            $table->dateTime('event_timestamp')->index();

            $table->json('raw_payload')->nullable();

            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(10);
            $table->text('last_error')->nullable();

            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamp('processed_at')->nullable()->index();

            $table->timestamps();

            // agent retries -> ignore duplicates
            $table->unique(['access_control_device_id', 'device_event_uid'], 'inbox_device_event_uid_unique');

            $table->index(['status', 'received_at'], 'inbox_status_received_idx');
            $table->index(['branch_id', 'status', 'event_timestamp'], 'inbox_branch_status_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_log_inbox_events');
    }
};
