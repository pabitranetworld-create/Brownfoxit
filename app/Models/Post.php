<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'post_category_id', 'user_id', 'title', 'slug', 'excerpt', 'body',
        'image', 'status', 'published_at', 'seo_title', 'seo_description', 'tags', 'source_url',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function link(): string
    {
        return url('/blog/'.$this->slug);
    }

    public function readingMinutes(): int
    {
        return max(1, (int) round(str_word_count(strip_tags((string) $this->body)) / 220));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
