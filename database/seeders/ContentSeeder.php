<?php

namespace Database\Seeders;

use App\Content\Fingerprint;
use App\Content\ServicePage;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\PageBuilder\LayoutValidator;
use Illuminate\Database\Seeder;

/**
 * Creates the initial pages (About, Services, 22 service pages, Process, FAQ, Contact,
 * Start a project, 404, legal drafts) and the service catalogue.
 *
 * Safe to run on every deploy:
 * - an existing page or service is never changed (your edits stay),
 * - something created once and later deleted by you is not created again
 *   (remembered in the "content_seeded" setting),
 * - menus are only updated while they are still the original, unedited defaults.
 */
class ContentSeeder extends Seeder
{
    protected array $done = [];

    protected array $log = [];

    public function run(): void
    {
        $this->done = (array) Setting::get('content_seeded', []);
        $validator = app(LayoutValidator::class);
        $dir = database_path('content');

        $services = array_merge(...array_map(fn ($f) => require $f, glob($dir.'/services/*.php')));
        foreach ($services as $slug => &$s) {
            $s['slug'] = $slug;
        }
        unset($s);

        // 1. Service catalogue (Services page, menus, quote form)
        $order = ['ai' => 100, 'web' => 200, 'software' => 300, 'media' => 400, 'design' => 500, 'growth' => 600];
        $i = 0;
        foreach ($services as $slug => $s) {
            $i++;
            $this->once('service:'.$slug, function () use ($s, $slug, $order, $i) {
                if (Service::where('url', '/services/'.$slug)->exists()) {
                    return false;
                }
                Service::create([
                    'title' => $s['title'], 'slug' => 'svc-'.$slug, 'icon' => $s['icon'], 'icon_color' => $s['color'],
                    'group' => $s['group'], 'url' => '/services/'.$slug, 'short_description' => $s['short'],
                    'sort_order' => ($order[$s['group']] ?? 900) + $i, 'is_active' => true,
                    'show_on_home' => false, 'show_in_catalog' => true,
                ]);

                return true;
            });
        }

        // The four home-page cards link to the matching service page and stay out of the catalogue.
        $this->once('home-service-links', function () {
            foreach (['web-design-development' => 'website-development', 'graphic-design' => 'graphic-design', 'video-editing' => 'video-editing', 'data-science-analytics' => 'data-dashboards'] as $homeSlug => $page) {
                Service::where('slug', $homeSlug)->whereNull('url')->update(['url' => '/services/'.$page, 'show_on_home' => true, 'show_in_catalog' => false]);
            }

            return true;
        });

        // 2. Service pages
        foreach ($services as $slug => $s) {
            $this->page('services/'.$slug, fn () => [
                'title' => $s['title'],
                'seo_title' => $s['seo_title'],
                'seo_description' => $s['seo_description'],
                'template' => 'full_width',
                'show_page_header' => false,
                'layout' => ServicePage::layout($s, $services),
            ], $validator);
        }

        // 3. Core pages and 4. legal drafts
        foreach (['pages' => 'published', 'legal' => 'draft'] as $file => $status) {
            foreach (require $dir.'/'.$file.'.php' as $slug => $p) {
                $this->page($slug, fn () => [
                    'title' => $p['title'],
                    'seo_title' => $p['seo_title'] ?? null,
                    'seo_description' => $p['seo_description'] ?? null,
                    'template' => $p['template'] ?? 'default',
                    'show_page_header' => ($p['template'] ?? 'default') === 'default',
                    'header_subtitle' => $p['header_subtitle'] ?? null,
                    'noindex' => $p['noindex'] ?? false,
                    'layout' => ($p['layout'])(),
                ], $validator, $status);
            }
        }

        // The pabitra.net shop only had its theme's demo products (Hair Gel, Smart Watch …). They were
        // imported as drafts once; remove them again while still untouched (no fake products in the shop).
        $this->once('remove-pabitra-demo-products', function () {
            $demo = \App\Models\Product::where('status', 'draft')->where('source_url', 'like', 'https://pabitra.net/%')->whereColumn('updated_at', 'created_at')->get();
            foreach ($demo as $p) {
                \App\Models\ImportLog::where('target_type', \App\Models\Product::class)->where('target_id', $p->id)->update(['status' => 'removed', 'notes' => 'Theme demo product — removed automatically.', 'target_id' => null]);
                $p->delete();
            }

            return true;
        });

        // 5. Menus — only while they are still the untouched originals
        $this->menus();

        Setting::put('content_seeded', array_values(array_unique($this->done)));
        if ($this->log && $this->command) {
            $this->command->info('Content created: '.implode(', ', $this->log));
        }
    }

    protected function page(string $slug, callable $data, LayoutValidator $validator, string $status = 'published'): void
    {
        $this->upgrade($slug, $data, $validator);
        $this->once('page:'.$slug, function () use ($slug, $data, $validator, $status) {
            if (Page::where('slug', $slug)->exists()) {
                return true; // you already have a page here: leave it alone
            }
            $d = $data();
            $layout = $validator->clean($d['layout']);
            Page::create(array_merge($d, [
                'slug' => $slug,
                'status' => $status,
                'color_scheme' => 'light',
                'layout' => $layout,
                'draft_layout' => null,
                'published_at' => $status === 'published' ? now() : null,
            ]));
            $this->log[] = '/'.$slug.($status === 'draft' ? ' (draft)' : '');
            Setting::put('content_fingerprints', array_merge((array) Setting::get('content_fingerprints', []), [$slug => Fingerprint::of($layout)]));

            return true;
        });
    }

