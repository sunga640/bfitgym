<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('access_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->enum('subject_type', ['member','staff']);
            $table->unsignedBigInteger('subject_id');
            $table->string('device_user_id', 100);
            $table->string('card_number', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'device_user_id'], 'branch_device_user_unique');
            $table->index(['subject_type', 'subject_id'], 'access_subject_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_identities');
    }
};
