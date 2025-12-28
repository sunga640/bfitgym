<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('access_control_agent_enrollments')) {
            return;
        }

        if (!Schema::hasColumn('access_control_agent_enrollments', 'code_hash')) {
            return;
        }

        // Check if unique index already exists (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            $database_name = DB::getDatabaseName();
            $result = DB::selectOne(
                'SELECT COUNT(1) AS count
                 FROM information_schema.statistics
                 WHERE table_schema = ?
                   AND table_name = ?
                   AND index_name = ?',
                [$database_name, 'access_control_agent_enrollments', 'enrollment_code_hash_unique']
            );

            if ((int) ($result->count ?? 0) > 0) {
                return;
            }
        }

        Schema::table('access_control_agent_enrollments', function (Blueprint $table) {
            // Add unique index on code_hash (nullable columns can have multiple NULLs in unique index)
            $table->unique('code_hash', 'enrollment_code_hash_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('access_control_agent_enrollments')) {
            return;
        }

        Schema::table('access_control_agent_enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollment_code_hash_unique');
        });
    }
};
