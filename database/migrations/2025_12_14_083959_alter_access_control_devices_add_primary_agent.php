<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_control_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('access_control_agent_id')->nullable()->after('branch_id');
            $table->index(['access_control_agent_id', 'branch_id'], 'device_primary_agent_idx');
        });
    }

    public function down(): void
    {
        Schema::table('access_control_devices', function (Blueprint $table) {
            $table->dropIndex('device_primary_agent_idx');
            $table->dropColumn('access_control_agent_id');
        });
    }
};
