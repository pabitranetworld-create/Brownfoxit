<?php

namespace App\Seo;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\SeoMeta;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Builds the one and only set of search/social tags for the current page.
 * Priority for every value: owner's per-page SEO settings → value given by the view → generated from the content → site defaults.
 */
class Seo
{
    protected Model|string|null $subject = null;

    protected array $hints = [];

    protected ?array $resolved = null;

    /** Query parameters that create a genuinely different page; everything else is dropped from canonicals. */
    public const KEEP_PARAMS = ['page'];

    public function subject(Model|string|null $s): static
    {
        $this->subject = $s;
        $this->resolved = null;

        return $this;
    }

    public function getSubject(): Model|string|null
    {
        return $this->subject;
    }

    /** Values from the view: title, description, robots, canonical, image, type, breadcrumbs, schema[] */
    public function set(array $values): static
    {
        foreach ($values as $k => $v) {
            if ($v !== null && $v !== '') {
                $this->hints[$k] = $v;
            }
        }
        $this->resolved = null;

        return $this;
    }

    public static function cfg(string $key, mixed $default = null): mixed
    {
        $v = setting('seo.'.$key);

        return ($v === null || $v === '') ? $default : $v;
    }

    public static function siteName(): string
    {
        return (string) (self::cfg('org_name') ?: setting('site.name', config('app.name')));
    }

    /** Is this request on the real (production) domain? */
    public static function onProductionHost(): bool
    {
        $prod = strtolower(trim((string) self::cfg('production_host', ''), '/ '));
        $prod = preg_replace('#^https?://#', '', $prod);
        if ($prod === '') {
            return true;
        }
        $host = strtolower(request()->getHost());

        return preg_replace('/^www\./', '', $host) === preg_replace('/^www\./', '', $prod);
    }

    /** May search engines index this site right now? (draft domains stay hidden automatically) */
    public static function siteIndexable(): bool
    {
        return match (self::cfg('visibility', 'auto')) {
            'index' => true,
            'noindex' => false,
            default => self::onProductionHost(),
        };
    }

    /** Cart, checkout, orders, account, downloads and payment returns are never indexed. */
    public static function isTransactional(): bool
    {
        return request()->routeIs('cart.*', 'checkout*', 'shop.checkout*', 'shop.order*', 'shop.pay*', 'shop.redirect*', 'account.*', 'download*', 'payment.*', 'search')
            || request()->is('cart', 'cart/*', 'checkout', 'checkout/*', 'order/*', 'account', 'account/*', 'download/*', 'payment/*');
    }

    /** Base URL used for canonicals and the sitemap. */
    public static function base(): string
    {
        $prod = preg_replace('#^https?://#', '', strtolower(trim((string) self::cfg('production_host', ''), '/ ')));
        if ($prod !== '' && self::onProductionHost()) {
            return 'https://'.$prod;
        }

        return rtrim(request()->getSchemeAndHttpHost(), '/');
    }

    public static function absolute(string $pathOrUrl): string
    {
        if (preg_match('#^https?://#i', $pathOrUrl)) {
            return $pathOrUrl;
        }
        $path = '/'.ltrim($pathOrUrl, '/');

        return self::base().($path === '/' ? '/' : rtrim($path, '/'));
    }

