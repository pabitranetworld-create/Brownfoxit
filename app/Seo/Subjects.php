<?php

namespace App\Seo;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Every kind of public page the site has, and how to find, link and judge each one.
 * Used by the sitemap, the SEO dashboard / editor and the page head.
 */
class Subjects
{
    /** type => [label, model class or null for fixed routes] */
    public const TYPES = [
        'route' => ['মূল পেজ', null],
        'page' => ['Page (builder)', Page::class],
        'post' => ['Blog post', Post::class],
        'post_category' => ['Blog category', PostCategory::class],
        'project' => ['Portfolio', Project::class],
        'product' => ['Product', Product::class],
        'product_category' => ['Product category', ProductCategory::class],
    ];

    /** Fixed pages that are not stored as builder pages. */
    public const ROUTES = [
        'home' => ['Home', '/'],
        'shop' => ['Shop', '/shop'],
        'blog' => ['Blog', '/blog'],
        'portfolio' => ['Portfolio', '/portfolio'],
    ];

    /** Utility pages that must never be indexed or listed. */
    public const UTILITY_PAGES = ['page-not-found', 'thank-you'];

    public static function typeOf(Model $m): ?string
    {
        foreach (self::TYPES as $type => [, $class]) {
            if ($class && $m instanceof $class) {
                return $type;
            }
        }

        return null;
    }

    public static function find(string $type, string $key): Model|string|null
    {
        if ($type === 'route') {
            return isset(self::ROUTES[$key]) ? $key : null;
        }
        $class = self::TYPES[$type][1] ?? null;

        return $class ? $class::find($key) : null;
    }

    /** Which SEO values a content table already stores itself (they stay the single source of truth). */
    public static function columns(string $type): array
    {
        static $cache = [];
        if (isset($cache[$type])) {
            return $cache[$type];
        }
        $class = self::TYPES[$type][1] ?? null;
        if (! $class) {
            return $cache[$type] = [];
        }
        $table = (new $class)->getTable();
        $cols = [];
        foreach (['title' => 'seo_title', 'description' => 'seo_description', 'og_image' => 'og_image', 'noindex' => 'noindex'] as $field => $col) {
            if (rescue(fn () => Schema::hasColumn($table, $col), false, false)) {
                $cols[$field] = $col;
            }
        }

        return $cache[$type] = $cols;
    }

    public static function label(Model|string $s): string
    {
        if (is_string($s)) {
            return self::ROUTES[$s][0] ?? $s;
        }

        return (string) ($s->title ?? $s->name ?? '#'.$s->getKey());
    }

    public static function path(Model|string $s): string
    {
        if (is_string($s)) {
            return self::ROUTES[$s][1] ?? '/';
        }

        return match (true) {
            $s instanceof Page => '/'.$s->slug,
            $s instanceof Post => '/blog/'.$s->slug,
            $s instanceof PostCategory => '/blog/category/'.$s->slug,
            $s instanceof Project => '/portfolio/'.$s->slug,
            $s instanceof Product => '/shop/'.$s->slug,
            $s instanceof ProductCategory => '/shop/category/'.$s->slug,
            default => '/',
        };
    }

    /** Is it public (visible to visitors) at all? */
    public static function isPublic(Model|string $s): bool
    {
        if (is_string($s)) {
            return isset(self::ROUTES[$s]);
        }

        return match (true) {
            $s instanceof Page => $s->status === Page::STATUS_PUBLISHED && ! $s->is_template,
            $s instanceof Post => $s->status === Post::STATUS_PUBLISHED && $s->published_at && $s->published_at <= now(),
            $s instanceof Project => (bool) $s->is_active && in_array($s->status, ['published', null], true),
            $s instanceof Product => $s->status === 'published',
            $s instanceof PostCategory => $s->posts()->published()->exists(),
            $s instanceof ProductCategory => $s->products()->published()->exists(),
            default => false,
        };
    }

