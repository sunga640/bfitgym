<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index_name): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // PRAGMA returns: seq, name, unique, origin, partial
            $rows = DB::select("PRAGMA index_list('{$table}')");
            foreach ($rows as $row) {
                $name = is_object($row) ? ($row->name ?? null) : ($row['name'] ?? null);
                if ($name === $index_name) {
                    return true;
                }
            }
            return false;
        }

        if ($driver !== 'mysql') {
            return false;
        }

        $result = DB::selectOne(
            'SELECT COUNT(1) AS c
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND index_name = ?',
            [$table, $index_name],
        );

        return (int) ($result->c ?? 0) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('access_control_agent_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('access_control_agent_devices', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->after('id')->index();
            }

            if (!Schema::hasColumn('access_control_agent_devices', 'access_control_agent_id')) {
                $table->unsignedBigInteger('access_control_agent_id')->after('branch_id')->index();
            }

            if (!Schema::hasColumn('access_control_agent_devices', 'access_control_device_id')) {
                $table->unsignedBigInteger('access_control_device_id')->after('access_control_agent_id')->index();
            }
        });

        // Create explicit indexes/constraints (guarded so re-runs are safe).
        Schema::table('access_control_agent_devices', function (Blueprint $table) {
            if (
                Schema::hasColumn('access_control_agent_devices', 'access_control_agent_id')
                && Schema::hasColumn('access_control_agent_devices', 'access_control_device_id')
                && !$this->indexExists('access_control_agent_devices', 'agent_device_unique')
            ) {
                $table->unique(['access_control_agent_id', 'access_control_device_id'], 'agent_device_unique');
            }

            if (
                Schema::hasColumn('access_control_agent_devices', 'branch_id')
                && Schema::hasColumn('access_control_agent_devices', 'access_control_agent_id')
                && !$this->indexExists('access_control_agent_devices', 'agent_device_branch_agent_idx')
            ) {
                $table->index(['branch_id', 'access_control_agent_id'], 'agent_device_branch_agent_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_control_agent_devices', function (Blueprint $table) {
            if ($this->indexExists('access_control_agent_devices', 'agent_device_branch_agent_idx')) {
                $table->dropIndex('agent_device_branch_agent_idx');
            }

            if ($this->indexExists('access_control_agent_devices', 'agent_device_unique')) {
                $table->dropUnique('agent_device_unique');
            }

            if (Schema::hasColumn('access_control_agent_devices', 'access_control_device_id')) {
                $table->dropColumn('access_control_device_id');
            }

            if (Schema::hasColumn('access_control_agent_devices', 'access_control_agent_id')) {
                $table->dropColumn('access_control_agent_id');
            }

            if (Schema::hasColumn('access_control_agent_devices', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });
    }
};
