<?php

namespace App\Support;

use App\Models\ImportLog;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Copies content from a WordPress site through its public REST API:
 *  - blog posts      (wp/v2/posts)                    → Blog (published, same date)
 *  - portfolio items (any post type named like portfolio/project) → Portfolio
 *  - products        (WooCommerce Store API)          → Products, ALWAYS as drafts
 * Images are downloaded into public/uploads. Nothing that already exists is overwritten,
 * and every source item is recorded in import_logs (the inventory / report).
 */
class WordPressImporter
{
    public const PER_PAGE = 8;

    protected string $base;

    protected string $host;

    public function __construct(string $site)
    {
        $this->base = rtrim($site, '/');
        $this->host = preg_replace('/^www\./', '', (string) parse_url($this->base, PHP_URL_HOST));
    }

    protected function get(string $path, array $query = []): array
    {
        $res = Http::timeout(40)->retry(2, 800, throw: false)->withHeaders(['User-Agent' => 'BrownFoxIT-Importer/1.0'])->acceptJson()->get($this->base.'/wp-json/'.ltrim($path, '/'), $query);
        if (! $res->successful()) {
            return ['_error' => 'HTTP '.$res->status(), '_total_pages' => 0];
        }
        $data = $res->json();

        return ['_items' => is_array($data) ? $data : [], '_total' => (int) $res->header('X-WP-Total'), '_total_pages' => (int) $res->header('X-WP-TotalPages')];
    }

    /** What the source site offers. */
    public function discover(): array
    {
        $out = ['posts' => 0, 'portfolio' => [], 'products' => 0, 'errors' => []];
        $p = $this->get('wp/v2/posts', ['per_page' => 1]);
        isset($p['_error']) ? $out['errors'][] = 'Blog: '.$p['_error'] : $out['posts'] = $p['_total'];

        $types = $this->get('wp/v2/types');
        foreach ((array) ($types['_items'] ?? []) as $key => $t) {
            $name = strtolower($key.' '.($t['name'] ?? '').' '.($t['rest_base'] ?? ''));
            if (preg_match('/portfolio|project|case[-_ ]?stud|our[-_ ]?work/', $name) && ! empty($t['rest_base'])) {
                $c = $this->get(($t['rest_namespace'] ?? 'wp/v2').'/'.$t['rest_base'], ['per_page' => 1]);
                $out['portfolio'][] = ['type' => $key, 'name' => $t['name'] ?? $key, 'path' => ($t['rest_namespace'] ?? 'wp/v2').'/'.$t['rest_base'], 'count' => $c['_total'] ?? 0];
            }
        }
        $w = $this->get('wc/store/v1/products', ['per_page' => 1]);
        isset($w['_error']) ? $out['errors'][] = 'Products (WooCommerce Store API): '.$w['_error'] : $out['products'] = $w['_total'];

        return $out;
    }

    /**
     * Import one page. $kind = posts | portfolio | products. Returns [imported, skipped, failed, totalPages, messages].
     */
    public function batch(string $kind, int $page, ?string $path = null): array
    {
        $r = ['imported' => 0, 'skipped' => 0, 'failed' => 0, 'pages' => 0, 'messages' => []];
        $res = match ($kind) {
            'posts' => $this->get('wp/v2/posts', ['per_page' => self::PER_PAGE, 'page' => $page, '_embed' => 1, 'status' => 'publish']),
            'portfolio' => $this->get($path ?: 'wp/v2/portfolio', ['per_page' => self::PER_PAGE, 'page' => $page, '_embed' => 1]),
            'products' => $this->get('wc/store/v1/products', ['per_page' => self::PER_PAGE, 'page' => $page]),
        };
        if (isset($res['_error'])) {
            $r['messages'][] = $res['_error'];

            return $r;
        }
        $r['pages'] = $res['_total_pages'] ?: 1;
        foreach ($res['_items'] as $item) {
            try {
                $status = match ($kind) {
                    'posts' => $this->post($item),
                    'portfolio' => $this->project($item),
                    'products' => $this->product($item),
                };
                $r[$status === 'imported' || $status === 'missing_images' ? 'imported' : 'skipped']++;
            } catch (Throwable $e) {
                $r['failed']++;
                $this->log($kind, $item, 'failed', null, null, Str::limit($e->getMessage(), 300));
            }
        }

        return $r;
    }

