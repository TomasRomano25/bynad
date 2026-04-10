<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('brand', ['visa', 'mastercard', 'amex', 'naranja', 'cabal', 'otro']);
            $table->string('last_four', 4)->nullable();
            $table->string('bank')->nullable();
            $table->decimal('limit_amount', 15, 2)->default(0);
            $table->decimal('limit_amount_usd', 15, 2)->default(0);
            $table->integer('closing_day')->nullable();
            $table->integer('due_day')->nullable();
            $table->string('color', 7)->default('#8b5cf6');
            $table->timestamps();
        });

        Schema::create('credit_card_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_usd', 15, 2)->default(0);
            $table->integer('total_installments')->default(1);
            $table->integer('current_installment')->default(1);
            $table->decimal('installment_amount', 15, 2)->default(0);
            $table->date('purchase_date');
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_expenses');
        Schema::dropIfExists('credit_cards');
    }
};
