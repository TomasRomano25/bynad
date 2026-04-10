<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['inmueble', 'vehiculo', 'inversion', 'crypto', 'ahorro', 'otro']);
            $table->decimal('value_ars', 15, 2)->default(0);
            $table->decimal('value_usd', 15, 2)->default(0);
            $table->string('currency_input', 10)->default('ARS');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
