<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['banco', 'billetera_virtual', 'efectivo', 'otro']);
            $table->string('institution')->nullable();
            $table->string('currency', 10)->default('ARS');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('balance_usd', 15, 2)->default(0);
            $table->string('color', 7)->default('#6366f1');
            $table->string('icon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
