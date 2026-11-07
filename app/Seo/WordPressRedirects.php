<?php

namespace App\Seo;

use App\Models\ImportLog;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\Redirect;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads the old WordPress site's public addresses (pages, posts, categories, tags, products, portfolio)
 * and proposes where each should go on the new site. Nothing is created until the owner confirms;
 * addresses without a sensible match are listed for a manual choice — never sent to the home page by default.
 */
class WordPressRedirects
{
    protected const ALIASES = [
        'about-us' => 'about', 'about-me' => 'about', 'who-we-are' => 'about',
        'contact-us' => 'contact', 'contact-me' => 'contact', 'get-in-touch' => 'contact',
        'our-services' => 'services', 'service' => 'services', 'what-we-do' => 'services',
        'our-work' => 'portfolio', 'projects' => 'portfolio', 'works' => 'portfolio', 'case-studies' => 'portfolio',
        'store' => 'shop', 'products' => 'shop', 'my-account' => 'account', 'cart' => 'cart', 'checkout' => 'checkout',
        'news' => 'blog', 'articles' => 'blog', 'insights' => 'blog',
        'privacy' => 'privacy-policy', 'terms' => 'terms-and-conditions', 'terms-of-service' => 'terms-and-conditions',
        'refund-policy' => 'refund-policy', 'return-policy' => 'refund-policy', 'refund_returns' => 'refund-policy',
        'get-a-quote' => 'start-a-project', 'quote' => 'start-a-project', 'hire-me' => 'start-a-project',
    ];

    public function __construct(protected string $site)
    {
        $this->site = rtrim($site, '/');
    }

    protected function all(string $path, array $query = []): array
    {
        $items = [];
        for ($page = 1; $page <= 20; $page++) {
            $res = Http::timeout(30)->retry(1, 500, throw: false)->acceptJson()->withHeaders(['User-Agent' => 'BrownFoxIT-Redirects/1.0'])
                ->get($this->site.'/wp-json/'.$path, $query + ['per_page' => 100, 'page' => $page]);
            if (! $res->successful() || ! is_array($res->json())) {
                break;
            }
            $items = array_merge($items, $res->json());
            if ($page >= (int) $res->header('X-WP-TotalPages')) {
                break;
            }
        }

        return $items;
    }

    protected function path(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $p = Redirect::normalize($url);

        return $p === '/' ? null : $p;
    }

