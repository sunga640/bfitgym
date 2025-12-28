<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->string('device_event_uid', 150)->nullable()->after('raw_payload');

            // Prevent duplicates when agent retries (MySQL allows multiple NULLs in UNIQUE)
            $table->unique(['access_control_device_id', 'device_event_uid'], 'device_event_uid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropUnique('device_event_uid_unique');
            $table->dropColumn('device_event_uid');
        });
    }
};
