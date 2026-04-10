<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'usd_rate', 'value' => '1200', 'group' => 'currency', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_host', 'value' => '', 'group' => 'smtp', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_port', 'value' => '587', 'group' => 'smtp', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_user', 'value' => '', 'group' => 'smtp', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_password', 'value' => '', 'group' => 'smtp', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_from', 'value' => '', 'group' => 'smtp', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'backup_enabled', 'value' => '1', 'group' => 'backup', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'backup_frequency', 'value' => 'daily', 'group' => 'backup', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'backup_path', 'value' => '/home/karen/projects/untitled2/storage/backups', 'group' => 'backup', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
