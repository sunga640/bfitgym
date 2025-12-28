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
        Schema::create('branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('currency', 3)->default('TZS');
            $table->string('timezone', 50)->nullable();
            $table->boolean('module_pos_enabled')->default(true);
            $table->boolean('module_classes_enabled')->default(true);
            $table->boolean('module_insurance_enabled')->default(true);
            $table->boolean('module_access_control_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_settings');
    }
};

