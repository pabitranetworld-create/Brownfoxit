<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Saves admin uploads to public/uploads/YYYY/MM/ (same layout as the
 * images copied from WordPress), so no `storage:link` is needed on cPanel.
 * Auto-deploy never touches public/uploads, so uploads are safe.
 */
class ImageUploader
{
    public const MAX_KB = 5120;

    /** SVG is not allowed: it can carry scripts. */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'ico'];

    public function problem(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return 'ফাইল আপলোড হয়নি ('.$file->getErrorMessage().')';
        }
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, self::EXTENSIONS, true)) {
            return 'শুধু JPG, PNG, WEBP, AVIF, GIF বা ICO ছবি দেওয়া যাবে।';
        }
        if ($file->getSize() > self::MAX_KB * 1024) {
            return 'ছবি সর্বোচ্চ ৫ MB হতে পারে।';
        }
        $mime = (string) $file->getMimeType();
        if (! str_starts_with($mime, 'image/') && $mime !== 'application/octet-stream') {
            return 'এটা ছবির ফাইল নয়।';
        }

        return null;
    }

    /**
     * @return string path relative to public/, e.g. "uploads/2026/11/logo-a1b2c3.png"
     */
    public function store(UploadedFile $file): string
    {
        $dir = 'uploads/'.date('Y/m');
        $ext = strtolower($file->getClientOriginalExtension());
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';
        $name = Str::limit($name, 60, '').'-'.Str::lower(Str::random(6)).'.'.$ext;

        $original = $file->getClientOriginalName();
        $mime = (string) $file->getMimeType();
        $size = (int) $file->getSize();
        $file->move(public_path($dir), $name);
        $path = $dir.'/'.$name;

        // Every upload also appears in the Media library.
        [$w, $h] = @getimagesize(public_path($path)) ?: [null, null];
        \App\Models\Media::create([
            'path' => $path, 'original_name' => $original, 'mime' => $mime, 'size' => $size,
            'width' => $w, 'height' => $h, 'user_id' => auth()->id(),
            'alt' => Str::of(pathinfo($original, PATHINFO_FILENAME))->replace(['-', '_'], ' ')->squish()->title()->limit(120, '')->toString(),
        ]);

        return $path;
    }
}
