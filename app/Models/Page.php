<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    /** Slugs that cannot be used because a real route lives there. */
    public const RESERVED = ['admin', 'up', '_deploy', 'install', 'vendor', 'assets', 'uploads', 'storage', 'login', 'logout', 'register', 'shop', 'cart', 'checkout', 'account', 'blog', 'portfolio', 'services', 'search', 'sitemap.xml', 'robots.txt'];

    protected $fillable = [
        'title', 'slug', 'status', 'is_template', 'layout', 'draft_layout',
        'seo_title', 'seo_description', 'og_image', 'noindex',
        'show_page_header', 'header_subtitle', 'published_at', 'updated_by',
    ];

    protected $casts = [
        'layout' => 'array',
        'draft_layout' => 'array',
        'is_template' => 'boolean',
        'noindex' => 'boolean',
        'show_page_header' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->latest('id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PUBLISHED)->where('is_template', false);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** Layout being edited: unpublished draft if there is one, else the live layout. */
    public function workingLayout(): array
    {
        return $this->draft_layout ?? $this->layout ?? ['version' => 1, 'sections' => []];
    }

    public function hasUnpublishedChanges(): bool
    {
        return $this->draft_layout !== null && $this->draft_layout != $this->layout;
    }

    public function url(): string
    {
        return url('/'.$this->slug);
    }
}