    public static function clean(?string $text, int $limit = 0): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $limit ? Str::limit($t, $limit, '…') : $t;
    }

    protected function typeKey(): array
    {
        $s = $this->subject;
        if ($s === null) {
            return [null, null];
        }

        return is_string($s) ? ['route', $s] : [Subjects::typeOf($s), (string) $s->getKey()];
    }

    public function meta(): ?SeoMeta
    {
        [$t, $k] = $this->typeKey();

        return $t ? SeoMeta::for($t, $k) : null;
    }

    /** Model column value (seo_title etc.) when the table has it. */
    protected function column(string $field): mixed
    {
        $s = $this->subject;
        if (! $s instanceof Model) {
            return null;
        }
        $col = Subjects::columns(Subjects::typeOf($s) ?? '')[$field] ?? null;

        return $col ? $s->{$col} : null;
    }

    public function resolve(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }
        $s = $this->subject;
        $meta = $this->meta();
        $site = self::siteName();
        $sep = ' '.trim((string) self::cfg('title_separator', '|')).' ';

        // ---- title
        $isHome = $s === 'home';
        $custom = $meta?->title ?: $this->column('title');
        if ($custom) {
            $title = $custom;
        } elseif ($isHome) {
            $title = self::cfg('home_title') ?: setting('site.meta_title') ?: $site;
        } else {
            $base = $this->hints['title'] ?? ($s ? Subjects::label($s) : $site);
            $base = preg_replace('/\s*[·|–-]\s*'.preg_quote($site, '/').'\s*$/u', '', $base); // views used "X · Site"
            $title = Str::contains(mb_strtolower($base), mb_strtolower($site)) ? $base : $base.$sep.$site;
        }
        $title = self::clean($title);

        // ---- description
        $desc = $meta?->description ?: $this->column('description') ?: ($this->hints['description'] ?? null) ?: $this->generatedDescription()
            ?: ($isHome ? setting('site.meta_description') : null) ?: self::cfg('default_description') ?: setting('site.meta_description');
        $desc = self::clean($desc, 300);

        // ---- canonical
        $canonical = $meta?->canonical ?: ($this->hints['canonical'] ?? null);
        $canonical = $canonical ? self::absolute($canonical) : $this->currentCanonical();

        // ---- robots
        $noindex = ! self::siteIndexable() || (bool) $meta?->noindex || (bool) $this->column('noindex')
            || ($s !== null && ! Subjects::isPublic($s)) || ($s instanceof Page && in_array($s->slug, Subjects::UTILITY_PAGES, true))
            || str_contains((string) ($this->hints['robots'] ?? ''), 'noindex') || self::isTransactional();
        $nofollow = ! self::siteIndexable() || self::isTransactional() || (bool) $meta?->nofollow || str_contains((string) ($this->hints['robots'] ?? ''), 'nofollow');
        $robots = ($noindex ? 'noindex' : 'index').', '.($nofollow ? 'nofollow' : 'follow');
        if (! $noindex) {
            $robots .= ', '.($meta?->robots_extra ?: 'max-image-preview:large, max-snippet:-1, max-video-preview:-1');
        }

        // ---- social
        $image = $meta?->og_image ?: $this->column('og_image') ?: ($this->hints['image'] ?? null) ?: ($s ? Subjects::image($s) : null)
            ?: self::cfg('default_og_image') ?: setting('hero.person_image') ?: setting('site.logo');
        $imageUrl = null;
        if ($image) {
            $imageUrl = preg_match('#^https?://#', $image) && ! Str::startsWith($image, url('/'))
                ? $image
                : self::absolute((string) (parse_url(media($image), PHP_URL_PATH) ?: $image));
        }
        $type = $this->hints['type'] ?? match (true) {
            $s instanceof Post => 'article',
            $s instanceof Product => 'product',
            default => 'website',
        };

        $crumbs = $meta?->hide_breadcrumbs ? [] : $this->breadcrumbs();

        $this->resolved = [
            'title' => $title,
            'description' => $desc,
            'canonical' => $canonical,
            'robots' => $robots,
            'noindex' => $noindex,
            'og_title' => self::clean($meta?->og_title ?: $title),
            'og_description' => self::clean($meta?->og_description ?: $desc, 300),
            'og_image' => $imageUrl,
            'og_image_alt' => self::clean($meta?->og_image_alt ?: ($s ? Subjects::label($s) : $site)),
            'og_type' => $type,
            'twitter_card' => $meta?->twitter_card ?: ($imageUrl ? 'summary_large_image' : 'summary'),
            'twitter_site' => ltrim((string) self::cfg('twitter_handle', ''), '@'),
            'locale' => self::cfg('locale', 'en_US'),
            'breadcrumbs' => $crumbs,
            'published' => $s instanceof Post ? $s->published_at : null,
            'modified' => $s instanceof Model ? $s->updated_at : null,
        ];
        $this->resolved['jsonld'] = (new StructuredData($this, $this->resolved))->json();

        return $this->resolved;
    }

    public function generatedDescription(): ?string
    {
        $s = $this->subject;
        $text = match (true) {
            $s instanceof Post => $s->excerpt ?: $s->body,
            $s instanceof Project => $s->summary ?: $s->description,
            $s instanceof Product => $s->short_description ?: $s->description,
            $s instanceof ProductCategory => $s->description ?: 'Browse '.$s->name.' products from '.self::siteName().'.',
            $s instanceof PostCategory => ($s->description ?? null) ?: 'Articles about '.$s->name.' from '.self::siteName().'.',
            $s instanceof Page => $this->pageText($s),
            $s === 'blog' => setting('blog_page.meta_description') ?: setting('blog_page.subtitle'),
            $s === 'portfolio' => setting('portfolio_page.meta_description') ?: setting('portfolio_page.subtitle'),
            $s === 'shop' => setting('store.shop_subtitle') ?: setting('store.shop_intro'),
            default => null,
        };
        $text = self::clean($text);

        return $text === '' ? null : Str::limit($text, 158, '…');
    }

    /** First meaningful text of a builder page: hero text, else the first text widget. */
    protected function pageText(Page $p): ?string
    {
        $first = null;
        foreach ((array) ($p->layout['sections'] ?? []) as $sec) {
            foreach ((array) ($sec['columns'] ?? []) as $col) {
                foreach ((array) ($col['widgets'] ?? []) as $w) {
                    $d = (array) ($w['data'] ?? []);
                    if (($w['type'] ?? '') === 'hero' && ! empty($d['text'])) {
                        return $d['text'];
                    }
                    if (! $first && in_array($w['type'] ?? '', ['text', 'heading'], true)) {
                        $t = self::clean($d['html'] ?? $d['subtitle'] ?? '');
                        if (mb_strlen($t) > 60) {
                            $first = $t;
                        }
                    }
                }
            }
        }

        return $first ?: $p->header_subtitle;
    }

    /** Canonical for the current request: own URL, no tracking/sort/filter parameters, page=1 removed. */
    public function currentCanonical(): string
    {
        $req = request();
        $q = array_intersect_key($req->query(), array_flip(self::KEEP_PARAMS));
        if (isset($q['page']) && (int) $q['page'] <= 1) {
            unset($q['page']);
        }
        $path = '/'.trim($req->path(), '/');

        return self::absolute($path === '/' ? '/' : $path).($q ? '?'.http_build_query($q) : '');
    }

    /** [[name, absolute url], …] — Home first, the current page last. */
    public function breadcrumbs(): array
    {
        if (! empty($this->hints['breadcrumbs'])) {
            return array_map(fn ($c) => [$c[0], self::absolute($c[1])], $this->hints['breadcrumbs']);
        }
        $s = $this->subject;
        if ($s === null || $s === 'home') {
            return [];
        }
        $label = $this->meta()?->breadcrumb_label ?: Subjects::label($s);
        $c = [['Home', self::absolute('/')]];
        if ($s instanceof Page) {
            $parts = explode('/', $s->slug);
            $acc = '';
            foreach (array_slice($parts, 0, -1) as $part) {
                $acc .= '/'.$part;
                $parent = Page::where('slug', ltrim($acc, '/'))->published()->first();
                $c[] = [$parent ? $parent->title : Str::headline($part), self::absolute($acc)];
            }
        } elseif ($s instanceof Post) {
            $c[] = ['Blog', self::absolute('/blog')];
            if ($s->category) {
                $c[] = [$s->category->name, self::absolute('/blog/category/'.$s->category->slug)];
            }
        } elseif ($s instanceof PostCategory) {
            $c[] = ['Blog', self::absolute('/blog')];
        } elseif ($s instanceof Project) {
            $c[] = ['Portfolio', self::absolute('/portfolio')];
        } elseif ($s instanceof Product) {
            $c[] = ['Shop', self::absolute('/shop')];
            if ($s->category) {
                $c[] = [$s->category->name, self::absolute($s->category->link())];
            }
        } elseif ($s instanceof ProductCategory) {
            $c[] = ['Shop', self::absolute('/shop')];
        }
        $c[] = [$label, self::absolute(Subjects::path($s))];

        return $c;
    }

    /** Service record that belongs to a builder page (services/x → Service with that url). */
    public function serviceFor(Page $p): ?Service
    {
        return rescue(fn () => Service::where('url', '/'.$p->slug)->orWhere('url', url('/'.$p->slug))->first(), null, false);
    }
}
