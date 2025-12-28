<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('access_identities', function (Blueprint $table) {
            // Track when fingerprint was actually enrolled on the device
            $table->timestamp('fingerprint_enrolled_at')->nullable()->after('is_active');
            
            // Track when last synced to device
            $table->timestamp('device_synced_at')->nullable()->after('fingerprint_enrolled_at');
            
            // Store original valid_until before disable (for re-enable)
            $table->date('original_valid_until')->nullable()->after('device_synced_at');
            
            // Validity period for device
            $table->date('valid_from')->nullable()->after('original_valid_until');
            $table->date('valid_until')->nullable()->after('valid_from');
            
            // Track when manually disabled
            $table->timestamp('disabled_at')->nullable()->after('valid_until');
            
            // Last sync error message
            $table->string('last_sync_error', 500)->nullable()->after('disabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('access_identities', function (Blueprint $table) {
            $table->dropColumn([
                'fingerprint_enrolled_at',
                'device_synced_at',
                'original_valid_until',
                'valid_from',
                'valid_until',
                'disabled_at',
                'last_sync_error',
            ]);
        });
    }
};

