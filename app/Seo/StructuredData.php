<?php

namespace App\Seo;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;

/**
 * One JSON-LD @graph per page, built only from real stored values. Entities point to each other by @id.
 * No ratings, reviews, awards or invented addresses; Offer only when a real price exists; no FAQPage
 * (Google limits FAQ rich results to authoritative government/health sites).
 */
class StructuredData
{
    public function __construct(protected Seo $seo, protected array $r) {}

    public static function id(string $frag): string
    {
        return Seo::base().'/#'.$frag;
    }

    public static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $text)), fn ($u) => preg_match('#^https://#', $u)));
    }

    protected function img(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return preg_match('#^https?://#', $path) ? $path : Seo::absolute((string) (parse_url(media($path), PHP_URL_PATH) ?: $path));
    }

    public function graph(): array
    {
        $s = $this->seo->getSubject();
        $r = $this->r;
        $url = strtok($r['canonical'], '?');
        $g = [];

        // --- the business
        $org = array_filter([
            '@type' => Seo::cfg('local_business') && Seo::cfg('street') ? 'ProfessionalService' : 'Organization',
            '@id' => self::id('organization'),
            'name' => Seo::siteName(),
            'url' => Seo::absolute('/'),
            'logo' => ($logo = $this->img(Seo::cfg('org_logo') ?: setting('site.logo'))) ? ['@type' => 'ImageObject', '@id' => self::id('logo'), 'url' => $logo] : null,
            'description' => Seo::cfg('org_description'),
            'email' => Seo::cfg('contact_email') ?: setting('site.email'),
            'telephone' => Seo::cfg('contact_phone') ?: null,
            'sameAs' => self::lines(Seo::cfg('same_as')) ?: null,
            'founder' => Seo::cfg('founder_name') ? ['@id' => self::id('founder')] : null,
        ]);
        if (Seo::cfg('local_business') && Seo::cfg('street')) {
            $org['address'] = array_filter(['@type' => 'PostalAddress', 'streetAddress' => Seo::cfg('street'), 'addressLocality' => Seo::cfg('city'), 'postalCode' => Seo::cfg('postal_code'), 'addressCountry' => Seo::cfg('country')]);
            $org['image'] = $org['logo']['url'] ?? null;
        }
        $g[] = $org;

        if ($name = Seo::cfg('founder_name')) {
            $g[] = array_filter([
                '@type' => 'Person', '@id' => self::id('founder'), 'name' => $name,
                'jobTitle' => Seo::cfg('founder_job_title'), 'url' => Seo::cfg('founder_url') ?: null,
                'image' => $this->img(Seo::cfg('founder_image')),
                'sameAs' => self::lines(Seo::cfg('founder_same_as')) ?: null,
                'worksFor' => ['@id' => self::id('organization')],
            ]);
        }

        $g[] = ['@type' => 'WebSite', '@id' => self::id('website'), 'url' => Seo::absolute('/'), 'name' => Seo::siteName(), 'publisher' => ['@id' => self::id('organization')], 'inLanguage' => 'en'];

        // --- this page
        $pageType = match (true) {
            $s === 'home' => 'WebPage',
            in_array($s, ['shop', 'blog', 'portfolio'], true), $s instanceof PostCategory, $s instanceof ProductCategory => 'CollectionPage',
            $s instanceof Page && str_starts_with($s->slug, 'about') => 'AboutPage',
            $s instanceof Page && str_starts_with($s->slug, 'contact') => 'ContactPage',
            $s instanceof Product, $s instanceof Project => 'ItemPage',
            default => 'WebPage',
        };
        $webpage = array_filter([
            '@type' => $pageType, '@id' => $url.'#webpage', 'url' => $url, 'name' => $r['title'], 'description' => $r['description'] ?: null,
            'isPartOf' => ['@id' => self::id('website')], 'inLanguage' => 'en',
            'about' => $s === 'home' ? ['@id' => self::id('organization')] : null,
            'primaryImageOfPage' => $r['og_image'] ? ['@type' => 'ImageObject', 'url' => $r['og_image']] : null,
            'datePublished' => $r['published']?->toAtomString(),
            'dateModified' => $r['modified']?->toAtomString(),
            'breadcrumb' => count($r['breadcrumbs']) > 1 ? ['@id' => $url.'#breadcrumb'] : null,
        ]);
        $g[] = $webpage;

        if (count($r['breadcrumbs']) > 1) {
            $g[] = ['@type' => 'BreadcrumbList', '@id' => $url.'#breadcrumb', 'itemListElement' => array_map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => Seo::clean($c[0]), 'item' => $c[1]], $r['breadcrumbs'], array_keys($r['breadcrumbs']))];
        }

        // --- the main thing on the page
        if ($s instanceof Post) {
            $author = $s->author;
            $authorNode = $author ? (Seo::cfg('founder_name') && mb_strtolower($author->name) === mb_strtolower(Seo::cfg('founder_name')) ? ['@id' => self::id('founder')] : ['@type' => 'Person', 'name' => $author->name]) : ['@id' => self::id('organization')];
            $g[] = array_filter([
                '@type' => 'BlogPosting', '@id' => $url.'#article', 'headline' => Seo::clean($s->title, 110), 'description' => $r['description'] ?: null,
                'image' => $s->image ? [$this->img($s->image)] : null, 'datePublished' => $s->published_at?->toAtomString(), 'dateModified' => $s->updated_at?->toAtomString(),
                'author' => $authorNode, 'publisher' => ['@id' => self::id('organization')], 'mainEntityOfPage' => ['@id' => $url.'#webpage'],
                'articleSection' => $s->category?->name, 'keywords' => $s->tags ?: null, 'inLanguage' => 'en',
                'wordCount' => str_word_count(Seo::clean($s->body)) ?: null,
            ]);
        } elseif ($s instanceof Product) {
            [$regular, $sale] = $s->prices(($s->variants ?? [])[0]['key'] ?? null);
            $price = $sale ?? $regular;
            $images = array_values(array_filter(array_map(fn ($i) => $this->img(is_array($i) ? ($i['image'] ?? null) : $i), array_merge([$s->image], $s->gallery ?? []))));
            $product = array_filter([
                '@type' => 'Product', '@id' => $url.'#product', 'name' => $s->name, 'description' => Seo::clean($s->short_description ?: $s->description, 1000) ?: null,
                'image' => $images ?: null, 'sku' => $s->sku ?: null, 'category' => $s->category?->name, 'brand' => ['@type' => 'Brand', 'name' => Seo::siteName()],
                'url' => $url,
            ]);
            if ($price > 0 && $s->status === 'published') {
                $product['offers'] = array_filter([
                    '@type' => 'Offer', 'url' => $url, 'price' => number_format((float) $price, 2, '.', ''), 'priceCurrency' => store('currency', 'BDT'),
                    'availability' => $s->inStock(($s->variants ?? [])[0]['key'] ?? null) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition' => 'https://schema.org/NewCondition', 'seller' => ['@id' => self::id('organization')],
                    'priceValidUntil' => $sale !== null && $s->sale_ends_at ? $s->sale_ends_at->toDateString() : null,
                ]);
            }
            $g[] = $product;
        } elseif ($s instanceof Project) {
            $g[] = array_filter([
                '@type' => 'CreativeWork', '@id' => $url.'#work', 'name' => $s->title, 'description' => $r['description'] ?: null,
                'image' => $s->image ? $this->img($s->image) : null, 'creator' => ['@id' => self::id('organization')], 'url' => $url,
                'dateCreated' => $s->year ? (string) $s->year : null, 'keywords' => $s->tags ?: null, 'genre' => $s->category ?: null,
            ]);
        } elseif ($s instanceof Page && ($service = $this->seo->serviceFor($s))) {
            $g[] = array_filter([
                '@type' => 'Service', '@id' => $url.'#service', 'name' => $service->title, 'serviceType' => $service->title,
                'description' => Seo::clean($service->short_description) ?: ($r['description'] ?: null),
                'provider' => ['@id' => self::id('organization')], 'url' => $url, 'mainEntityOfPage' => ['@id' => $url.'#webpage'],
            ]);
        }

        // --- owner's extra JSON-LD (validated when saved)
        $override = $this->seo->meta()?->schema_override;
        if ($override && is_array($extra = json_decode($override, true))) {
            foreach (isset($extra['@graph']) ? (array) $extra['@graph'] : (array_is_list($extra) ? $extra : [$extra]) as $node) {
                if (is_array($node) && ! empty($node['@type'])) {
                    unset($node['@context']);
                    $g[] = $node;
                }
            }
        }

        return $g;
    }

    public function json(): string
    {
        return json_encode(['@context' => 'https://schema.org', '@graph' => $this->graph()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /** Validate owner-supplied JSON-LD. Returns [ok, normalisedJson|error]. */
    public static function validateOverride(?string $json): array
    {
        $json = trim((string) $json);
        if ($json === '') {
            return [true, null];
        }
        if (strlen($json) > 20000) {
            return [false, 'JSON-LD is too long (max 20 KB).'];
        }
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return [false, 'Not valid JSON: '.json_last_error_msg()];
        }
        $nodes = isset($data['@graph']) ? (array) $data['@graph'] : (array_is_list($data) ? $data : [$data]);
        foreach ($nodes as $n) {
            if (! is_array($n) || empty($n['@type'])) {
                return [false, 'Every item needs an "@type".'];
            }
            if (preg_match('/aggregateRating|"review"/i', json_encode($n))) {
                return [false, 'Ratings / reviews are not allowed here unless they are shown on the page and genuinely collected — remove aggregateRating/review.'];
            }
        }

        return [true, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)];
    }
}
