<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_usd', 15, 2)->default(0);
            $table->integer('due_day')->nullable();
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fixed_expense_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('month');
            $table->integer('year');
            $table->boolean('paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->decimal('amount_paid', 15, 2)->nullable();
            $table->decimal('amount_paid_usd', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['fixed_expense_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_expense_payments');
        Schema::dropIfExists('fixed_expenses');
    }
};
