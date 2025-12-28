<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'access_control_agent_enrollments';

        // Make migration idempotent - check if columns already exist
        if (!Schema::hasColumn($tableName, 'status')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->enum('status', ['active', 'used', 'expired', 'revoked'])
                    ->default('active')
                    ->after('code')
                    ->index();
            });
        }

        if (!Schema::hasColumn($tableName, 'label')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('label', 255)->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn($tableName, 'code_hash')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('code_hash', 64)->nullable()->after('code');
            });
        }

        if (!Schema::hasColumn($tableName, 'access_control_agent_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('access_control_agent_id')->nullable()->after('branch_id')->index();
            });
        }

        // Add composite index if it doesn't exist (ignore if already there)
        try {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['branch_id', 'status'], 'enrollments_branch_status_idx');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index may already exist - ignore
        }

        // Create pivot table for enrollment device assignments if not exists
        if (!Schema::hasTable('access_control_enrollment_devices')) {
            Schema::create('access_control_enrollment_devices', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->bigIncrements('id');
                $table->unsignedBigInteger('access_control_agent_enrollment_id');
                $table->unsignedBigInteger('access_control_device_id');

                $table->timestamps();

                // Use shorter explicit index names to avoid MySQL 64-char limit
                $table->index('access_control_agent_enrollment_id', 'enroll_dev_enrollment_idx');
                $table->index('access_control_device_id', 'enroll_dev_device_idx');

                $table->unique(
                    ['access_control_agent_enrollment_id', 'access_control_device_id'],
                    'enrollment_device_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('access_control_enrollment_devices');

        Schema::table('access_control_agent_enrollments', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'status']);
            $table->dropColumn(['status', 'label', 'code_hash', 'access_control_agent_id']);
        });
    }
};
