<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_integration_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->string('integration_type', 20); // hikvision | zkteco
            $table->string('mode', 20)->default('agent'); // platform | agent
            $table->string('provider', 50); // hikvision_agent | zkbio_platform | zkteco_agent

            $table->boolean('is_enabled')->default(true);
            $table->boolean('sync_enabled')->default(true);
            $table->boolean('agent_fallback_enabled')->default(false);

            // Platform mode configuration (e.g. ZKBio CVAccess/CVSecurity)
            $table->string('platform_base_url', 255)->nullable();
            $table->string('platform_username', 120)->nullable();
            $table->text('platform_password_encrypted')->nullable();
            $table->string('platform_site_code', 120)->nullable();
            $table->string('platform_client_id', 120)->nullable();
            $table->text('platform_client_secret_encrypted')->nullable();

            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('health_status', 20)->default('unknown'); // healthy | degraded | down | unknown
            $table->text('last_health_message')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'integration_type'], 'access_integration_branch_type_unique');
            $table->index(['integration_type', 'provider'], 'access_integration_type_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_integration_configs');
    }
};

