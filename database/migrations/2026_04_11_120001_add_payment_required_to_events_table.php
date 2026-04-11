<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('payment_required')->default(false)->after('end_datetime');
        });

        // Backfill from legacy "paid" type events.
        DB::table('events')
            ->where('type', 'paid')
            ->update(['payment_required' => true]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('payment_required');
        });
    }
};

