<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Small helpers for the marketing site / CMS: resolve uploaded-image URLs and
 * read section visibility flags, so views stay tidy.
 */
class Cms
{
    /**
     * Public URL for an image stored on the public disk, or null.
     * Accepts either a raw path (site/logo.png) or a settings key that holds one.
     */
    public static function image(?string $pathOrKey, bool $isSettingKey = false): ?string
    {
        $path = $isSettingKey ? Setting::get($pathOrKey) : $pathOrKey;

        if (! $path) {
            return null;
        }

        // Already an absolute URL (e.g. an external image) — pass through.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Section visibility flag (defaults to visible when never set).
     */
    public static function shows(string $key): bool
    {
        return (bool) Setting::get($key, true);
    }
}
