<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use Sortable;

    /** Colour names used by the icon boxes in style.css (.ic-blue, .ic-orange …). */
    public const ICON_COLORS = ['blue', 'orange', 'green', 'purple'];

    /** Service groups (the first four are the main ones in navigation). */
    /** The five main services of Brown Fox IT, in display order. */
    public const GROUPS = [
        'ai' => 'AI Automation',
        'n8n' => 'n8n Automation',
        'software' => 'Software & Development',
        'creative' => 'Graphic & Video',
        'marketing' => 'Digital Marketing',
    ];

    protected $fillable = [
        'title', 'slug', 'icon', 'icon_color', 'group', 'url', 'short_description',
        'description', 'image', 'sort_order', 'is_active', 'show_on_home', 'show_in_catalog',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_home' => 'boolean',
        'show_in_catalog' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
