<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SeoController;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SeoAudit;
use App\Models\SeoMeta;
use App\Seo\Auditor;
use App\Seo\PageFetcher;
use App\Seo\Seo;
use App\Seo\StructuredData;
use App\Seo\Subjects;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoAdminController extends Controller
{
    protected const STOP = ['the', 'and', 'for', 'with', 'you', 'your', 'our', 'are', 'that', 'this', 'from', 'can', 'will', 'into', 'have', 'has', 'not', 'but', 'all', 'more', 'what', 'how', 'who', 'its', 'it’s', 'out', 'use', 'any', 'also', 'one', 'they', 'their', 'them', 'when', 'than', 'then', 'each', 'about', 'which', 'over', 'such', 'only', 'other', 'some', 'like', 'just', 'get', 'was', 'were', 'been', 'being', 'very', 'much', 'many', 'most', 'need', 'make', 'work', 'works', 'well', 'way'];

    /** ---------- Dashboard ---------- */
    public function index(): View
    {
        $rows = $this->rows();
        $audits = SeoAudit::all()->keyBy(fn ($a) => $a->subject_type.':'.$a->subject_key);
        $public = array_filter($rows, fn ($r) => $r['public']);
        $indexable = array_filter($public, fn ($r) => $r['reason'] === null);
        $checked = $audits->filter(fn ($a) => $a->checked_at);

        $dupTitles = $checked->whereNotNull('title')->groupBy(fn ($a) => mb_strtolower($a->title))->filter(fn ($g) => $g->count() > 1);
        $dupDescs = $checked->whereNotNull('description')->groupBy(fn ($a) => mb_strtolower($a->description))->filter(fn ($g) => $g->count() > 1);

        $redirectProblems = [];
        foreach (Redirect::where('is_active', true)->get() as $r) {
            [$final, $hops, $loop] = Redirect::follow($r->to_url, $r->from_path);
            if ($loop) {
                $redirectProblems[] = [$r, 'loop'];
            } elseif ($hops > 0) {
                $redirectProblems[] = [$r, 'chain → '.$final];
            }
        }

        $sitemap = [];
        foreach (SeoController::SECTIONS as $s) {
            $sitemap[$s] = rescue(fn () => count(app(SeoController::class)->entries($s)), null, false);
        }

        $attention = $checked->filter(fn ($a) => collect($a->issues)->whereIn('level', ['error', 'warn'])->isNotEmpty())
            ->sortByDesc(fn ($a) => collect($a->issues)->where('level', 'error')->count() * 10 + collect($a->issues)->where('level', 'warn')->count())->take(15);

        $recent = collect($rows)->filter(fn ($r) => $r['modified'])->sortByDesc('modified')->take(8);

        return view('admin.seo.index', [
            'counts' => [
                'all' => count($rows), 'public' => count($public), 'indexable' => count($indexable), 'noindex' => count($public) - count($indexable),
                'checked' => $checked->count(),
                'missing_title' => $checked->filter(fn ($a) => collect($a->issues)->whereIn('code', ['title_count'])->isNotEmpty())->count(),
                'missing_desc' => $checked->filter(fn ($a) => collect($a->issues)->where('code', 'desc_missing')->isNotEmpty())->count(),
                'canonical' => $checked->filter(fn ($a) => collect($a->issues)->where('code', 'canonical_count')->isNotEmpty())->count(),
                'img_alt' => $checked->sum('images_without_alt'),
                'h1' => $checked->filter(fn ($a) => $a->h1_count !== 1 && $a->status_code === 200)->count(),
                'schema' => $checked->where('schema_ok', false)->count(),
                'errors' => $checked->filter(fn ($a) => $a->status_code !== 200)->count(),
            ],
            'dupTitles' => $dupTitles, 'dupDescs' => $dupDescs,
            'broken' => (array) Cache::get('seo_broken_links', []), 'linksChecked' => Cache::get('seo_links_checked_at'),
            'redirectProblems' => $redirectProblems, 'sitemap' => $sitemap,
            'attention' => $attention, 'rowsByKey' => collect($rows)->keyBy('id'), 'recent' => $recent,
            'lastAudit' => $checked->max('checked_at'),
            'visible' => Seo::siteIndexable(), 'production' => Seo::cfg('production_host'),
        ]);
    }

    /** Every public URL with its SEO state. */
    public function pages(Request $request): View
    {
        $rows = $this->rows();
        $audits = SeoAudit::all()->keyBy(fn ($a) => $a->subject_type.':'.$a->subject_key);
        $type = $request->query('type');
        $filter = $request->query('show');
        $rows = array_filter($rows, function ($r) use ($type, $filter, $audits) {
            if ($type && $r['type'] !== $type) {
                return false;
            }
            $a = $audits[$r['id']] ?? null;

            return match ($filter) {
                'problems' => $a && collect($a->issues)->whereIn('level', ['error', 'warn'])->isNotEmpty(),
                'noindex' => $r['public'] && $r['reason'] !== null,
                'drafts' => ! $r['public'],
                default => true,
            };
        });

        return view('admin.seo.pages', ['rows' => $rows, 'audits' => $audits, 'type' => $type, 'show' => $filter]);
    }

    protected function rows(): array
    {
        $out = [];
        foreach (Subjects::all() as [$type, $key, $s]) {
            $meta = SeoMeta::for($type, $key);
            $cols = Subjects::columns($type);
            $out[] = [
                'id' => $type.':'.$key, 'type' => $type, 'key' => $key, 'label' => Subjects::label($s), 'path' => Subjects::path($s),
                'public' => Subjects::isPublic($s), 'reason' => Subjects::notIndexableReason($s),
                'has_title' => (bool) ($meta?->title ?: (isset($cols['title']) && ! is_string($s) ? $s->{$cols['title']} : null)),
                'has_desc' => (bool) ($meta?->description ?: (isset($cols['description']) && ! is_string($s) ? $s->{$cols['description']} : null)),
                'keyword' => $meta?->focus_keyword,
                'modified' => Subjects::lastModified($s),
            ];
        }

        return $out;
    }

    /** ---------- Edit one page's SEO ---------- */
    public function edit(string $type, string $key): View
    {
        [$subject, $meta, $values] = $this->load($type, $key);
        $path = Subjects::path($subject);

        // What a visitor / Google gets right now (one render of the page)
        $res = Subjects::isPublic($subject) ? PageFetcher::get($path) : ['status' => 0, 'html' => ''];
        $analysis = $res['status'] === 200 ? Auditor::analyse($res, $path) : null;
        if ($analysis) {
            SeoAudit::updateOrCreate(['subject_type' => $type, 'subject_key' => $key], $analysis + ['url' => $path, 'checked_at' => now()]);
        }
        $text = $res['status'] === 200 ? $this->visibleText($res['html']) : '';

        return view('admin.seo.edit', [
            'type' => $type, 'key' => $key, 'subject' => $subject, 'meta' => $meta, 'v' => $values, 'path' => $path,
            'label' => Subjects::label($subject), 'typeLabel' => Subjects::TYPES[$type][0], 'editUrl' => Subjects::editUrl($subject),
            'public' => Subjects::isPublic($subject), 'reason' => Subjects::notIndexableReason($subject),
            'resolved' => $this->resolvedFor($subject), 'analysis' => $analysis,
            'keywordChecks' => $this->keywordChecks($values['focus_keyword'] ?? '', $res['html'] ?? '', $text, $path),
            'suggestions' => $this->phraseSuggestions($text, $values['focus_keyword'] ?? ''),
            'linkIdeas' => $this->linkIdeas($type, $key, $subject, $analysis['links'] ?? [], $values['focus_keyword'] ?? ''),
            'canSlug' => ! is_string($subject) && isset($subject->slug) && ! ($subject instanceof Page && $subject->slug === 'home'),
            'base' => Seo::base(),
        ]);
    }

    public function update(Request $request, string $type, string $key): RedirectResponse
    {
        [$subject] = $this->load($type, $key);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'canonical' => ['nullable', 'string', 'max:500', 'regex:#^(https://[^\s]+|/[^\s]*)$#'],
            'noindex' => ['nullable', 'boolean'], 'nofollow' => ['nullable', 'boolean'],
            'robots_extra' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9:,\- ]*$/i'],
            'og_title' => ['nullable', 'string', 'max:255'], 'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:255'], 'og_image_alt' => ['nullable', 'string', 'max:255'],
            'twitter_card' => ['nullable', 'in:summary,summary_large_image'],
            'focus_keyword' => ['nullable', 'string', 'max:120'], 'secondary_keywords' => ['nullable', 'string', 'max:500'],
            'schema_override' => ['nullable', 'string', 'max:20000'],
            'breadcrumb_label' => ['nullable', 'string', 'max:120'], 'hide_breadcrumbs' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'slug' => ['nullable', 'string', 'max:190'],
        ], ['canonical.regex' => 'Canonical must start with https:// or with /.']);

        [$ok, $schema] = StructuredData::validateOverride($data['schema_override'] ?? null);
        if (! $ok) {
            return back()->withInput()->withErrors(['schema_override' => $schema]);
        }
        $data['schema_override'] = $schema;
        foreach (['noindex', 'nofollow', 'hide_breadcrumbs'] as $b) {
            $data[$b] = $request->boolean($b);
        }
        // Share image: library choice or a fresh upload (checked like every other image upload)
        $form = app(\App\Admin\FormData::class);
        $imgField = [['key' => 'og_image', 'label' => 'Share image', 'type' => 'image']];
        $imgIn = ['og_image' => $data['og_image'] ?? null];
        $imgFiles = ['og_image' => $request->file('og_image_file')];
        if (! $form->validate($imgField, $imgIn, $imgFiles)) {
            return back()->withInput()->withErrors($form->errors);
        }
        $data['og_image'] = $form->values($imgField, $imgIn, $imgFiles)['og_image'] ?? null;

        // URL change → validated; the observer adds the 301 redirect from the old address.
        if (! is_string($subject) && filled($data['slug'] ?? null) && $data['slug'] !== $subject->slug) {
            $slug = trim(Str::lower($data['slug']), '/');
            $pattern = $subject instanceof Page ? Page::SLUG_PATTERN : '[a-z0-9]+(?:-[a-z0-9]+)*';
            if (! preg_match('#^'.$pattern.'$#', $slug)) {
                return back()->withInput()->withErrors(['slug' => 'Use lower-case letters, numbers and hyphens'.($subject instanceof Page ? ' (and up to two "/").' : '.')]);
            }
            if ($subject instanceof Page && in_array(explode('/', $slug)[0], Page::RESERVED, true)) {
                return back()->withInput()->withErrors(['slug' => 'This address is reserved by the system.']);
            }
            if ($subject::where('slug', $slug)->whereKeyNot($subject->getKey())->exists()) {
                return back()->withInput()->withErrors(['slug' => 'Another item already uses this address.']);
            }
            $subject->slug = $slug;
            $subject->save();
        }
        unset($data['slug']);

        // Values the content table stores itself stay there (same fields as in the content editor).
        $cols = Subjects::columns($type);
        if (! is_string($subject) && $cols) {
            foreach ($cols as $field => $col) {
                $subject->{$col} = $field === 'noindex' ? $data['noindex'] : ($data[$field] ?? null ?: null);
            }
            $subject->save();
        }

        SeoMeta::updateOrCreate(['subject_type' => $type, 'subject_key' => $key], $data);
        SeoController::flush();

        return redirect()->route('admin.seo.edit', [$type, $key])->with('status', 'SEO সেভ হয়েছে।');
    }

    protected function load(string $type, string $key): array
    {
        abort_unless(isset(Subjects::TYPES[$type]), 404);
        $subject = Subjects::find($type, $key);
        abort_if($subject === null, 404);
        $meta = SeoMeta::where('subject_type', $type)->where('subject_key', $key)->first();
        $values = $meta ? $meta->toArray() : [];
        foreach (Subjects::columns($type) as $field => $col) {
            $values[$field] = $subject->{$col};
        }
        $values['slug'] = is_string($subject) ? null : ($subject->slug ?? null);

        return [$subject, $meta, $values];
    }

    protected function resolvedFor(Model|string $subject): array
    {
        $seo = new Seo;
        $seo->subject($subject)->set(['canonical' => Subjects::path($subject)]);
        $r = $seo->resolve();
        $r['path'] = Subjects::path($subject);

        return $r;
    }

    protected function visibleText(string $html): string
    {
        $html = preg_replace('#<(script|style|header|footer|nav|noscript)\b.*?</\1>#is', ' ', $html);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /** Where the focus keyword appears. Editorial guidance only — not a ranking formula. */
    protected function keywordChecks(string $kw, string $html, string $text, string $path): array
    {
        $kw = trim(mb_strtolower($kw));
        if ($kw === '' || $html === '') {
            return [];
        }
        preg_match('#<title>(.*?)</title>#is', $html, $t);
        preg_match('#<meta name="description" content="([^"]*)"#i', $html, $d);
        preg_match_all('#<h1\b[^>]*>(.*?)</h1>#is', $html, $h1);
        preg_match_all('#<h2\b[^>]*>(.*?)</h2>#is', $html, $h2);
        $has = fn ($s) => str_contains(mb_strtolower(html_entity_decode(strip_tags((string) $s), ENT_QUOTES | ENT_HTML5)), $kw);
        $first = mb_substr(mb_strtolower($text), 0, 1200);

        return [
            ['In the SEO title', $has($t[1] ?? '')],
            ['In the meta description', $has($d[1] ?? '')],
            ['In the main heading (H1)', $has(implode(' ', $h1[1] ?? []))],
            ['In at least one sub-heading (H2)', $has(implode(' ', $h2[1] ?? []))],
            ['Near the start of the text', str_contains($first, $kw)],
            ['In the address (URL)', str_contains(str_replace('-', ' ', $path), str_replace('-', ' ', Str::slug($kw, ' ')))],
            ['Mentioned in the text', substr_count(mb_strtolower($text), $kw).' time(s)'],
        ];
    }

    /** Frequent two/three-word phrases of the page — ideas for related keywords (from the page itself, no search-volume data). */
    protected function phraseSuggestions(string $text, string $kw): array
    {
        $words = preg_split('/[^\p{L}\p{N}\-]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $count = [];
        $n = count($words);
        for ($i = 0; $i < $n - 1; $i++) {
            foreach ([2, 3] as $len) {
                $slice = array_slice($words, $i, $len);
                if (count($slice) < $len || in_array($slice[0], self::STOP, true) || in_array(end($slice), self::STOP, true) || array_filter($slice, fn ($w) => mb_strlen($w) < 3 || is_numeric($w))) {
                    continue;
                }
                $p = implode(' ', $slice);
                $count[$p] = ($count[$p] ?? 0) + 1;
            }
        }
        arsort($count);
        $kw = mb_strtolower(trim($kw));

        return array_slice(array_keys(array_filter($count, fn ($c, $p) => $c >= 2 && $p !== $kw, ARRAY_FILTER_USE_BOTH)), 0, 12);
    }

    /** Published pages about the same topic that this page does not link to yet. */
    protected function linkIdeas(string $type, string $key, Model|string $subject, array $links, string $kw): array
    {
        $terms = array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($kw.' '.Subjects::label($subject))), fn ($w) => mb_strlen($w) > 3 && ! in_array($w, self::STOP, true));
        if (! $terms) {
            return [];
        }
        $linked = array_flip(array_map(fn ($l) => Redirect::normalize($l), $links));
        $ideas = [];
        foreach (Subjects::all(true) as [$t, $k, $s]) {
            if (($t === $type && $k === $key) || Subjects::notIndexableReason($s) !== null) {
                continue;
            }
            $path = Subjects::path($s);
            if (isset($linked[Redirect::normalize($path)])) {
                continue;
            }
            $label = mb_strtolower(Subjects::label($s));
            $score = count(array_filter($terms, fn ($w) => str_contains($label, $w)));
            if ($score > 0) {
                $ideas[] = ['label' => Subjects::label($s), 'path' => $path, 'score' => $score];
            }
        }
        usort($ideas, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($ideas, 0, 8);
    }

    /** ---------- Audit (runs in small steps from the dashboard) ---------- */
    public function audit(Request $request): JsonResponse
    {
        @set_time_limit(120);
        $all = array_values(array_filter(Subjects::all(true), fn ($r) => ! ($r[2] instanceof Page && $r[2]->slug === 'home')));
        $offset = max(0, (int) $request->input('offset', 0));
        $batch = array_slice($all, $offset, 4);
        $done = [];
        foreach ($batch as [$type, $key, $s]) {
            $a = Auditor::check($type, $key, Subjects::path($s));
            $done[] = Subjects::path($s).' → '.$a->status_code.' ('.count((array) $a->issues).' notes)';
        }
        if ($offset === 0) {
            // forget results of pages that no longer exist / are no longer public
            $ids = array_map(fn ($r) => $r[0].':'.$r[1], $all);
            SeoAudit::all()->reject(fn ($a) => in_array($a->subject_type.':'.$a->subject_key, $ids, true))->each->delete();
        }

        return response()->json(['total' => count($all), 'next' => $offset + count($batch), 'done' => $done, 'finished' => $offset + count($batch) >= count($all)]);
    }

    public function auditLinks(Request $request): JsonResponse
    {
        @set_time_limit(120);
        $found = [];
        foreach (SeoAudit::whereNotNull('links')->get() as $a) {
            foreach ((array) $a->links as $l) {
                $found[strtok($l, '?')][] = $a->url;
            }
        }
        ksort($found);
        $paths = array_keys($found);
        $offset = max(0, (int) $request->input('offset', 0));
        $status = $offset === 0 ? [] : (array) Cache::get('seo_link_status_tmp', []);
        $status += Auditor::checkLinks(array_slice($paths, $offset, 12));
        Cache::put('seo_link_status_tmp', $status, 3600);
        $next = $offset + 12;
        if ($next >= count($paths)) {
            $broken = [];
            foreach ($status as $p => $code) {
                if ($code >= 400 || $code === 0) {
                    $broken[] = ['path' => $p, 'status' => $code, 'on' => array_values(array_unique($found[$p] ?? []))];
                }
            }
            Cache::forever('seo_broken_links', $broken);
            Cache::forever('seo_links_checked_at', now()->toDateTimeString());
        }

        return response()->json(['total' => count($paths), 'next' => min($next, count($paths)), 'finished' => $next >= count($paths)]);
    }

    /** Validate a JSON-LD override from the editor without saving. */
    public function validateSchema(Request $request): JsonResponse
    {
        [$ok, $msg] = StructuredData::validateOverride((string) $request->input('json'));

        return response()->json(['ok' => $ok, 'message' => $ok ? 'Valid JSON-LD.' : $msg]);
    }
}
