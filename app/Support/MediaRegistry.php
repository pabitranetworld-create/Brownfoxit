<?php

namespace App\Support;

use App\Models\Media;

/** Adds an image that already sits in public/uploads to the media library (once). */
class MediaRegistry
{
    public static function register(?string $path, ?string $alt = null): ?Media
    {
        $path = ltrim((string) $path, '/');
        if ($path === '' || ! str_starts_with($path, 'uploads/') || str_contains($path, '..') || ! preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $path)) {
            return null;
        }
        $file = public_path($path);
        if (! is_file($file)) {
            return null;
        }
        if ($m = Media::where('path', $path)->first()) {
            return $m;
        }
        [$w, $h] = @getimagesize($file) ?: [null, null];

        return Media::create([
            'path' => $path,
            'original_name' => basename($path),
            'mime' => (string) (mime_content_type($file) ?: 'image/'.strtolower(pathinfo($path, PATHINFO_EXTENSION))),
            'size' => (int) filesize($file),
            'width' => $w, 'height' => $h,
            'alt' => $alt ? mb_substr(trim(strip_tags($alt)), 0, 250) : null,
        ]);
    }

    /** All image paths used in a piece of HTML (src="/uploads/…"). */
    public static function fromHtml(?string $html): array
    {
        preg_match_all('~src=["\']/?(uploads/[^"\'?#]+)~i', (string) $html, $m);

        return array_unique($m[1]);
    }
}
