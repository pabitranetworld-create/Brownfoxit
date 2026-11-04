<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Key/value store for page content that is not a list of records:
 * hero text, about text, skills, counters, steps, contact info, menus …
 * Each row's `value` is JSON, so a key can hold a string or a whole array.
 */
class Setting extends Model
{
    public const CACHE_KEY = 'site_settings';

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * All settings as [key => value], cached until a setting changes.
     */
    public static function allValues(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                return static::query()->get()
                    ->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])
                    ->all();
            });
        } catch (Throwable) {
            // Database not ready yet (e.g. before `php artisan migrate`).
            return [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(static::allValues(), $key, $default);
    }

    public static function put(string $key, mixed $value): self
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
