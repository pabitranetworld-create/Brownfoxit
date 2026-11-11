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
        $order = ['ai' => 100, 'n8n' => 150, 'software' => 200, 'creative' => 400, 'marketing' => 600];
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

        // Images downloaded by the WordPress import were not added to the media library — add them (once).
        $this->once('media-backfill-imports', function () {
            $n = 0;
            foreach (\App\Models\Post::all() as $p) {
                foreach (array_merge([$p->image], \App\Support\MediaRegistry::fromHtml($p->body)) as $img) {
                    $n += (int) (bool) \App\Support\MediaRegistry::register($img, $p->title);
                }
            }
            foreach (\App\Models\Project::all() as $p) {
                $gallery = array_map(fn ($g) => is_array($g) ? ($g['image'] ?? null) : $g, (array) $p->gallery);
                foreach (array_merge([$p->image], $gallery, \App\Support\MediaRegistry::fromHtml($p->description)) as $img) {
                    $n += (int) (bool) \App\Support\MediaRegistry::register($img, $p->title);
                }
            }
            foreach (\App\Models\Product::all() as $p) {
                $gallery = array_map(fn ($g) => is_array($g) ? ($g['image'] ?? null) : $g, (array) $p->gallery);
                foreach (array_merge([$p->image], $gallery, \App\Support\MediaRegistry::fromHtml($p->description), \App\Support\MediaRegistry::fromHtml($p->short_description)) as $img) {
                    $n += (int) (bool) \App\Support\MediaRegistry::register($img, $p->name);
                }
            }
            if ($n) {
                $this->log[] = $n.' imported image(s) added to the media library';
            }

            return true;
        });

        // SEO defaults (once): the real domain is known for the two planned installs, so the draft domain
        // (e.g. 1stdraft.brownfoxit.com) stays out of search results automatically.
        // The five main services, written the same way everywhere (hero line, footer, home slider, menu, SEO).
        $this->once('main-services-2026-11', function () {
            $line = 'AI Automation | n8n | Software & Development | Graphic & Video | Digital Marketing';
            $hero = (array) Setting::get('hero', []);
            if ($hero) {
                $hero['subtitle'] = $line;
                Setting::put('hero', $hero);
            }
            $footer = (array) Setting::get('footer', []);
            if ($footer) {
                $footer['about_text'] = $line;
                if (in_array($footer['about_title'] ?? '', ['', 'Full Stack Web Developer & Digital Solutions Expert'], true)) {
                    $footer['about_title'] = 'Next-Gen Digital Solutions Expert';
                }
                // a link into the admin panel never belongs in the public footer
                foreach (['links', 'support'] as $col) {
                    $footer[$col] = array_values(array_filter((array) ($footer[$col] ?? []), fn ($l) => ! str_contains((string) ($l['url'] ?? ''), '/admin/')));
                }
                Setting::put('footer', $footer);
            }
            $site = (array) Setting::get('site', []);
            if ($site) {
                if (str_contains((string) ($site['meta_title'] ?? ''), 'Web Design, Graphic Design')) {
                    $site['meta_title'] = 'Brown Fox IT — AI Automation, n8n, Software, Graphic & Video';
                }
                if (str_contains((string) ($site['meta_description'] ?? ''), 'Web Design & Development, Graphic Design')) {
                    $site['meta_description'] = 'Brown Fox IT by Arjun Kumar (Pabitra): AI automation, n8n workflows, software & website development, graphic design & video, and digital marketing.';
                }
                Setting::put('site', $site);
            }
            $about = (array) Setting::get('about', []);
            if ($about && in_array($about['title'] ?? '', ['Full Stack Digital Visualizer', 'Full Stack Digital Visulizer'], true)) {
                $about['title'] = 'Next-Gen Digital Solutions Expert';
                $about['intro'] = 'I’m <strong>Arjun Kumar (Pabitra)</strong>, CEO &amp; Head of Technology at Brown Fox IT — a Next-Gen Digital Solutions Expert with 15+ years of experience helping businesses build and grow their digital presence.';
                $about['body'] = 'With our team I deliver AI automation, n8n workflows, software and website development, graphic design and video, and digital marketing — combining strategy, design and technology to improve user experience, increase conversions and support business growth.';
                Setting::put('about', $about);
            }

            // Home page slider under the hero: the five main services
            Service::query()->where(fn ($q) => $q->whereNull('group')->orWhere('group', ''))
                ->whereIn('title', ['Web Design & Development', 'Graphic Design', 'Video Editing', 'Data Science & Analytics'])
                ->update(['show_on_home' => false, 'show_in_catalog' => false]);
            $cards = [
                ['ai', 'AI Automation', 'fa-solid fa-robot', 'purple', '/services/ai-automation', 'AI-powered workflows and assistants that take repetitive work off your team.'],
                ['n8n', 'n8n Automation', 'fa-solid fa-diagram-project', 'orange', '/services/n8n-automation', 'Reliable n8n workflows, cloud or self-hosted, connecting your apps, APIs and AI.'],
                ['software', 'Software & Development', 'fa-solid fa-code', 'blue', '/services/custom-software-development', 'Websites, web apps and custom business software built around how you work.'],
                ['creative', 'Graphic & Video', 'fa-solid fa-pen-ruler', 'green', '/services/graphic-design', 'Brand design, UI/UX, motion graphics and video editing that make you stand out.'],
                ['marketing', 'Digital Marketing', 'fa-solid fa-bullhorn', 'purple', '/services/digital-marketing-assets', 'Ad creatives, social content and campaign assets that are on-brand and ready to publish.'],
            ];
            foreach ($cards as $n => [$group, $title, $icon, $color, $url, $text]) {
                Service::updateOrCreate(['slug' => 'home-'.$group], [
                    'title' => $title, 'group' => $group, 'icon' => $icon, 'icon_color' => $color, 'url' => $url,
                    'short_description' => $text, 'sort_order' => $n + 1, 'is_active' => true, 'show_on_home' => true, 'show_in_catalog' => false,
                ]);
            }

            // Services menu: only when it is still the menu the installer made
            $menu = (array) Setting::get('menu', []);
            foreach ($menu as &$item) {
                $labels = array_map(fn ($c) => $c['label'] ?? '', (array) ($item['children'] ?? []));
                if (($item['url'] ?? '') === '/services' && $labels === ['AI Automation', 'Website Development', 'Software Development', 'Motion Graphics & Video', 'All Services']) {
                    $item['children'] = [
                        ['label' => 'AI Automation', 'url' => '/services/ai-automation'],
                        ['label' => 'n8n Automation', 'url' => '/services/n8n-automation'],
                        ['label' => 'Software & Development', 'url' => '/services#software'],
                        ['label' => 'Graphic & Video', 'url' => '/services#creative'],
                        ['label' => 'Digital Marketing', 'url' => '/services#marketing'],
                        ['label' => 'All Services', 'url' => '/services'],
                    ];
                }
            }
            unset($item);
            Setting::put('menu', $menu);

            return true;
        });

        // Team (from the pabitra.net "Our Professional Team" section); photos are copied to this site.
        $this->once('team-members', function () {
            if (\App\Models\TeamMember::query()->exists()) {
                return true;
            }
            $team = [
                ['Arjun Kumar (Pabitra)', 'CEO & Head of Technology', 'https://www.pabitra.net/wp-content/uploads/2026/05/Arjun-Kumar-Pabitra-.jpg', 'arjun-kumar-pabitra.jpg', [['type' => 'linkedin', 'url' => 'https://www.linkedin.com/in/pabitra-sarkar/'], ['type' => 'behance', 'url' => 'https://www.behance.net/pabitra_sarkar'], ['type' => 'dribbble', 'url' => 'https://dribbble.com/Pabitras_Galary']]],
                ['Placid Rozario', 'Senior Graphic Designer', 'https://www.pabitra.net/wp-content/uploads/2026/05/Placid-Rozario-1-530x530-1.jpg', 'placid-rozario.jpg', []],
                ['Andress Gomes', 'Senior Graphic Designer & Video Editor', 'https://www.pabitra.net/wp-content/uploads/2026/05/Dip-Gomes-530x530-1.jpg', 'andress-gomes.jpg', []],
                ['Arpana Sarkar', 'Web Application Expert', 'https://www.pabitra.net/wp-content/uploads/2026/05/WhatsApp-Image-2024-03-21-at-22.48.09_6cbb48e9-copy-530x576-1.jpg', 'arpana-sarkar.jpg', []],
            ];
            foreach ($team as $n => [$name, $role, $src, $file, $links]) {
                $photo = null;
                try {
                    $res = \Illuminate\Support\Facades\Http::timeout(20)->get($src);
                    if ($res->successful() && str_starts_with((string) $res->header('Content-Type'), 'image/')) {
                        @mkdir(public_path('uploads/team'), 0755, true);
                        file_put_contents(public_path('uploads/team/'.$file), $res->body());
                        $photo = 'uploads/team/'.$file;
                        \App\Support\MediaRegistry::register($photo, $name);
                    }
                } catch (\Throwable) {
                    // photo can be added later in Admin → Team members
                }
                \App\Models\TeamMember::create(['name' => $name, 'role' => $role, 'photo' => $photo, 'links' => $links, 'show_on_home' => true, 'sort_order' => $n + 1, 'is_active' => true]);
            }

            return true;
        });

        // Floating chat button: start with the contact details the site already has (Messenger etc. are added in Admin).
        $this->once('chat-widget-defaults', function () {
            if (Setting::get('chat') !== null) {
                return true;
            }
            $channels = [];
            if ($wa = Setting::get('site.whatsapp_url')) {
                $channels[] = ['type' => 'whatsapp', 'value' => $wa, 'label' => 'WhatsApp'];
            }
            if ($phone = Setting::get('site.phone')) {
                $channels[] = ['type' => 'phone', 'value' => $phone, 'label' => 'Call us'];
            }
            if ($email = Setting::get('site.email')) {
                $channels[] = ['type' => 'email', 'value' => $email, 'label' => 'Email'];
            }
            Setting::put('chat', ['enabled' => true, 'cta' => 'Chat with us', 'position' => 'right', 'channels' => $channels]);

            return true;
        });

        $this->once('seo-defaults', function () {
            $seo = (array) Setting::get('seo', []);
            $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
            $prod = match (true) {
                str_ends_with($host, 'brownfoxit.com') => 'brownfoxit.com',
                str_ends_with($host, 'pabitra.net') => 'pabitra.net',
                default => '',
            };
            $seo += [
                'production_host' => $prod, 'visibility' => 'auto', 'force_https' => true, 'title_separator' => '|',
                'org_name' => Setting::get('site.name') ?: 'Brown Fox IT', 'contact_email' => Setting::get('site.email'),
                'locale' => 'en_US', 'local_business' => false,
            ];
            Setting::put('seo', $seo);
            $int = (array) Setting::get('seo_integrations', []);
            Setting::put('seo_integrations', $int + ['consent_required' => true, 'privacy_url' => '/privacy-policy']);

            return true;
        });

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
                'color_scheme' => 'dark',
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
            if ($page->color_scheme !== 'dark') {
                $page->forceFill(['color_scheme' => 'dark'])->save(); // the site's dark technology look
                $this->log[] = '/'.$slug.' (dark)';
            }

            return;
        }
        $page->forceFill(['layout' => $layout, 'color_scheme' => 'dark'])->save();
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