    /**
     * Newer page designs: a page that is still exactly as the installer made it (its layout matches a
     * known fingerprint) gets the current design. Pages edited in the builder never match — untouched.
     */
    protected function upgrade(string $slug, callable $data, LayoutValidator $validator): void
    {
        $page = Page::where('slug', $slug)->first();
        if (! $page || $page->draft_layout) {
            return;
        }
        static $known = null;
        $known ??= require database_path('content/fingerprints.php');
        $written = (array) Setting::get('content_fingerprints', []);
        $current = Fingerprint::of($page->layout);
        $ours = array_merge($known[$slug] ?? [], (array) ($written[$slug] ?? []));
        if (! in_array($current, $ours, true)) {
            return; // edited by the owner (or never ours)
        }
        $layout = $validator->clean($data()['layout']);
        $new = Fingerprint::of($layout);
        if ($new === $current) {
            return;
        }
        $page->forceFill(['layout' => $layout])->save();
        $written[$slug] = $new;
        Setting::put('content_fingerprints', $written);
        $this->log[] = '/'.$slug.' (new design)';
    }

    /** Run $fn once per key, ever. $fn returns false to try again next time. */
    protected function once(string $key, callable $fn): void
    {
        if (in_array($key, $this->done, true)) {
            return;
        }
        if ($fn() !== false) {
            $this->done[] = $key;
        }
    }

    protected function menus(): void
    {
        $defaults = (new SettingsSeeder)->settings();

        // The menu is replaced only while it is one of the menus this installer wrote itself
        // (original default, or the Phase 2 version) — a menu you edited is never touched.
        $menu = Setting::get('menu');
        $services = ['label' => 'Services', 'url' => '/services', 'children' => [
            ['label' => 'AI Automation', 'url' => '/services/ai-automation'],
            ['label' => 'Website Development', 'url' => '/services/website-development'],
            ['label' => 'Software Development', 'url' => '/services/custom-software-development'],
            ['label' => 'Motion Graphics & Video', 'url' => '/services/motion-graphics'],
            ['label' => 'All Services', 'url' => '/services'],
        ]];
        $phase2 = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About', 'url' => '/about'],
            $services,
            ['label' => 'Portfolio', 'url' => '/#portfolio'],
            $defaults['menu'][4],
            ['label' => 'Contact Us', 'url' => '/contact'],
        ];
        $norm = fn ($m) => array_map(fn ($i) => array_filter((array) $i, fn ($v) => $v !== [] && $v !== null && $v !== ''), (array) $m);
        if ($norm($menu) == $norm($defaults['menu']) || $norm($menu) == $norm($phase2)) {
            Setting::put('menu', [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'About', 'url' => '/about'],
                $services,
                ['label' => 'Portfolio', 'url' => '/portfolio'],
                ['label' => 'Shop', 'url' => '/shop', 'children' => [
                    ['label' => 'All Products', 'url' => '/shop'],
                    ['label' => 'Cart', 'url' => '/cart'],
                    ['label' => 'My Account', 'url' => '/account'],
                ]],
                ['label' => 'Blog', 'url' => '/blog'],
                ['label' => 'Contact Us', 'url' => '/contact'],
            ]);
            $this->log[] = 'main menu';
        }

        // Home page service cards: link to the portfolio page instead of the #portfolio anchor (only untouched cards).
        $this->once('portfolio-links', function () {
            $port = (array) Setting::get('service_cards', []);
            $changed = false;
            foreach ((array) ($port['items'] ?? []) as $k => $item) {
                if (($item['url'] ?? null) === '/#portfolio') {
                    $port['items'][$k]['url'] = '/portfolio';
                    $changed = true;
                }
            }
            if ($changed) {
                Setting::put('service_cards', $port);
            }

            return true;
        });

        $footer = (array) Setting::get('footer', []);
        $changed = false;
        if (($footer['links'] ?? null) == $defaults['footer']['links']) {
            $footer['links'] = [
                ['label' => 'About us', 'url' => '/about'],
                ['label' => 'Services', 'url' => '/services'],
                ['label' => 'Our Process', 'url' => '/our-process'],
                ['label' => 'Tools & Technologies', 'url' => '/tools-and-technologies'],
                ['label' => 'Testimonials', 'url' => '/#testimonials'],
                ['label' => 'FAQ', 'url' => '/faq'],
            ];
            $changed = true;
        }
        // Phase 3: add Portfolio and Blog to the Phase 2 footer list (only if still unedited).
        if (($footer['links'] ?? null) == [
            ['label' => 'About us', 'url' => '/about'], ['label' => 'Services', 'url' => '/services'],
            ['label' => 'Our Process', 'url' => '/our-process'], ['label' => 'Tools & Technologies', 'url' => '/tools-and-technologies'],
            ['label' => 'Testimonials', 'url' => '/#testimonials'], ['label' => 'FAQ', 'url' => '/faq'],
        ]) {
            array_splice($footer['links'], 2, 0, [['label' => 'Portfolio', 'url' => '/portfolio'], ['label' => 'Blog', 'url' => '/blog'], ['label' => 'Shop', 'url' => '/shop']]);
            $changed = true;
        }
        if (($footer['support'] ?? null) == $defaults['footer']['support']) {
            $footer['support'] = [
                ['label' => 'Contact Us', 'url' => '/contact'],
                ['label' => 'Start a Project', 'url' => '/start-a-project'],
                ['label' => 'Privacy Policy', 'url' => '/privacy-policy'],
                ['label' => 'Terms & Conditions', 'url' => '/terms-and-conditions'],
                ['label' => 'Refund Policy', 'url' => '/refund-policy'],
                ['label' => 'Search', 'url' => '/search'],
            ];
            $changed = true;
        }
        if ($changed) {
            Setting::put('footer', $footer);
            $this->log[] = 'footer links';
        }
    }
}
