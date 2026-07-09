<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * USD rate used across the app.
     *
     * The rate is configured per family. When there is an authenticated user we
     * return their family's rate; otherwise (console, jobs, guests) we fall back
     * to the global default stored in settings, which also seeds new families.
     */
    public static function getUsdRate(): float
    {
        $user = auth()->user();
        if ($user) {
            return $user->usdRate();
        }

        return (float) static::get('usd_rate', 1200);
    }

    /**
     * The global default rate (used to seed new families and as fallback).
     */
    public static function getDefaultUsdRate(): float
    {
        return (float) static::get('usd_rate', 1200);
    }
}
