<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_control_agents', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id')->index();

            $table->uuid('uuid')->unique();
            $table->string('name', 150);

            $table->enum('os', ['windows', 'macos', 'linux'])->default('windows');
            $table->string('app_version', 50)->nullable();

            $table->enum('status', ['active', 'revoked'])->default('active')->index();

            // Store SHA-256 of the token (NOT plaintext)
            $table->string('secret_hash', 64);

            $table->timestamp('last_seen_at')->nullable()->index();
            $table->string('last_ip', 45)->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_control_agents');
    }
};