    /** Run everything (re-runnable: already imported items are skipped). Stops after $budget seconds. */
    public function all(bool $products = true, int $budget = 240): array
    {
        $start = microtime(true);
        $found = $this->discover();
        $jobs = [['posts', null]];
        foreach ($found['portfolio'] as $p) {
            $jobs[] = ['portfolio', $p['path']];
        }
        if ($products) {
            $jobs[] = ['products', null];
        }
        $summary = ['found' => $found, 'done' => true, 'results' => []];
        foreach ($jobs as [$kind, $path]) {
            $page = 1;
            do {
                if (microtime(true) - $start > $budget) {
                    $summary['done'] = false; // run again to continue

                    return $summary;
                }
                $r = $this->batch($kind, $page, $path);
                $key = $kind.($path ? ':'.$path : '');
                foreach (['imported', 'skipped', 'failed'] as $f) {
                    $summary['results'][$key][$f] = ($summary['results'][$key][$f] ?? 0) + $r[$f];
                }
                if ($r['messages']) {
                    $summary['results'][$key]['messages'] = $r['messages'];
                }
                $page++;
            } while ($page <= $r['pages']);
        }

        return $summary;
    }

    protected function text(mixed $v): string
    {
        $s = is_array($v) ? (string) ($v['rendered'] ?? '') : (string) $v;

        return trim(html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    protected function exists(string $kind, array $item): ?ImportLog
    {
        return ImportLog::where('source', $this->base)->where('kind', $kind)->where('source_id', (string) $item['id'])->whereIn('status', ['imported', 'missing_images', 'exists', 'removed'])->first();
    }

    protected function log(string $kind, array $item, string $status, ?string $type, ?int $id, ?string $notes = null): string
    {
        ImportLog::updateOrCreate(
            ['source' => $this->base, 'kind' => $kind, 'source_id' => (string) ($item['id'] ?? Str::random(8))],
            ['source_url' => $item['link'] ?? $item['permalink'] ?? null, 'title' => Str::limit($this->text($item['title'] ?? $item['name'] ?? '(no title)'), 250, ''), 'target_type' => $type, 'target_id' => $id, 'status' => $status, 'notes' => $notes],
        );

        return $status;
    }

    protected function post(array $item): string
    {
        if ($this->exists('posts', $item)) {
            return 'skipped';
        }
        $slug = Str::slug($item['slug'] ?? '') ?: Str::slug($this->text($item['title']));
        if ($existing = Post::where('slug', $slug)->first()) {
            return $this->log('posts', $item, 'exists', Post::class, $existing->id, 'A post with this URL already exists — left unchanged.');
        }
        $missing = [];
        $body = $this->localiseHtml((string) ($item['content']['rendered'] ?? ''), $missing);
        $image = $this->featured($item, $missing);
        $terms = $item['_embedded']['wp:term'] ?? [];
        $cat = collect($terms)->flatten(1)->firstWhere('taxonomy', 'category');
        $tags = collect($terms)->flatten(1)->where('taxonomy', 'post_tag')->pluck('name')->map(fn ($n) => html_entity_decode($n))->implode(', ');
        $category = $cat && strtolower($cat['slug'] ?? '') !== 'uncategorized' ? PostCategory::firstOrCreate(['slug' => $cat['slug']], ['name' => html_entity_decode($cat['name'])]) : null;
        $seo = (array) ($item['yoast_head_json'] ?? []);
        $post = Post::create([
            'title' => $this->text($item['title']),
            'slug' => $slug,
            'excerpt' => Str::limit($this->text($item['excerpt'] ?? ''), 500),
            'body' => $body,
            'image' => $image,
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => isset($item['date_gmt']) ? \Carbon\Carbon::parse($item['date_gmt'].'Z') : now(),
            'post_category_id' => $category?->id,
            'user_id' => User::where('role', User::ROLE_ADMIN)->value('id'),
            'tags' => $tags ?: null,
            'seo_title' => isset($seo['title']) ? Str::limit(html_entity_decode($seo['title']), 250, '') : null,
            'seo_description' => isset($seo['description']) ? Str::limit(html_entity_decode($seo['description']), 490, '') : null,
            'source_url' => $item['link'] ?? null,
        ]);

        return $this->log('posts', $item, $missing ? 'missing_images' : 'imported', Post::class, $post->id, $missing ? 'Images not downloaded: '.implode(', ', array_slice($missing, 0, 5)) : null);
    }

    protected function project(array $item): string
    {
        if ($this->exists('portfolio', $item)) {
            return 'skipped';
        }
        $slug = Str::slug($item['slug'] ?? '') ?: Str::slug($this->text($item['title']));
        if ($existing = Project::where('slug', $slug)->first()) {
            return $this->log('portfolio', $item, 'exists', Project::class, $existing->id, 'A project with this URL already exists — left unchanged.');
        }
        $missing = [];
        $html = (string) ($item['content']['rendered'] ?? '');
        $gallery = [];
        if (preg_match_all('#<img[^>]+src=["\']([^"\']+)["\'][^>]*>#i', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $img) {
                $alt = preg_match('#alt=["\']([^"\']*)["\']#i', $img[0], $a) ? html_entity_decode($a[1]) : '';
                if ($p = $this->download($img[1], $missing)) {
                    $gallery[] = ['image' => $p, 'caption' => '', 'alt' => $alt];
                }
            }
        }
        $image = $this->featured($item, $missing) ?: ($gallery[0]['image'] ?? null);
        $gallery = array_values(array_filter($gallery, fn ($g) => $g['image'] !== $image));
        $terms = collect($item['_embedded']['wp:term'] ?? [])->flatten(1)->pluck('name')->map(fn ($n) => html_entity_decode($n))->unique()->values();
        $video = preg_match('#(https?://(?:www\.)?(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/|vimeo\.com/(?:video/)?)[A-Za-z0-9_\-?=&/]+)#i', $html, $v) ? $v[1] : null;
        $project = Project::create([
            'title' => $this->text($item['title']),
            'slug' => $slug,
            'category' => $terms->take(3)->implode(', ') ?: null,
            'tags' => $terms->slice(3)->implode(', ') ?: null,
            'summary' => Str::limit($this->text($item['excerpt'] ?? ''), 300) ?: null,
            'image' => $image,
            'gallery' => $gallery ?: null,
            'video_url' => $video,
            'description' => $this->localiseHtml($html, $missing),
            'status' => 'published',
            'is_featured' => false,
            'is_active' => true,
            'source_url' => $item['link'] ?? null,
            'sort_order' => 1000,
        ]);

        return $this->log('portfolio', $item, $missing ? 'missing_images' : 'imported', Project::class, $project->id, $missing ? 'Images not downloaded: '.implode(', ', array_slice($missing, 0, 5)) : null);
    }

    protected function product(array $item): string
    {
        if ($this->exists('products', $item)) {
            return 'skipped';
        }
        $slug = Str::slug($item['slug'] ?? '') ?: Str::slug($this->text($item['name'] ?? ''));
        if ($existing = Product::where('slug', $slug)->first()) {
            return $this->log('products', $item, 'exists', Product::class, $existing->id, 'A product with this URL already exists — left unchanged.');
        }
        $missing = [];
        $minor = (int) ($item['prices']['currency_minor_unit'] ?? 2);
        $div = 10 ** $minor;
        $regular = isset($item['prices']['regular_price']) ? (float) $item['prices']['regular_price'] / $div : 0;
        $sale = isset($item['prices']['sale_price']) ? (float) $item['prices']['sale_price'] / $div : null;
        $images = [];
        foreach ((array) ($item['images'] ?? []) as $img) {
            if ($p = $this->download($img['src'] ?? '', $missing)) {
                $images[] = $p;
            }
        }
        $cat = $item['categories'][0] ?? null;
        $category = $cat ? ProductCategory::firstOrCreate(['slug' => $cat['slug']], ['name' => html_entity_decode($cat['name'])]) : null;
        $product = Product::create([
            'name' => $this->text($item['name'] ?? ''),
            'slug' => $slug,
            'status' => 'draft', // always checked by the owner before publishing
            'type' => 'digital',
            'sku' => $item['sku'] ?? null ?: null,
            'product_category_id' => $category?->id,
            'tags' => collect($item['tags'] ?? [])->pluck('name')->implode(', ') ?: null,
            'short_description' => $this->localiseHtml((string) ($item['short_description'] ?? ''), $missing),
            'description' => $this->localiseHtml((string) ($item['description'] ?? ''), $missing),
            'image' => $images[0] ?? null,
            'gallery' => array_slice($images, 1) ?: null,
            'price' => $regular,
            'sale_price' => $sale !== null && $sale < $regular ? $sale : null,
            'source_url' => $item['permalink'] ?? null,
        ]);
        $note = 'Imported as DRAFT. Check price ('.($item['prices']['currency_code'] ?? '?').'), description and upload the download files before publishing.';

        return $this->log('products', $item, $missing ? 'missing_images' : 'imported', Product::class, $product->id, $note.($missing ? ' Images not downloaded: '.implode(', ', array_slice($missing, 0, 3)) : ''));
    }

    protected function featured(array $item, array &$missing): ?string
    {
        $src = $item['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;

        return $src ? $this->download($src, $missing) : null;
    }

    /** Download images from the source site referenced in HTML and point them to the local copies. */
    protected function localiseHtml(string $html, array &$missing): string
    {
        if ($html === '') {
            return '';
        }
        $html = preg_replace('#\s(srcset|sizes)=["\'][^"\']*["\']#i', '', $html);

        return preg_replace_callback('#(<img[^>]+src=["\'])([^"\']+)(["\'])#i', function ($m) use (&$missing) {
            $local = $this->download($m[2], $missing);

            return $m[1].($local ? '/'.$local : $m[2]).$m[3];
        }, $html);
    }

    /** Returns "uploads/…" relative path or null. Only images from the source site are downloaded. */
    public function download(string $url, array &$missing): ?string
    {
        $url = html_entity_decode(trim($url));
        if ($url === '' || str_starts_with($url, 'data:')) {
            return null;
        }
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        } elseif (str_starts_with($url, '/')) {
            $url = $this->base.$url;
        }
        $host = preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($host !== $this->host && ! preg_match('/^i[0-3]\.wp\.com$/', $host)) {
            return null; // external image: leave the link as it is
        }
        $rel = preg_match('#/wp-content/uploads/(.+)$#', $path, $m) ? 'uploads/'.$m[1] : 'uploads/imported/'.md5($url).'.'.(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
        $rel = preg_replace('#[^A-Za-z0-9_\-./]#', '-', rawurldecode($rel));
        if (! preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $rel) || str_contains($rel, '..')) {
            return null;
        }
        $target = public_path($rel);
        if (is_file($target) && filesize($target) > 0) {
            MediaRegistry::register($rel);

            return $rel;
        }
        try {
            $res = Http::timeout(40)->withHeaders(['User-Agent' => 'BrownFoxIT-Importer/1.0'])->get($url);
            $body = $res->body();
            $isSvg = str_ends_with(strtolower($rel), '.svg');
            if (! $res->successful() || strlen($body) === 0 || strlen($body) > 15 * 1024 * 1024 || (! $isSvg && @getimagesizefromstring($body) === false) || ($isSvg && preg_match('/<script|on\w+\s*=/i', $body))) {
                $missing[] = basename($path);

                return null;
            }
            File::ensureDirectoryExists(dirname($target));
            File::put($target, $body);
            MediaRegistry::register($rel); // show it in the media library too

            return $rel;
        } catch (Throwable $e) {
            $missing[] = basename($path);

            return null;
        }
    }
}
