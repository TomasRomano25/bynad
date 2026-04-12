<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('credit_card_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->integer('month');
            $table->integer('year');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('ARS');
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            $table->unique(['credit_card_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_payments');
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Account::class);
            $table->dropColumn('account_id');
        });
    }
};
