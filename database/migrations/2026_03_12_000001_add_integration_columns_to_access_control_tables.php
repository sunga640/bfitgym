<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateAccessControlDevices();
        $this->updateAccessControlAgents();
        $this->updateAccessIdentities();
        $this->updateAgentEnrollments();
        $this->updateAccessLogs();
        $this->updateDeviceCommands();
        $this->updateInboxEvents();
    }

    public function down(): void
    {
        if (Schema::hasTable('access_log_inbox_events')) {
            Schema::table('access_log_inbox_events', function (Blueprint $table) {
                $table->dropIndex('access_log_inbox_integration_provider_idx');
                $table->dropColumn(['integration_type', 'provider']);
            });
        }

        Schema::table('access_control_device_commands', function (Blueprint $table) {
            $table->dropIndex('access_cmd_integration_provider_idx');
            $table->dropColumn(['integration_type', 'provider']);
        });

        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropIndex('access_logs_integration_provider_idx');
            $table->dropColumn(['integration_type', 'provider']);
        });

        Schema::table('access_control_agent_enrollments', function (Blueprint $table) {
            $table->dropIndex('access_enrollments_integration_provider_idx');
            $table->dropColumn(['integration_type', 'provider']);
        });

        Schema::table('access_identities', function (Blueprint $table) {
            $table->dropUnique('branch_integration_device_user_unique');
            $table->unique(['branch_id', 'device_user_id'], 'branch_device_user_unique');
            $table->dropIndex('access_identities_integration_provider_idx');
            $table->dropColumn(['integration_type', 'provider']);
        });

        Schema::table('access_control_agents', function (Blueprint $table) {
            $table->dropIndex('access_agents_default_provider_idx');
            $table->dropColumn(['supported_providers', 'default_provider']);
        });

        Schema::table('access_control_devices', function (Blueprint $table) {
            $table->dropIndex('access_devices_integration_provider_idx');
            $table->dropIndex('access_devices_branch_integration_idx');
            $table->dropColumn(['integration_type', 'provider']);
        });
    }

    private function updateAccessControlDevices(): void
    {
        Schema::table('access_control_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('access_control_devices', 'integration_type')) {
                $table->string('integration_type', 20)->default('hikvision')->after('branch_id');
            }

            if (!Schema::hasColumn('access_control_devices', 'provider')) {
                $table->string('provider', 50)->default('hikvision_agent')->after('integration_type');
            }
        });

        Schema::table('access_control_devices', function (Blueprint $table) {
            $table->index(['integration_type', 'provider'], 'access_devices_integration_provider_idx');
            $table->index(['branch_id', 'integration_type'], 'access_devices_branch_integration_idx');
        });

        DB::table('access_control_devices')
            ->whereNull('integration_type')
            ->update(['integration_type' => 'hikvision']);

        DB::table('access_control_devices')
            ->whereNull('provider')
            ->update(['provider' => 'hikvision_agent']);
    }

    private function updateAccessControlAgents(): void
    {
        Schema::table('access_control_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('access_control_agents', 'supported_providers')) {
                $table->json('supported_providers')->nullable()->after('status');
            }

            if (!Schema::hasColumn('access_control_agents', 'default_provider')) {
                $table->string('default_provider', 50)->nullable()->after('supported_providers');
            }
        });

        Schema::table('access_control_agents', function (Blueprint $table) {
            $table->index('default_provider', 'access_agents_default_provider_idx');
        });

        DB::table('access_control_agents')
            ->whereNull('supported_providers')
            ->update(['supported_providers' => json_encode(['hikvision_agent'])]);

        DB::table('access_control_agents')
            ->whereNull('default_provider')
            ->update(['default_provider' => 'hikvision_agent']);
    }

    private function updateAccessIdentities(): void
    {
        Schema::table('access_identities', function (Blueprint $table) {
            if (!Schema::hasColumn('access_identities', 'integration_type')) {
                $table->string('integration_type', 20)->default('hikvision')->after('branch_id');
            }

            if (!Schema::hasColumn('access_identities', 'provider')) {
                $table->string('provider', 50)->default('hikvision_agent')->after('integration_type');
            }
        });

        DB::table('access_identities')
            ->whereNull('integration_type')
            ->update(['integration_type' => 'hikvision']);

        DB::table('access_identities')
            ->whereNull('provider')
            ->update(['provider' => 'hikvision_agent']);

        Schema::table('access_identities', function (Blueprint $table) {
            try {
                $table->dropUnique('branch_device_user_unique');
            } catch (\Throwable) {
                // Ignore if the legacy index does not exist in this environment.
            }

            $table->unique(
                ['branch_id', 'integration_type', 'device_user_id'],
                'branch_integration_device_user_unique'
            );
            $table->index(['integration_type', 'provider'], 'access_identities_integration_provider_idx');
        });
    }

    private function updateAgentEnrollments(): void
    {
        Schema::table('access_control_agent_enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('access_control_agent_enrollments', 'integration_type')) {
                $table->string('integration_type', 20)->default('hikvision')->after('branch_id');
            }

            if (!Schema::hasColumn('access_control_agent_enrollments', 'provider')) {
                $table->string('provider', 50)->default('hikvision_agent')->after('integration_type');
            }
        });

        Schema::table('access_control_agent_enrollments', function (Blueprint $table) {
            $table->index(['integration_type', 'provider'], 'access_enrollments_integration_provider_idx');
        });

        DB::table('access_control_agent_enrollments')
            ->whereNull('integration_type')
            ->update(['integration_type' => 'hikvision']);

        DB::table('access_control_agent_enrollments')
            ->whereNull('provider')
            ->update(['provider' => 'hikvision_agent']);
    }

    private function updateAccessLogs(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('access_logs', 'integration_type')) {
                $table->string('integration_type', 20)->default('hikvision')->after('branch_id');
            }

            if (!Schema::hasColumn('access_logs', 'provider')) {
                $table->string('provider', 50)->default('hikvision_agent')->after('integration_type');
            }
        });

        Schema::table('access_logs', function (Blueprint $table) {
            $table->index(['integration_type', 'provider'], 'access_logs_integration_provider_idx');
        });

        DB::table('access_logs')
            ->whereNull('integration_type')
            ->update(['integration_type' => 'hikvision']);

        DB::table('access_logs')
            ->whereNull('provider')
            ->update(['provider' => 'hikvision_agent']);
    }

    private function updateDeviceCommands(): void
    {
        Schema::table('access_control_device_commands', function (Blueprint $table) {
            if (!Schema::hasColumn('access_control_device_commands', 'integration_type')) {
                $table->string('integration_type', 20)->default('hikvision')->after('branch_id');
            }

            if (!Schema::hasColumn('access_control_device_commands', 'provider')) {
                $table->string('provider', 50)->default('hikvision_agent')->after('integration_type');
            }
        });

        Schema::table('access_control_device_commands', function (Blueprint $table) {
            $table->index(['integration_type', 'provider'], 'access_cmd_integration_provider_idx');
        });

        DB::table('access_control_device_commands')
            ->whereNull('integration_type')
            ->update(['integration_type' => 'hikvision']);

        DB::table('access_control_device_commands')
            ->whereNull('provider')
            ->update(['provider' => 'hikvision_agent']);
    }

    private function updateInboxEvents(): void
    {
        if (!Schema::hasTable('access_log_inbox_events')) {
            return;
        }

        Schema::table('access_log_inbox_events', function (Blueprint $table) {
            if (!Schema::hasColumn('access_log_inbox_events', 'integration_type')) {
                $table->string('integration_type', 20)->default('hikvision')->after('branch_id');
            }

            if (!Schema::hasColumn('access_log_inbox_events', 'provider')) {
                $table->string('provider', 50)->default('hikvision_agent')->after('integration_type');
            }
        });

        Schema::table('access_log_inbox_events', function (Blueprint $table) {
            $table->index(['integration_type', 'provider'], 'access_log_inbox_integration_provider_idx');
        });

        DB::table('access_log_inbox_events')
            ->whereNull('integration_type')
            ->update(['integration_type' => 'hikvision']);

        DB::table('access_log_inbox_events')
            ->whereNull('provider')
            ->update(['provider' => 'hikvision_agent']);
    }
};

