<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_control_agent_devices', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('access_control_agent_id')->index();
            $table->unsignedBigInteger('access_control_device_id')->index();

            $table->timestamps();

            $table->unique(['access_control_agent_id', 'access_control_device_id'], 'agent_device_unique');
            $table->index(['branch_id', 'access_control_agent_id'], 'agent_device_branch_agent_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_control_agent_devices');
    }
};
