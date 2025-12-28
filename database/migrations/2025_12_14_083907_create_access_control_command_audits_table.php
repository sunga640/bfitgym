<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_control_command_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->uuid('command_id')->index();
            $table->unsignedBigInteger('agent_id')->nullable()->index();

            $table->enum('status', ['received', 'started', 'done', 'failed'])->index();
            $table->text('message')->nullable();
            $table->json('result')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['command_id', 'status'], 'cmd_audit_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_control_command_audits');
    }
};
