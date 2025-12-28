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
        Schema::table('access_control_devices', function (Blueprint $table) {
            // Hikvision connection settings
            $table->unsignedSmallInteger('port')->default(80)->after('ip_address');
            $table->string('username', 100)->nullable()->after('port');
            $table->text('password_encrypted')->nullable()->after('username');

            // Device capabilities & configuration
            $table->enum('device_type', ['entry', 'exit', 'bidirectional'])->default('entry')->after('device_model');
            $table->boolean('supports_face_recognition')->default(true)->after('device_type');
            $table->boolean('supports_fingerprint')->default(true)->after('supports_face_recognition');
            $table->boolean('supports_card')->default(true)->after('supports_fingerprint');

            // Sync & connectivity status
            $table->enum('connection_status', ['online', 'offline', 'unknown'])->default('unknown')->after('status');
            $table->timestamp('last_sync_at')->nullable()->after('connection_status');
            $table->timestamp('last_heartbeat_at')->nullable()->after('last_sync_at');
            $table->text('last_error')->nullable()->after('last_heartbeat_at');

            // Access log sync settings
            $table->boolean('auto_sync_enabled')->default(true)->after('last_error');
            $table->unsignedSmallInteger('sync_interval_minutes')->default(5)->after('auto_sync_enabled');
            $table->timestamp('logs_synced_until')->nullable()->after('sync_interval_minutes');

            // Additional metadata
            $table->string('firmware_version', 50)->nullable()->after('logs_synced_until');
            $table->string('mac_address', 17)->nullable()->after('firmware_version');
            $table->json('capabilities')->nullable()->after('mac_address');
            $table->text('notes')->nullable()->after('capabilities');

            // Index for connection status queries
            $table->index(['branch_id', 'connection_status'], 'branch_connection_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_control_devices', function (Blueprint $table) {
            $table->dropIndex('branch_connection_status_idx');

            $table->dropColumn([
                'port',
                'username',
                'password_encrypted',
                'device_type',
                'supports_face_recognition',
                'supports_fingerprint',
                'supports_card',
                'connection_status',
                'last_sync_at',
                'last_heartbeat_at',
                'last_error',
                'auto_sync_enabled',
                'sync_interval_minutes',
                'logs_synced_until',
                'firmware_version',
                'mac_address',
                'capabilities',
                'notes',
            ]);
        });
    }
};
