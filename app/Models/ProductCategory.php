<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'image', 'sort_order'];

    protected static function booted(): void
    {
        // A category can never sit under itself or under one of its own sub-categories.
        static::saving(function (self $c) {
            if (! $c->parent_id || ! $c->exists) {
                return;
            }
            if ((int) $c->parent_id === (int) $c->id || in_array((int) $c->parent_id, $c->descendantIds(), true)) {
                $c->parent_id = null;
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /** IDs of every sub-category below this one (any depth), without this one. */
    public function descendantIds(): array
    {
        $all = static::query()->pluck('parent_id', 'id')->all();
        $out = [];
        $queue = [(int) $this->id];
        while ($queue) {
            $current = array_shift($queue);
            foreach ($all as $id => $parent) {
                if ((int) $parent === $current && ! in_array((int) $id, $out, true) && (int) $id !== (int) $this->id) {
                    $out[] = (int) $id;
                    $queue[] = (int) $id;
                }
            }
        }

        return $out;
    }

    /** Parents from the top down, without this one (read from one small query, no lazy loading). */
    public function ancestors(): array
    {
        $all = static::query()->get(['id', 'parent_id', 'name', 'slug'])->keyBy('id');
        $list = [];
        $seen = [$this->id];
        $pid = $this->parent_id;
        while ($pid && isset($all[$pid]) && ! in_array($pid, $seen, true)) {
            array_unshift($list, $all[$pid]);
            $seen[] = $pid;
            $pid = $all[$pid]->parent_id;
        }

        return $list;
    }

    /** "Parent › Child" — used in admin dropdowns. */
    public function getPathNameAttribute(): string
    {
        return implode(' › ', array_merge(array_map(fn ($a) => $a->name, $this->ancestors()), [$this->name]));
    }

    public function link(): string
    {
        return url('/shop/category/'.$this->slug);
    }
}
