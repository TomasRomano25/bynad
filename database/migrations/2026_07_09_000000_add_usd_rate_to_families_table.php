<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->decimal('usd_rate', 15, 2)->default(1200)->after('name');
        });

        // Seed every existing family with the current global usd_rate so nothing
        // changes visually right after the migration.
        $globalRate = DB::table('settings')->where('key', 'usd_rate')->value('value');
        if ($globalRate !== null && $globalRate !== '') {
            DB::table('families')->update(['usd_rate' => $globalRate]);
        }
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn('usd_rate');
        });
    }
};