    /** @return array<int, array{from:string, label:string, kind:string, to:?string, confidence:string, note:string, exists:bool}> */
    public function suggest(): array
    {
        $rows = [];
        $add = function (?string $from, string $label, string $kind, ?string $to, string $confidence, string $note = '') use (&$rows) {
            if (! $from || isset($rows[$from])) {
                return;
            }
            if ($to && Redirect::normalize($to) === $from) {
                return; // same address exists on the new site — nothing to do
            }
            $rows[$from] = ['from' => $from, 'label' => html_entity_decode(strip_tags($label)), 'kind' => $kind, 'to' => $to, 'confidence' => $to ? $confidence : 'none', 'note' => $note, 'exists' => isset(Redirect::map()[$from])];
        };

        foreach ($this->all('wp/v2/posts', ['_fields' => 'id,slug,link,title']) as $p) {
            $post = Post::where('slug', $p['slug'] ?? '')->first();
            $log = ImportLog::where('kind', 'posts')->where('source_id', (string) ($p['id'] ?? ''))->whereNotNull('target_id')->first();
            $post ??= $log ? Post::find($log->target_id) : null;
            $add($this->path($p['link'] ?? null), $p['title']['rendered'] ?? $p['slug'], 'Post', $post ? Subjects::path($post) : '/blog', $post ? 'high' : 'low', $post ? '' : 'Post not found on the new site — goes to the blog.');
        }
        foreach ($this->all('wp/v2/categories', ['_fields' => 'slug,link,name,count']) as $c) {
            $cat = PostCategory::where('slug', $c['slug'] ?? '')->first();
            $add($this->path($c['link'] ?? null), $c['name'] ?? '', 'Blog category', $cat ? Subjects::path($cat) : '/blog', $cat ? 'high' : 'medium');
        }
        foreach ($this->all('wp/v2/tags', ['_fields' => 'slug,link,name']) as $t) {
            $has = Post::published()->where('tags', 'like', '%'.($t['name'] ?? '-').'%')->exists();
            $add($this->path($t['link'] ?? null), '#'.($t['name'] ?? ''), 'Tag', $has ? '/blog/tag/'.Str::slug($t['name']) : '/blog', $has ? 'high' : 'medium');
        }
        foreach ($this->all('wc/store/v1/products') as $p) {
            $prod = Product::where('slug', $p['slug'] ?? '')->first();
            $live = $prod && $prod->status === 'published';
            $add($this->path($p['permalink'] ?? null), $p['name'] ?? '', 'Product', $live ? Subjects::path($prod) : '/shop', $live ? 'high' : 'medium', $live ? '' : ($prod ? 'Product is a draft on the new site — goes to the shop for now.' : 'Not on the new site — goes to the shop.'));
        }
        foreach ($this->all('wc/store/v1/products/categories') as $c) {
            $cat = ProductCategory::where('slug', $c['slug'] ?? '')->first();
            $add($this->path($c['permalink'] ?? null), $c['name'] ?? '', 'Product category', $cat ? Subjects::path($cat) : '/shop', $cat ? 'high' : 'medium');
        }
        // portfolio-like custom post types
        $types = Http::timeout(20)->acceptJson()->get($this->site.'/wp-json/wp/v2/types')->json() ?: [];
        foreach ((array) $types as $key => $t) {
            if (! is_array($t) || empty($t['rest_base']) || ! preg_match('/portfolio|project|case|work/i', $key.' '.($t['name'] ?? ''))) {
                continue;
            }
            foreach ($this->all(($t['rest_namespace'] ?? 'wp/v2').'/'.$t['rest_base'], ['_fields' => 'slug,link,title']) as $p) {
                $proj = Project::where('slug', $p['slug'] ?? '')->first();
                $add($this->path($p['link'] ?? null), $p['title']['rendered'] ?? '', 'Portfolio', $proj ? Subjects::path($proj) : '/portfolio', $proj ? 'high' : 'medium');
            }
        }
        // pages last (most need a judgement)
        $pages = Page::published()->get(['id', 'slug', 'title']);
        foreach ($this->all('wp/v2/pages', ['_fields' => 'slug,link,title']) as $p) {
            $from = $this->path($p['link'] ?? null);
            if (! $from) {
                continue;
            }
            $slug = trim($from, '/');
            $last = basename($slug);
            $title = html_entity_decode(strip_tags($p['title']['rendered'] ?? $last));
            [$to, $conf, $note] = [null, 'none', 'No clear match — choose where it should go.'];
            if ($pages->firstWhere('slug', $slug)) {
                continue; // identical address exists
            } elseif ($m = $pages->firstWhere('slug', 'services/'.$last)) {
                [$to, $conf, $note] = ['/'.$m->slug, 'high', ''];
            } elseif (isset(self::ALIASES[$last]) && ($a = self::ALIASES[$last]) && (in_array($a, ['shop', 'blog', 'portfolio', 'cart', 'checkout', 'account'], true) || $pages->firstWhere('slug', $a))) {
                [$to, $conf, $note] = ['/'.$a, 'high', ''];
            } else {
                $best = null;
                $bestScore = 0;
                foreach ($pages as $np) {
                    similar_text($last, basename($np->slug), $a);
                    similar_text(mb_strtolower($title), mb_strtolower($np->title), $b);
                    $score = max($a, $b);
                    if ($score > $bestScore) {
                        [$best, $bestScore] = [$np, $score];
                    }
                }
                if ($best && $bestScore >= 72) {
                    [$to, $conf, $note] = ['/'.$best->slug, 'medium', 'Similar name ('.round($bestScore).'%) — please check.'];
                }
            }
            $add($from, $title, 'Page', $to, $conf, $note);
        }

        return array_values($rows);
    }
}
