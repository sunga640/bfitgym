<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('access_control_device_commands')) {
            return;
        }

        // This is a production repair migration for MySQL/MariaDB installs.
        // In tests we often run sqlite, which does not support the MySQL-specific DDL/DML used below.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Some early installs created an incorrect / truncated table (e.g. bigint id, missing core columns).
        // If so, preserve it as a legacy table and recreate the correct schema expected by the codebase.
        $is_legacy_table =
            ! Schema::hasColumn('access_control_device_commands', 'access_control_device_id')
            || ! Schema::hasColumn('access_control_device_commands', 'type')
            || ! Schema::hasColumn('access_control_device_commands', 'status');

        if ($is_legacy_table) {
            $legacy_table_name = 'access_control_device_commands_legacy_' . now()->format('Ymd_His');
            Schema::rename('access_control_device_commands', $legacy_table_name);

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

            return;
        }

        // Normal fix path: ensure branch_id exists and is populated.
        if (! Schema::hasColumn('access_control_device_commands', 'branch_id')) {
            Schema::table('access_control_device_commands', function (Blueprint $table) {
                // Add nullable first so we can backfill safely.
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->index('branch_id');
            });
        }

        // Backfill from the device's branch_id (branch-safe identity).
        DB::statement('
            UPDATE access_control_device_commands c
            INNER JOIN access_control_devices d
                ON d.id = c.access_control_device_id
            SET c.branch_id = d.branch_id
            WHERE c.branch_id IS NULL
        ');

        // Enforce NOT NULL after backfill (avoid requiring doctrine/dbal).
        DB::statement('
            ALTER TABLE access_control_device_commands
            MODIFY branch_id BIGINT UNSIGNED NOT NULL
        ');

        // Ensure key polling/claiming indexes exist (older installs may miss them).
        $this->ensureIndexExists(
            table: 'access_control_device_commands',
            index_name: 'cmd_claim_idx',
            columns: ['branch_id', 'access_control_device_id', 'status', 'available_at', 'priority', 'created_at'],
        );

        $this->ensureIndexExists(
            table: 'access_control_device_commands',
            index_name: 'cmd_status_available_priority_idx',
            columns: ['status', 'available_at', 'priority', 'created_at'],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('access_control_device_commands')) {
            return;
        }

        // We intentionally do NOT attempt to revert a legacy-table rebuild.
        // This migration is a one-way repair for previously incorrect installs.
    }

    private function ensureIndexExists(string $table, string $index_name, array $columns): void
    {
        if ($this->indexExists($table, $index_name)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($columns, $index_name) {
            $table->index($columns, $index_name);
        });
    }

    private function dropIndexIfExists(string $table, string $index_name): void
    {
        if (! $this->indexExists($table, $index_name)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($index_name) {
            $table->dropIndex($index_name);
        });
    }

    private function indexExists(string $table, string $index_name): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $database_name = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(1) AS count
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?',
            [$database_name, $table, $index_name]
        );

        return (int) ($result->count ?? 0) > 0;
    }
};

