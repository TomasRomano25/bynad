<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('currency', 10)->default('ARS')->after('amount');
        });

        Schema::table('variable_expenses', function (Blueprint $table) {
            $table->string('currency', 10)->default('ARS')->after('amount');
        });

        Schema::table('fixed_expenses', function (Blueprint $table) {
            $table->string('currency', 10)->default('ARS')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('incomes', fn($t) => $t->dropColumn('currency'));
        Schema::table('variable_expenses', fn($t) => $t->dropColumn('currency'));
        Schema::table('fixed_expenses', fn($t) => $t->dropColumn('currency'));
    }
};
