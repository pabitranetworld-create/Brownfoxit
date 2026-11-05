<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Media extends Model
{
    protected $fillable = ['path', 'original_name', 'mime', 'size', 'width', 'height', 'alt', 'caption', 'user_id'];

    protected $casts = ['size' => 'integer', 'width' => 'integer', 'height' => 'integer'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return media($this->path);
    }

    public static function altFor(?string $path): ?string
    {
        return $path ? static::where('path', $path)->value('alt') : null;
    }

    protected static function booted(): void
    {
        static::deleted(function (Media $m) {
            // Only delete files we manage (uploads), never anything outside public/uploads.
            if (str_starts_with($m->path, 'uploads/') && ! str_contains($m->path, '..')) {
                File::delete(public_path($m->path));
            }
        });
    }
}
