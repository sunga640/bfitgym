<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zkteco_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50)->default('zkbio_api');
            $table->string('status', 30)->default('disconnected');
            $table->string('base_url', 255);
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('username', 120)->nullable();
            $table->text('password')->nullable();
            $table->text('api_key')->nullable();
            $table->boolean('ssl_enabled')->default(true);
            $table->boolean('allow_self_signed')->default(false);
            $table->unsignedSmallInteger('timeout_seconds')->default(10);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_test_success_at')->nullable();
            $table->timestamp('last_personnel_sync_at')->nullable();
            $table->timestamp('last_event_sync_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('branch_id', 'zkteco_connections_branch_unique');
            $table->index(['status', 'provider'], 'zkteco_connections_status_provider_idx');
        });

        Schema::create('zkteco_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zkteco_connection_id')->constrained('zkteco_connections')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('remote_device_id', 120);
            $table->string('remote_name', 150)->nullable();
            $table->string('remote_type', 50)->nullable();
            $table->string('remote_status', 40)->nullable();
            $table->boolean('is_online')->default(false);
            $table->boolean('is_assignable')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('remote_payload')->nullable();
            $table->timestamps();

            $table->unique(['zkteco_connection_id', 'remote_device_id'], 'zkteco_devices_connection_remote_unique');
            $table->index(['branch_id', 'is_online'], 'zkteco_devices_branch_online_idx');
        });

        Schema::create('zkteco_branch_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zkteco_connection_id')->constrained('zkteco_connections')->cascadeOnDelete();
            $table->foreignId('zkteco_device_id')->constrained('zkteco_devices')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'zkteco_device_id'], 'zkteco_branch_device_unique');
            $table->index(['branch_id', 'is_active'], 'zkteco_branch_mappings_branch_active_idx');
        });

        Schema::create('zkteco_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zkteco_connection_id')->constrained('zkteco_connections')->cascadeOnDelete();
            $table->string('run_type', 40);
            $table->string('status', 20)->default('running');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('records_total')->default(0);
            $table->unsignedInteger('records_success')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('context')->nullable();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'run_type', 'started_at'], 'zkteco_sync_runs_branch_type_started_idx');
            $table->index(['zkteco_connection_id', 'status'], 'zkteco_sync_runs_connection_status_idx');
        });

        Schema::create('zkteco_access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zkteco_connection_id')->constrained('zkteco_connections')->cascadeOnDelete();
            $table->foreignId('zkteco_device_id')->nullable()->constrained('zkteco_devices')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('remote_event_id', 120)->nullable();
            $table->string('event_fingerprint', 64);
            $table->string('remote_personnel_id', 120)->nullable();
            $table->string('direction', 20)->default('unknown');
            $table->string('event_status', 50)->nullable();
            $table->timestamp('occurred_at');
            $table->boolean('matched_member')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['zkteco_connection_id', 'remote_event_id'], 'zkteco_events_conn_remote_unique');
            $table->unique(['zkteco_connection_id', 'event_fingerprint'], 'zkteco_events_conn_fingerprint_unique');
            $table->index(['branch_id', 'occurred_at'], 'zkteco_events_branch_occurred_idx');
            $table->index(['member_id', 'occurred_at'], 'zkteco_events_member_occurred_idx');
        });

        Schema::create('zkteco_member_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zkteco_connection_id')->constrained('zkteco_connections')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('remote_personnel_id', 120)->nullable();
            $table->string('remote_personnel_code', 120)->nullable();
            $table->string('biometric_status', 30)->default('unknown');
            $table->boolean('access_active')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'member_id'], 'zkteco_member_maps_branch_member_unique');
            $table->unique(['zkteco_connection_id', 'remote_personnel_id'], 'zkteco_member_maps_conn_remote_unique');
            $table->index(['branch_id', 'access_active'], 'zkteco_member_maps_branch_active_idx');
        });

        Schema::create('zkteco_member_device_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zkteco_member_map_id')->constrained('zkteco_member_maps')->cascadeOnDelete();
            $table->foreignId('zkteco_device_id')->constrained('zkteco_devices')->cascadeOnDelete();
            $table->boolean('access_granted')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['zkteco_member_map_id', 'zkteco_device_id'], 'zkteco_member_device_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_member_device_access');
        Schema::dropIfExists('zkteco_member_maps');
        Schema::dropIfExists('zkteco_access_events');
        Schema::dropIfExists('zkteco_sync_runs');
        Schema::dropIfExists('zkteco_branch_mappings');
        Schema::dropIfExists('zkteco_devices');
        Schema::dropIfExists('zkteco_connections');
    }
};

