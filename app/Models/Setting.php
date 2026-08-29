<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Fetch a setting value (JSON-decoded), with a sensible default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = Cache::rememberForever("setting:{$key}", fn () => static::query()->where('key', $key)->first());

        if (! $row) {
            return $default;
        }

        $decoded = json_decode((string) $row->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
    }

    /**
     * Create or update a setting, JSON-encoding structured values.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) ? $value : json_encode($value), 'group' => $group],
        );

        Cache::forget("setting:{$key}");
    }
}
