<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cvsecurity_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->string('name', 160);
            $table->string('status', 30)->default('pending'); // disconnected|pending|paired|connected|error|disabled
            $table->string('pairing_status', 30)->default('unpaired'); // unpaired|token_issued|paired|expired
            $table->string('agent_status', 30)->default('offline'); // online|offline|unknown
            $table->string('cvsecurity_status', 30)->default('unknown'); // reachable|unreachable|unknown

            $table->string('agent_label', 150)->nullable();
            $table->string('cv_base_url', 255)->nullable();
            $table->unsignedInteger('cv_port')->nullable();
            $table->string('cv_username', 120)->nullable();
            $table->text('cv_password_encrypted')->nullable();
            $table->text('cv_api_token_encrypted')->nullable();
            $table->unsignedInteger('poll_interval_seconds')->default(30);
            $table->string('timezone', 80)->nullable();
            $table->text('notes')->nullable();

            $table->boolean('agent_test_requested')->default(false);
            $table->boolean('agent_sync_requested')->default(false);
            $table->boolean('agent_event_pull_requested')->default(false);

            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('last_test_result')->nullable();
            $table->timestamp('disabled_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['branch_id', 'status'], 'cvsec_conn_branch_status_idx');
            $table->index(['status', 'agent_status', 'cvsecurity_status'], 'cvsec_conn_status_idx');
            $table->unique(['branch_id', 'name'], 'cvsec_conn_branch_name_unique');
        });

        Schema::create('cvsecurity_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cvsecurity_connection_id')->constrained('cvsecurity_connections')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->uuid('uuid')->unique();
            $table->string('display_name', 150);
            $table->string('status', 30)->default('pending'); // pending|active|revoked|offline
            $table->string('os', 30)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('last_ip', 64)->nullable();

            $table->string('auth_token_hash', 64);
            $table->text('auth_token_encrypted')->nullable();

            $table->timestamp('paired_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['branch_id', 'status'], 'cvsec_agents_branch_status_idx');
            $table->index(['cvsecurity_connection_id', 'status'], 'cvsec_agents_conn_status_idx');
        });

        Schema::create('cvsecurity_pairing_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cvsecurity_connection_id')->constrained('cvsecurity_connections')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('claimed_by_agent_id')->nullable()->constrained('cvsecurity_agents')->nullOnDelete();

            $table->string('token_hash', 64)->unique();
            $table->string('token_hint', 16);
            $table->timestamp('expires_at');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['cvsecurity_connection_id', 'expires_at'], 'cvsec_pairing_conn_expires_idx');
            $table->index(['branch_id', 'expires_at'], 'cvsec_pairing_branch_expires_idx');
        });

        Schema::create('cvsecurity_sync_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cvsecurity_connection_id')->constrained('cvsecurity_connections')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->string('events_cursor', 255)->nullable();
            $table->timestamp('last_member_sync_at')->nullable();
            $table->timestamp('last_event_pull_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->unsignedInteger('pending_members_count')->default(0);
            $table->unsignedInteger('failed_members_count')->default(0);
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique('cvsecurity_connection_id', 'cvsec_sync_state_connection_unique');
            $table->index(['branch_id', 'last_member_sync_at'], 'cvsec_sync_state_branch_sync_idx');
        });

        Schema::create('cvsecurity_member_sync_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cvsecurity_connection_id')->constrained('cvsecurity_connections')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('claimed_by_agent_id')->nullable()->constrained('cvsecurity_agents')->nullOnDelete();

            $table->string('sync_action', 40); // upsert|enable|disable|revoke
            $table->string('desired_state', 20)->nullable(); // active|inactive
            $table->string('external_person_id', 120)->nullable();
            $table->string('status', 30)->default('pending'); // pending|processing|done|failed|retry
            $table->unsignedInteger('attempts')->default(0);
            $table->string('dedupe_key', 64)->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('payload');
            $table->json('result')->nullable();

            $table->timestamps();

            $table->index(['cvsecurity_connection_id', 'status', 'available_at'], 'cvsec_sync_items_claim_idx');
            $table->index(['branch_id', 'status', 'created_at'], 'cvsec_sync_items_branch_status_idx');
            $table->index(['member_id', 'status'], 'cvsec_sync_items_member_status_idx');
            $table->index(['cvsecurity_connection_id', 'dedupe_key'], 'cvsec_sync_items_dedupe_idx');
        });

        Schema::create('cvsecurity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cvsecurity_connection_id')->constrained('cvsecurity_connections')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('cvsecurity_agents')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();

            $table->string('external_event_id', 120)->nullable();
            $table->string('external_person_id', 120)->nullable();
            $table->string('event_type', 80);
            $table->string('direction', 20)->nullable();
            $table->timestamp('occurred_at');
            $table->string('device_id', 120)->nullable();
            $table->string('door_id', 120)->nullable();
            $table->string('reader_id', 120)->nullable();
            $table->string('processing_status', 30)->default('received');
            $table->string('dedupe_hash', 64);
            $table->json('raw_payload')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->unique(['cvsecurity_connection_id', 'dedupe_hash'], 'cvsec_events_conn_dedupe_unique');
            $table->index(['cvsecurity_connection_id', 'occurred_at'], 'cvsec_events_conn_occurred_idx');
            $table->index(['branch_id', 'occurred_at'], 'cvsec_events_branch_occurred_idx');
            $table->index(['member_id', 'occurred_at'], 'cvsec_events_member_occurred_idx');
            $table->index(['external_event_id'], 'cvsec_events_external_event_idx');
        });

        Schema::create('cvsecurity_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cvsecurity_connection_id')->nullable()->constrained('cvsecurity_connections')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('cvsecurity_agents')->nullOnDelete();

            $table->string('level', 20)->default('info');
            $table->string('event', 120);
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['cvsecurity_connection_id', 'created_at'], 'cvsec_logs_conn_created_idx');
            $table->index(['branch_id', 'created_at'], 'cvsec_logs_branch_created_idx');
            $table->index(['level', 'created_at'], 'cvsec_logs_level_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cvsecurity_activity_logs');
        Schema::dropIfExists('cvsecurity_events');
        Schema::dropIfExists('cvsecurity_member_sync_items');
        Schema::dropIfExists('cvsecurity_sync_states');
        Schema::dropIfExists('cvsecurity_pairing_tokens');
        Schema::dropIfExists('cvsecurity_agents');
        Schema::dropIfExists('cvsecurity_connections');
    }
};

