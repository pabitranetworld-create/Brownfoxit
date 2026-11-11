<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Called by GitHub Actions right after new files are uploaded:
 *   POST /_deploy   with header  X-Deploy-Token: <DEPLOY_TOKEN from .env>
 * Runs new database migrations and refreshes Laravel's caches.
 */
class DeployController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('site.deploy_token');

        if ($token === '' || ! hash_equals($token, (string) $request->header('X-Deploy-Token'))) {
            abort(404); // look like any missing page to everyone else
        }

        @set_time_limit(300);
        $output = [];

        if ($request->input('action') === 'export-page') {
            // Only the PUBLISHED version (already public on the site) of one page.
            $page = \App\Models\Page::where('slug', (string) $request->input('slug'))->where('status', 'published')->firstOrFail();

            return response()->json(['slug' => $page->slug, 'title' => $page->title, 'template' => $page->template, 'show_page_header' => $page->show_page_header, 'custom_head' => $page->custom_head, 'custom_css' => $page->custom_css, 'custom_js' => $page->custom_js, 'layout' => $page->layout]);
        }

        if ($request->input('action') === 'import') {
            // Copy blog / portfolio (published) and products (draft) from an old WordPress site. Re-runnable.
            $site = (string) $request->input('slug');
            abort_unless(preg_match('#^https://[a-z0-9.\-]+/?$#i', $site), 422);
            $result = (new \App\Support\WordPressImporter($site))->all((bool) $request->input('products', true), 230);
            $result['log'] = \App\Models\ImportLog::where('source', rtrim($site, '/'))->orderBy('kind')->get(['kind', 'status', 'title', 'target_id', 'notes'])->toArray();

            return response()->json($result);
        }

        if ($request->input('action') === 'inspect') {
            // read-only snapshot of content settings, for checking wording across the site
            $keys = array_filter(array_map('trim', explode(',', (string) $request->input('slug')))) ?: ['hero', 'about', 'menu', 'footer', 'site', 'counters', 'service_cards', 'chat'];
            $out = [];
            foreach ($keys as $k) {
                $out['setting:'.$k] = \App\Models\Setting::get($k);
            }
            $out['services'] = \App\Models\Service::query()->orderBy('sort_order')->get()->map(fn ($s) => $s->only(['id', 'title', 'url', 'group', 'icon', 'short_description', 'show_on_home', 'show_on_services', 'is_active', 'sort_order']))->all();
            $out['pages'] = \App\Models\Page::query()->get(['id', 'slug', 'status', 'title'])->map(fn ($p) => $p->only(['id', 'slug', 'status', 'title']))->all();
            $out['fingerprints'] = \App\Models\Setting::get('content_fingerprints');
            $out['current_fp'] = \App\Models\Page::query()->whereIn('slug', ['about', 'contact', 'services'])->get()->mapWithKeys(fn ($p) => [$p->slug => \App\Content\Fingerprint::of($p->layout).($p->draft_layout ? ' (has draft)' : '')])->all();

            return response()->json($out, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        if ($request->input('action') === 'copy-uploads') {
            // Copy public/uploads from another Laravel copy of this site on the same hosting account
            // (e.g. the draft domain). Without "slug": only lists candidates. Never overwrites existing files.
            $home = dirname(base_path());
            $candidates = [];
            foreach (array_merge(glob($home.'/*/public/uploads', GLOB_ONLYDIR) ?: [], glob($home.'/*/*/public/uploads', GLOB_ONLYDIR) ?: []) as $dir) {
                if (realpath($dir) === realpath(public_path('uploads'))) {
                    continue;
                }
                $n = rescue(fn () => iterator_count(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS))), -1, false);
                $candidates[substr($dir, strlen($home) + 1)] = $n;
            }
            $pick = (string) $request->input('slug');
            if ($pick === '' || ! isset($candidates[$pick])) {
                return response()->json(['home' => $home, 'candidates' => $candidates, 'hint' => 'run again with slug = one of the candidate keys']);
            }
            $src = $home.'/'.$pick;
            $copied = $skipped = 0;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS)) as $f) {
                $rel = substr($f->getPathname(), strlen($src) + 1);
                $dest = public_path('uploads/'.$rel);
                if (is_file($dest) || str_contains($rel, '..') || preg_match('/\.(php\d?|phtml|phar)$/i', $rel)) {
                    $skipped++;
                    continue;
                }
                @mkdir(dirname($dest), 0755, true);
                copy($f->getPathname(), $dest) ? $copied++ : $skipped++;
            }
            $missing = \App\Models\Media::query()->get(['path'])->filter(fn ($m) => ! is_file(public_path($m->path)))->count();

            return response()->json(['from' => $pick, 'copied' => $copied, 'skipped' => $skipped, 'media_still_missing' => $missing]);
        }

        if ($request->input('action') === 'selftest') {
            return response()->json($this->selfTest());
        }

        try {
            foreach ([
                ['migrate', ['--force' => true]],
                ['db:seed', ['--class' => 'Database\\Seeders\\ContentSeeder', '--force' => true]], // adds new pages only, never overwrites
                ['optimize:clear', []],
                ['optimize', []],
            ] as [$command, $args]) {
                Artisan::call($command, $args);
                $output[$command] = trim(Artisan::output());
            }
        } catch (Throwable $e) {
            Log::error('Deploy hook failed: '.$e->getMessage());

            return response()->json(['ok' => false, 'error' => $e->getMessage(), 'output' => $output], 500);
        }

        Log::info('Deploy hook finished', ['commit' => $request->input('commit')]);

        return response()->json(['ok' => true, 'output' => $output]);
    }

    /**
     * Runs the page-builder save/publish code on THIS server (with a throw-away page)
     * and reports environment details and recent errors, so problems that only
     * happen on the hosting can be diagnosed from GitHub Actions.
     */
    protected function selfTest(): array
    {
        $report = [
            'php' => PHP_VERSION,
            'app_url' => config('app.url'),
            'request_host' => request()->getHost(),
            'request_secure' => request()->secure(),
            'proxy_proto' => request()->header('X-Forwarded-Proto'),
            'home' => rescue(function () {
                $r = \App\Seo\PageFetcher::get('/');
                preg_match_all('#<link rel="stylesheet" href="([^"]+)"#', $r['html'], $css);
                preg_match('#<link rel="canonical" href="([^"]+)"#', $r['html'], $can);
                preg_match('#<meta name="robots" content="([^"]+)"#', $r['html'], $rob);

                return ['status' => $r['status'], 'location' => $r['location'], 'bytes' => strlen($r['html']), 'css' => array_slice($css[1], 0, 6), 'canonical' => $can[1] ?? null, 'robots' => $rob[1] ?? null, 'error' => $r['error'] ?? null];
            }, null, false),
            'seo_cfg' => rescue(fn () => ['production_host' => \App\Seo\Seo::cfg('production_host', ''), 'force_https' => \App\Seo\Seo::cfg('force_https', true), 'visibility' => \App\Seo\Seo::cfg('visibility', 'auto')], null, false),
            'http' => rescue(function () {
                $out = [];
                foreach (['https://brownfoxit.com/', 'https://www.brownfoxit.com/', 'http://brownfoxit.com/', 'https://brownfoxit.com/assets/css/style.css', 'https://www.brownfoxit.com/assets/css/style.css', 'https://brownfoxit.com/vendor/fontawesome/css/all.min.css', 'https://brownfoxit.com/assets/js/main.js'] as $u) {
                    $out[$u] = rescue(function () use ($u) {
                        $res = \Illuminate\Support\Facades\Http::withOptions(['allow_redirects' => false, 'verify' => false])->timeout(15)->get($u);
                        $body = $res->body();
                        preg_match_all('#<link rel="stylesheet" href="([^"]+)"#', $body, $css);

                        return ['status' => $res->status(), 'type' => $res->header('Content-Type'), 'location' => $res->header('Location'), 'bytes' => strlen($body), 'server' => $res->header('Server'), 'cache' => $res->header('X-LiteSpeed-Cache') ?: $res->header('X-Cache'), 'css' => array_slice($css[1] ?? [], 0, 8), 'head' => substr(preg_replace('/\s+/', ' ', $body), 0, 160)];
                    }, fn ($e) => ['error' => substr($e->getMessage(), 0, 200)], false);
                }

                return $out;
            }, null, false),
            'media' => rescue(function () {
                $rows = \App\Models\Media::query()->latest('id')->get(['id', 'path']);
                $missing = $rows->filter(fn ($m) => ! is_file(public_path($m->path)));
                $disk = is_dir(public_path('uploads')) ? iterator_count(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(public_path('uploads'), \FilesystemIterator::SKIP_DOTS))) : -1;

                return ['rows' => $rows->count(), 'missing_files' => $missing->count(), 'missing_sample' => $missing->take(5)->pluck('path'), 'present_sample' => $rows->diff($missing)->take(3)->pluck('path'), 'files_on_disk' => $disk, 'uploads_dirs' => is_dir(public_path('uploads')) ? array_values(array_diff(scandir(public_path('uploads')), ['.', '..'])) : []];
            }, fn ($e) => ['error' => $e->getMessage()], false),
            'files' => ['root_htaccess' => is_file(base_path('.htaccess')), 'public_htaccess' => is_file(public_path('.htaccess')), 'style_css' => is_file(public_path('assets/css/style.css')), 'uploads' => is_dir(public_path('uploads')), 'wp_leftovers' => is_file(base_path('wp-config.php')) || is_dir(base_path('wp-content')), 'base' => base_path()],
            'db' => rescue(fn () => \Illuminate\Support\Facades\DB::select('select version() as v')[0]->v, 'n/a', false),
            'env' => app()->environment(),
            'config_cached' => app()->configurationIsCached(),
            'routes_cached' => app()->routesAreCached(),
            'limits' => ['post_max_size' => ini_get('post_max_size'), 'upload_max_filesize' => ini_get('upload_max_filesize'), 'memory_limit' => ini_get('memory_limit'), 'max_input_vars' => ini_get('max_input_vars')],
            'session' => config('session.driver'),
            'steps' => [],
        ];

        $page = null;
        try {
            $admin = \App\Models\User::where('role', 'admin')->first();
            auth()->login($admin);
            $page = \App\Models\Page::create(['title' => 'Selftest', 'slug' => 'selftest-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)), 'status' => 'draft', 'layout' => ['version' => 1, 'sections' => []]]);
            $report['steps'][] = 'page created #'.$page->id;

            $layout = ['version' => 1, 'sections' => [['id' => 'sectest1', 'settings' => [], 'columns' => [['id' => 'coltest1', 'settings' => ['w' => 100], 'widgets' => [
                ['id' => 'wtest0001', 'type' => 'heading', 'data' => ['text' => 'Selftest'], 'settings' => []],
                ['id' => 'wtest0002', 'type' => 'html', 'data' => ['code' => '<div>html</div><script>console.log(1)</script>'], 'settings' => []],
            ]]]]]];
            $ctl = app(\App\Http\Controllers\Admin\PageController::class);
            foreach (['save', 'publish'] as $action) {
                $req = \Illuminate\Http\Request::create('/admin/pages/'.$page->id.'/'.$action, 'POST', [
                    'layout' => json_encode($layout),
                    'page' => ['title' => 'Selftest', 'slug' => $page->slug, 'show_page_header' => '1', 'noindex' => '0'],
                ]);
                $req->setUserResolver(fn () => $admin);
                app()->instance('request', $req);
                $res = $ctl->{$action}($req, $page->fresh(), app(\App\PageBuilder\LayoutValidator::class));
                $report['steps'][] = $action.': HTTP '.$res->getStatusCode().' '.\Illuminate\Support\Str::limit($res->getContent(), 200);
            }
            $view = rescue(fn () => strlen(view('pages.show', ['page' => $page->fresh(), 'layout' => $page->fresh()->layout, 'preview' => false, 'editor' => false])->render()).' bytes', fn ($e) => 'render error: '.$e->getMessage(), false);
            $report['steps'][] = 'render public page: '.$view;
            $report['ok'] = true;
        } catch (Throwable $e) {
            $report['ok'] = false;
            $report['error'] = get_class($e).': '.$e->getMessage().' @ '.str_replace(base_path(), '', $e->getFile()).':'.$e->getLine();
        } finally {
            $page?->delete();
            auth()->logout();
        }

        // Structure of the real pages (no content, only sizes/flags) to find display problems.
        $report['pages'] = rescue(fn () => \App\Models\Page::where('is_template', false)->orderBy('id')->get()->map(function ($p) {
            $info = function ($layout) {
                $types = []; $html = [];
                $walk = function ($node) use (&$walk, &$types, &$html) {
                    if (! is_array($node)) { return; }
                    if (isset($node['type']) && is_string($node['type'])) {
                        $types[] = $node['type'];
                        if ($node['type'] === 'html') {
                            $c = (string) ($node['data']['code'] ?? '');
                            $html[] = ['code' => strlen($c), 'css' => strlen((string) ($node['data']['css'] ?? '')), 'js' => strlen((string) ($node['data']['js'] ?? '')), 'wrap' => $node['data']['wrap'] ?? null, 'full_doc' => (bool) preg_match('/<html|<body|<!doctype/i', $c), 'anim' => $node['settings']['anim'] ?? null];
                        }
                    }
                    foreach ($node as $v) { $walk($v); }
                };
                $walk($layout ?? []);

                return ['sections' => count($layout['sections'] ?? []), 'types' => array_count_values($types), 'html' => $html];
            };
            $rendered = rescue(function () use ($p) {
                $out = view(\App\PageBuilder\Theme::view($p, $p->layout ?? []), ['page' => $p, 'layout' => $p->layout, 'preview' => false, 'editor' => false])->render();
                preg_match('#<main[^>]*>(.*)</main>#s', $out, $m);

                return ['bytes' => strlen($out), 'main_text_chars' => strlen(trim(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<(script|style)\b.*?</\1>#is', '', $m[1] ?? ''))))), 'body_class' => preg_match('#<body[^>]*class="([^"]*)"#', $out, $b) ? $b[1] : null];
            }, fn ($e) => ['error' => \Illuminate\Support\Str::limit($e->getMessage(), 300)], false);

            return ['id' => $p->id, 'slug' => $p->slug, 'status' => $p->status, 'template' => $p->template, 'unpublished_changes' => $p->hasUnpublishedChanges(), 'custom' => ['head' => strlen((string) $p->custom_head), 'css' => strlen((string) $p->custom_css), 'js' => strlen((string) $p->custom_js)], 'published' => $info($p->layout), 'draft' => $info($p->draft_layout), 'render' => $rendered];
        })->all(), fn ($e) => 'error: '.$e->getMessage(), false);

        // Last errors from the log (first line of each, shortened).
        $log = storage_path('logs/laravel.log');
        $report['recent_errors'] = is_file($log)
            ? collect(preg_split('/\R/', (string) @file_get_contents($log, false, null, max(0, filesize($log) - 300000)) ?: ''))
                ->filter(fn ($l) => preg_match('/^\[\d{4}-\d\d-\d\d [\d:]+\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY)/', $l))
                ->map(fn ($l) => \Illuminate\Support\Str::limit($l, 400))->take(-12)->values()->all()
            : ['(no log file)'];

        return $report;
    }
}
