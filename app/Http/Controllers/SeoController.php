<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Seo\Seo;
use App\Seo\Subjects;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** robots.txt and the XML sitemaps (generated from the database, cached for an hour, refreshed on every content change). */
class SeoController extends Controller
{
    public const PER_FILE = 2000;

    public const SECTIONS = ['pages', 'posts', 'projects', 'products', 'categories'];

    public function robots(): Response
    {
        $lines = ['User-agent: *'];
        if (! Seo::siteIndexable()) {
            // Draft / staging domain: keep everything out of search engines.
            $lines[] = 'Disallow: /';
        } else {
            foreach (['/admin', '/install', '/_deploy', '/cart', '/checkout', '/account', '/order/', '/download/', '/payment/', '/search'] as $p) {
                $lines[] = 'Disallow: '.$p;
            }
            $lines[] = 'Allow: /assets/';
            $lines[] = 'Allow: /uploads/';
            $lines[] = 'Allow: /vendor/';
            $lines[] = '';
            $lines[] = 'Sitemap: '.Seo::absolute('/sitemap.xml');
        }
        if ($extra = trim((string) Seo::cfg('robots_extra'))) {
            $lines[] = '';
            $lines[] = preg_replace('/[^\x20-\x7E\r\n]/', '', $extra);
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    protected function cached(string $key, callable $fn): string
    {
        $v = Cache::get('sitemap_v', 1);

        return Cache::remember('sitemap:'.$v.':'.request()->getHost().':'.$key, 3600, $fn);
    }

    protected function xml(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'X-Robots-Tag' => 'noindex']);
    }

    public function index(): Response
    {
        abort_unless(Seo::siteIndexable() || request()->boolean('preview'), 404);

        return $this->xml($this->cached('index', function () {
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach (self::SECTIONS as $section) {
                $entries = $this->entries($section);
                $pages = (int) ceil(count($entries) / self::PER_FILE);
                for ($i = 1; $i <= $pages; $i++) {
                    $last = collect(array_slice($entries, ($i - 1) * self::PER_FILE, self::PER_FILE))->max('lastmod');
                    $out .= '  <sitemap><loc>'.e(Seo::absolute('/sitemap-'.$section.($pages > 1 ? '-'.$i : '').'.xml')).'</loc>'.($last ? '<lastmod>'.$last.'</lastmod>' : '').'</sitemap>'."\n";
                }
            }

            return $out.'</sitemapindex>'."\n";
        }));
    }

    public function section(string $section, ?int $page = null): Response
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);
        abort_unless(Seo::siteIndexable() || request()->boolean('preview'), 404);
        $page = max(1, (int) $page);

        return $this->xml($this->cached($section.':'.$page, function () use ($section, $page) {
            $entries = array_slice($this->entries($section), ($page - 1) * self::PER_FILE, self::PER_FILE);
            abort_if(! $entries && $page > 1, 404);
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
            foreach ($entries as $e) {
                $out .= '  <url><loc>'.e($e['loc']).'</loc>'.($e['lastmod'] ? '<lastmod>'.$e['lastmod'].'</lastmod>' : '');
                foreach ($e['images'] as $img) {
                    $out .= '<image:image><image:loc>'.e($img).'</image:loc></image:image>';
                }
                $out .= '</url>'."\n";
            }

            return $out.'</urlset>'."\n";
        }));
    }

    /** Indexable, canonical URLs of one section. */
    public function entries(string $section): array
    {
        $items = match ($section) {
            'pages' => array_merge(array_keys(Subjects::ROUTES), Page::published()->orderBy('slug')->get()->all()),
            'posts' => Post::published()->latest('published_at')->get()->all(),
            'projects' => Project::public()->orderBy('sort_order')->get()->all(),
            'products' => Product::published()->orderBy('name')->get()->all(),
            'categories' => array_merge(PostCategory::orderBy('name')->get()->all(), ProductCategory::orderBy('name')->get()->all()),
            default => [],
        };
        $out = [];
        foreach ($items as $s) {
            if ($s instanceof Page && $s->slug === 'home') {
                continue; // the home page lives at "/"
            }
            if (Subjects::notIndexableReason($s) !== null) {
                continue;
            }
            $img = Subjects::image($s);
            $mod = Subjects::lastModified($s);
            $out[] = [
                'loc' => Seo::absolute(Subjects::path($s)),
                'lastmod' => $mod ? $mod->format('Y-m-d\TH:i:sP') : null,
                'images' => $img ? [Seo::absolute((string) (parse_url(media($img), PHP_URL_PATH) ?: $img))] : [],
            ];
        }

        return $out;
    }

    public static function flush(): void
    {
        Cache::forever('sitemap_v', (int) Cache::get('sitemap_v', 1) + 1);
    }
}
