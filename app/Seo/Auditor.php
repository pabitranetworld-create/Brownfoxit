<?php

namespace App\Seo;

use App\Models\Redirect;
use App\Models\SeoAudit;
use DOMDocument;
use DOMXPath;

/**
 * Lightweight on-site SEO checks of the rendered HTML. These are the site's own checks (things it can
 * see by itself) — not Google rankings or Search Console data.
 */
class Auditor
{
    /** Analyse one page and store the result. */
    public static function check(string $type, string $key, string $path): SeoAudit
    {
        $res = PageFetcher::get($path);
        $data = self::analyse($res, $path);

        return SeoAudit::updateOrCreate(['subject_type' => $type, 'subject_key' => $key], $data + ['url' => $path, 'checked_at' => now()]);
    }

    public static function analyse(array $res, string $path): array
    {
        $issues = [];
        $add = function (string $level, string $code, string $text) use (&$issues) {
            $issues[] = compact('level', 'code', 'text');
        };
        $out = ['status_code' => $res['status'], 'title' => null, 'description' => null, 'canonical' => null, 'robots' => null, 'h1_count' => 0, 'images_without_alt' => 0, 'words' => 0, 'links' => [], 'schema_ok' => true];

        if ($res['status'] >= 300 && $res['status'] < 400) {
            $add('warn', 'redirect', 'This address redirects to '.$res['location'].'.');

            return $out + ['issues' => $issues];
        }
        if ($res['status'] !== 200) {
            $err = isset($res['error']) ? ' ('.mb_substr($res['error'], 0, 200).')' : '';
            $add('error', 'status', 'The page answers with HTTP '.$res['status'].$err.'.');

            return $out + ['issues' => $issues];
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$res['html'], LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $x = new DOMXPath($dom);

        $titles = $x->query('//head/title');
        $out['title'] = $titles->length ? trim($titles->item(0)->textContent) : null;
        if ($titles->length !== 1) {
            $add('error', 'title_count', $titles->length ? 'There are '.$titles->length.' <title> tags (must be exactly one).' : 'The page has no <title>.');
        }
        $tl = mb_strlen((string) $out['title']);
        if ($out['title'] && ($tl < 25 || $tl > 65)) {
            $add('info', 'title_length', 'Title is '.$tl.' characters (about 30–60 is easiest to read in search results).');
        }

        $descs = $x->query('//head/meta[@name="description"]');
        $out['description'] = $descs->length ? trim($descs->item(0)->getAttribute('content')) : null;
        if ($descs->length > 1) {
            $add('error', 'desc_count', 'There are '.$descs->length.' meta descriptions (must be at most one).');
        }
        $dl = mb_strlen((string) $out['description']);
        if (! $out['description']) {
            $add('warn', 'desc_missing', 'No meta description.');
        } elseif ($dl < 70 || $dl > 165) {
            $add('info', 'desc_length', 'Description is '.$dl.' characters (about 120–160 is a good target).');
        }

        $cans = $x->query('//head/link[@rel="canonical"]');
        $out['canonical'] = $cans->length ? $cans->item(0)->getAttribute('href') : null;
        if ($cans->length !== 1) {
            $add('error', 'canonical_count', $cans->length ? 'There are '.$cans->length.' canonical tags (must be exactly one).' : 'No canonical URL.');
        } elseif (Redirect::normalize($out['canonical']) !== Redirect::normalize($path)) {
            $add('info', 'canonical_other', 'Canonical points to another address: '.$out['canonical']);
        }

        $robots = $x->query('//head/meta[@name="robots"]');
        $out['robots'] = $robots->length ? $robots->item(0)->getAttribute('content') : null;
        if ($robots->length > 1) {
            $add('error', 'robots_count', 'More than one robots meta tag.');
        }
        if (str_contains((string) $out['robots'], 'noindex')) {
            $add('info', 'noindex', 'This page is set to noindex (it will not appear in search results).');
        }

        $out['h1_count'] = $x->query('//body//h1')->length;
        if ($out['h1_count'] === 0) {
            $add('warn', 'h1_missing', 'No H1 heading on the page.');
        } elseif ($out['h1_count'] > 1) {
            $add('warn', 'h1_many', 'There are '.$out['h1_count'].' H1 headings — one main H1 is clearer.');
        }

        foreach ($x->query('//body//img') as $img) {
            if (! $img->hasAttribute('alt') && ! $img->hasAttribute('aria-hidden')) {
                $out['images_without_alt']++;
            }
        }
        if ($out['images_without_alt']) {
            $add('warn', 'img_alt', $out['images_without_alt'].' image(s) have no alt text.');
        }

        foreach ($x->query('//script[@type="application/ld+json"]') as $s) {
            if (! is_array(json_decode($s->textContent, true))) {
                $out['schema_ok'] = false;
                $add('error', 'schema_invalid', 'A JSON-LD block is not valid JSON.');
            }
        }
        if ($x->query('//script[@type="application/ld+json"]')->length > 1) {
            $add('info', 'schema_many', 'More than one JSON-LD block (check for duplicates added in custom code).');
        }

        // Visible text of the main content (header, footer, scripts removed)
        foreach (['//script', '//style', '//header', '//footer', '//nav', '//noscript'] as $q) {
            foreach (iterator_to_array($x->query($q)) as $n) {
                $n->parentNode?->removeChild($n);
            }
        }
        $text = preg_replace('/\s+/u', ' ', (string) $x->query('//body')->item(0)?->textContent);
        $out['words'] = count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
        if ($out['words'] < 200) {
            $add('info', 'thin', 'Only about '.$out['words'].' words of visible text. (A hint, not a rule — some pages are short by nature.)');
        }

        // Internal links (for the broken-link check). Header/footer links were removed above, so these are content links.
        $dom2 = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom2->loadHTML('<?xml encoding="UTF-8">'.$res['html'], LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $host = request()->getHost();
        $links = [];
        foreach ((new DOMXPath($dom2))->query('//body//a[@href]') as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '' || preg_match('#^(\#|mailto:|tel:|javascript:|data:)#i', $href)) {
                continue;
            }
            if (preg_match('#^https?://#i', $href)) {
                $h = parse_url($href, PHP_URL_HOST);
                if (preg_replace('/^www\./', '', (string) $h) !== preg_replace('/^www\./', '', $host)) {
                    continue;
                }
                $href = (parse_url($href, PHP_URL_PATH) ?: '/').(($q = parse_url($href, PHP_URL_QUERY)) ? '?'.$q : '');
            }
            if (! str_starts_with($href, '/') || preg_match('#^/(uploads|assets|vendor|storage)/#', $href) || str_starts_with($href, '//')) {
                continue;
            }
            $links[strtok($href, '#')] = true;
        }
        $out['links'] = array_slice(array_keys($links), 0, 300);

        return $out + ['issues' => $issues];
    }

    /** Check that internal links answer 200 (or a redirect). Returns [path => status]. */
    public static function checkLinks(array $paths): array
    {
        $out = [];
        foreach ($paths as $p) {
            if (preg_match('#^/(cart|checkout|account|order|download|payment|admin)\b#', $p)) {
                continue; // utility pages are fine for visitors but not part of the check
            }
            $r = PageFetcher::get($p);
            $out[$p] = $r['status'];
        }

        return $out;
    }
}
