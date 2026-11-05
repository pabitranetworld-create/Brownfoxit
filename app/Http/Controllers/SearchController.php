<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Site search over published pages and services (blog, portfolio and products join in later phases). */
class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = Str::limit(trim((string) $request->query('q', '')), 100, '');
        $results = collect();

        if (mb_strlen($q) >= 2) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $words = array_values(array_filter(preg_split('/\s+/', mb_strtolower($q))));

            $pages = Page::published()->where('noindex', false)->where('slug', '!=', 'page-not-found')
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('seo_title', 'like', $like)->orWhere('seo_description', 'like', $like)->orWhere('header_subtitle', 'like', $like)->orWhere('layout', 'like', $like))
                ->limit(60)->get();

            foreach ($pages as $p) {
                $text = $this->text($p->layout ?? []);
                $score = 0;
                foreach ($words as $w) {
                    $score += 6 * substr_count(mb_strtolower($p->title), $w) + 3 * substr_count(mb_strtolower((string) $p->seo_description), $w) + substr_count(mb_strtolower($text), $w);
                }
                if ($score === 0 && ! str_contains(mb_strtolower($text.' '.$p->title), mb_strtolower($q))) {
                    continue; // matched only inside settings / JSON keys
                }
                $results->push([
                    'title' => $p->seo_title ?: $p->title,
                    'url' => $p->url(),
                    'type' => str_starts_with($p->slug, 'services/') ? 'Service' : 'Page',
                    'excerpt' => $this->excerpt($p->seo_description ?: $text, $words, $text),
                    'score' => $score,
                ]);
            }

            // services without their own page yet
            $linked = $results->pluck('url')->all();
            foreach (Service::active()->where('show_in_catalog', true)->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('short_description', 'like', $like))->get() as $s) {
                $url = $s->url ? link_to_url($s->url) : null;
                if ($url && in_array($url, $linked, true)) {
                    continue;
                }
                $results->push(['title' => $s->title, 'url' => $url ?: url('/services'), 'type' => 'Service', 'excerpt' => (string) $s->short_description, 'score' => 5]);
            }

            $results = $results->sortByDesc('score')->values();
        }

        return view('search', ['q' => $q, 'results' => $results]);
    }

    /** All visible words in a builder layout. */
    protected function text(array $layout): string
    {
        $out = [];
        array_walk_recursive($layout, function ($v, $k) use (&$out) {
            if (is_string($v) && ! in_array($k, ['id', 'type', 'icon', 'image', 'url', 'color', 'style', 'layout', 'code', 'css', 'js', 'anim', 'hover', 'effect', 'size', 'tag', 'width', 'bg', 'fx'], true) && ! preg_match('#^(uploads/|https?://|/|fa-|\#)#', $v)) {
                $out[] = html_entity_decode(strip_tags($v));
            }
        });

        return trim(preg_replace('/\s+/', ' ', implode(' ', $out)));
    }

    protected function excerpt(string $preferred, array $words, string $text): string
    {
        $source = $text;
        foreach ($words as $w) {
            $pos = mb_stripos($text, $w);
            if ($pos !== false) {
                $start = max(0, $pos - 80);

                return ($start ? '… ' : '').Str::limit(mb_substr($source, $start), 230);
            }
        }

        return Str::limit($preferred, 230);
    }
}
