<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_insurances', function (Blueprint $table) {
            $table->string('policy_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('member_insurances', function (Blueprint $table) {
            $table->string('policy_number')->nullable(false)->change();
        });
    }
};
