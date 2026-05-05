<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_retentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('ARS');
            $table->string('description');
            $table->date('date');
            $table->date('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_retentions');
    }
};
