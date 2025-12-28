<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_control_agent_enrollments', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id')->index();

            $table->string('code', 64)->unique(); // random enrollment code
            $table->timestamp('expires_at')->index();

            $table->unsignedBigInteger('created_by')->nullable()->index(); // users.id
            $table->timestamp('used_at')->nullable()->index();
            $table->unsignedBigInteger('used_by_agent_id')->nullable()->index();

            $table->timestamps();

            $table->index(['branch_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_control_agent_enrollments');
    }
};
