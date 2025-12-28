<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_control_device_commands', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            // UUIDs are required for idempotency across agent retries.
            $table->uuid('id')->primary();

            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('access_control_device_id')->index();
            $table->unsignedBigInteger('claimed_by_agent_id')->nullable()->index();

            $table->enum('subject_type', ['member', 'staff'])->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();

            $table->string('type', 60)->index(); // canonical command type
            $table->unsignedSmallInteger('priority')->default(0)->index();

            $table->enum('status', [
                'pending',
                'claimed',
                'processing',
                'done',
                'failed',
                'cancelled',
                'superseded',
            ])->default('pending')->index();

            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(10);

            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('superseded_at')->nullable()->index();

            $table->json('payload')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            // Efficient agent polling / claiming (branch-safe + device-safe).
            $table->index(
                ['branch_id', 'access_control_device_id', 'status', 'available_at', 'priority', 'created_at'],
                'cmd_claim_idx'
            );
            $table->index(
                ['status', 'available_at', 'priority', 'created_at'],
                'cmd_status_available_priority_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_control_device_commands');
    }
};
