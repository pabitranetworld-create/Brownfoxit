<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'subject_type', 'subject_key', 'title', 'description', 'canonical', 'noindex', 'nofollow', 'robots_extra',
        'og_title', 'og_description', 'og_image', 'og_image_alt', 'twitter_card', 'focus_keyword', 'secondary_keywords',
        'schema_override', 'breadcrumb_label', 'hide_breadcrumbs', 'notes',
    ];

    protected $casts = ['noindex' => 'boolean', 'nofollow' => 'boolean', 'hide_breadcrumbs' => 'boolean'];

    public static function for(string $type, string|int $key): ?self
    {
        static $cache = [];

        return $cache[$type.':'.$key] ??= rescue(fn () => static::where('subject_type', $type)->where('subject_key', (string) $key)->first(), null, false);
    }
}