    /** null when indexable, otherwise the reason it is not. */
    public static function notIndexableReason(Model|string $s): ?string
    {
        if (! self::isPublic($s)) {
            return 'not public';
        }
        if ($s instanceof Page && in_array($s->slug, self::UTILITY_PAGES, true)) {
            return 'utility page';
        }
        $type = is_string($s) ? 'route' : self::typeOf($s);
        $key = is_string($s) ? $s : (string) $s->getKey();
        if (! is_string($s) && isset(self::columns($type)['noindex']) && $s->noindex) {
            return 'noindex';
        }
        $meta = SeoMeta::for($type, $key);
        if ($meta?->noindex) {
            return 'noindex';
        }
        if ($meta?->canonical && Seo::absolute($meta->canonical) !== Seo::absolute(self::path($s))) {
            return 'canonical points elsewhere';
        }

        return null;
    }

    public static function lastModified(Model|string $s): ?\DateTimeInterface
    {
        if (is_string($s)) {
            return match ($s) {
                'shop' => Product::published()->max('updated_at') ? \Illuminate\Support\Carbon::parse(Product::published()->max('updated_at')) : null,
                'blog' => Post::published()->max('updated_at') ? \Illuminate\Support\Carbon::parse(Post::published()->max('updated_at')) : null,
                'portfolio' => Project::public()->max('updated_at') ? \Illuminate\Support\Carbon::parse(Project::public()->max('updated_at')) : null,
                default => null,
            };
        }

        return $s->updated_at;
    }

    public static function image(Model|string $s): ?string
    {
        if (is_string($s)) {
            return null;
        }

        return $s->image ?? $s->og_image ?? null ?: null;
    }

    /** Admin link to edit the content itself. */
    public static function editUrl(Model|string $s): ?string
    {
        if (is_string($s)) {
            return match ($s) {
                'home' => route('admin.settings.edit', 'hero'),
                'shop' => route('admin.settings.edit', 'store'),
                'blog' => route('admin.settings.edit', 'blog_page'),
                'portfolio' => route('admin.settings.edit', 'portfolio_page'),
                default => null,
            };
        }

        return rescue(fn () => match (true) {
            $s instanceof Page => route('admin.pages.builder', $s),
            $s instanceof Post => route('admin.resource.edit', ['posts', $s->id]),
            $s instanceof PostCategory => route('admin.resource.edit', ['post_categories', $s->id]),
            $s instanceof Project => route('admin.resource.edit', ['projects', $s->id]),
            $s instanceof Product => route('admin.resource.edit', ['products', $s->id]),
            $s instanceof ProductCategory => route('admin.resource.edit', ['product_categories', $s->id]),
            default => null,
        }, null, false);
    }

    /**
     * Every public URL the CMS knows about: [type, key, label, path, subject].
     * Drafts are included (flagged) so the dashboard can show them; the sitemap filters.
     */
    public static function all(bool $publicOnly = false): array
    {
        $out = [];
        foreach (array_keys(self::ROUTES) as $r) {
            $out[] = ['route', $r, $r];
        }
        foreach (Page::where('is_template', false)->orderBy('slug')->get() as $p) {
            $out[] = ['page', (string) $p->id, $p];
        }
        foreach (Post::orderByDesc('published_at')->get() as $p) {
            $out[] = ['post', (string) $p->id, $p];
        }
        foreach (PostCategory::orderBy('name')->get() as $c) {
            $out[] = ['post_category', (string) $c->id, $c];
        }
        foreach (Project::orderBy('sort_order')->get() as $p) {
            $out[] = ['project', (string) $p->id, $p];
        }
        foreach (Product::orderBy('name')->get() as $p) {
            $out[] = ['product', (string) $p->id, $p];
        }
        foreach (ProductCategory::orderBy('name')->get() as $c) {
            $out[] = ['product_category', (string) $c->id, $c];
        }
        if ($publicOnly) {
            $out = array_values(array_filter($out, fn ($r) => self::isPublic($r[2])));
        }

        return $out;
    }
}
