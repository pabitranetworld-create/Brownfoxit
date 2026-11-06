<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use Sortable;

    protected $fillable = [
        'title', 'slug', 'category', 'summary', 'tags', 'image', 'gallery', 'url', 'video_url', 'description',
        'client', 'year', 'services', 'challenge', 'solution', 'result', 'status',
        'seo_title', 'seo_description', 'source_url', 'is_featured', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'gallery' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /** Visible on the public portfolio (status published and active). */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_active', true)->where(fn ($q) => $q->where('status', 'published')->orWhereNull('status'));
    }

    public function link(): string
    {
        return url('/portfolio/'.$this->slug);
    }

    public function categories(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->category))));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
