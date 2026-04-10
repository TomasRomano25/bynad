<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('url');
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('target')->default('_self');
            $table->timestamps();
        });

        DB::table('menu_items')->insert([
            ['label' => 'Inicio',     'url' => '/',          'position' => 1, 'is_active' => true, 'target' => '_self', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Funciones',  'url' => '/#features', 'position' => 2, 'is_active' => true, 'target' => '_self', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Blog',       'url' => '/blog',      'position' => 3, 'is_active' => true, 'target' => '_self', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
