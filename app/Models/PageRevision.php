<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageRevision extends Model
{
    public const UPDATED_AT = null;

    /** Keep this many versions per page. */
    public const KEEP = 30;

    protected $fillable = ['page_id', 'title', 'layout', 'note', 'user_id'];

    protected $casts = ['layout' => 'array', 'created_at' => 'datetime'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
