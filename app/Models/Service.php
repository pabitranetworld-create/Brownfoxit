<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use Sortable;

    /** Colour names used by the icon boxes in style.css (.ic-blue, .ic-orange …). */
    public const ICON_COLORS = ['blue', 'orange', 'green', 'purple'];

    protected $fillable = [
        'title', 'slug', 'icon', 'icon_color', 'short_description',
        'description', 'image', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
