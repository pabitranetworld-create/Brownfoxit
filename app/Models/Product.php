<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    public const TYPES = ['digital' => 'Digital (download)', 'physical' => 'Physical (shipped)'];

    public const STATUSES = ['draft' => 'Draft (লুকানো)', 'published' => 'Published (দোকানে দেখা যায়)', 'archived' => 'Archived'];

    protected $fillable = [
        'type', 'status', 'name', 'slug', 'sku', 'product_category_id', 'tags', 'short_description', 'description',
        'image', 'gallery', 'video_url', 'price', 'sale_price', 'sale_starts_at', 'sale_ends_at', 'manage_stock', 'stock',
        'weight', 'variants', 'files', 'features', 'specifications', 'requirements', 'compatibility', 'faqs',
        'documentation_url', 'demo_url', 'version', 'changelog', 'download_limit', 'download_expiry_days',
        'is_featured', 'related_ids', 'seo_title', 'seo_description', 'og_image', 'source_url', 'sort_order', 'published_at',
    ];

    protected $casts = [
        'gallery' => 'array', 'variants' => 'array', 'files' => 'array', 'features' => 'array',
        'specifications' => 'array', 'faqs' => 'array', 'related_ids' => 'array',
        'price' => 'decimal:2', 'sale_price' => 'decimal:2', 'weight' => 'decimal:3',
        'sale_starts_at' => 'datetime', 'sale_ends_at' => 'datetime', 'published_at' => 'datetime',
        'manage_stock' => 'boolean', 'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $p) {
            if ($p->status === 'published' && ! $p->published_at) {
                $p->published_at = now();
            }
            $p->code_normalize();
        });
    }

    /** related_ids may arrive as "1, 2, 3" from the admin form. */
    public function setRelatedIdsAttribute(mixed $value): void
    {
        $ids = is_array($value) ? $value : preg_split('/[^0-9]+/', (string) $value);
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $this->attributes['related_ids'] = $ids ? json_encode($ids) : null;
    }

    protected function code_normalize(): void
    {
        foreach (['variants', 'files'] as $k) {
            $this->{$k} = array_values(array_filter((array) $this->{$k}, fn ($r) => is_array($r) && (filled($r['name'] ?? null) || filled($r['path'] ?? null))));
        }
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    public function link(): string
    {
        return url('/shop/'.$this->slug);
    }

    public function isDigital(): bool
    {
        return $this->type !== 'physical';
    }

    public function hasVariants(): bool
    {
        return count($this->variants ?? []) > 0;
    }

    public function variant(?string $key): ?array
    {
        foreach ($this->variants ?? [] as $v) {
            if (($v['key'] ?? null) === $key) {
                return $v;
            }
        }

        return null;
    }

    protected function saleActive(): bool
    {
        return (! $this->sale_starts_at || $this->sale_starts_at->isPast()) && (! $this->sale_ends_at || $this->sale_ends_at->isFuture());
    }

    /** [regular, sale|null] for the product or one of its variants. */
    public function prices(?string $variantKey = null): array
    {
        $regular = (float) $this->price;
        $sale = $this->sale_price !== null ? (float) $this->sale_price : null;
        if ($variantKey !== null && ($v = $this->variant($variantKey))) {
            $regular = (float) ($v['price'] ?? $regular);
            $sale = isset($v['sale_price']) && $v['sale_price'] !== '' && $v['sale_price'] !== null ? (float) $v['sale_price'] : null;
        }
        if ($sale !== null && ($sale >= $regular || ! $this->saleActive())) {
            $sale = null;
        }

        return [$regular, $sale];
    }

    /** The price a customer pays right now (server-side source of truth). */
    public function currentPrice(?string $variantKey = null): float
    {
        [$regular, $sale] = $this->prices($variantKey);

        return round($sale ?? $regular, 2);
    }

    /** Lowest current price over all variants (for "from …" labels). */
    public function fromPrice(): float
    {
        if (! $this->hasVariants()) {
            return $this->currentPrice();
        }

        return min(array_map(fn ($v) => $this->currentPrice($v['key']), $this->variants));
    }

    /** Stock left, or null when stock is not tracked. */
    public function stockLeft(?string $variantKey = null): ?int
    {
        if ($variantKey !== null && ($v = $this->variant($variantKey)) && isset($v['stock']) && $v['stock'] !== '' && $v['stock'] !== null) {
            return (int) $v['stock'];
        }

        return $this->manage_stock ? (int) $this->stock : null;
    }

    public function inStock(?string $variantKey = null, int $qty = 1): bool
    {
        $left = $this->stockLeft($variantKey);

        return $left === null || $left >= $qty;
    }

    /** Download files for a purchase (files without a variant, plus the chosen variant's files). */
    public function filesFor(?string $variantKey): array
    {
        return array_values(array_filter($this->files ?? [], fn ($f) => ! empty($f['path']) && (empty($f['variant']) || $f['variant'] === $variantKey)));
    }

    public function file(string $key): ?array
    {
        foreach ($this->files ?? [] as $f) {
            if (($f['key'] ?? null) === $key) {
                return $f;
            }
        }

        return null;
    }

    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tags))));
    }
}
