<?php

namespace App\PageBuilder;

/**
 * Decides how a page-builder section looks on a page:
 * - light or dark (inner pages are white by default; dark backgrounds stay dark),
 * - how wide "Boxed" content is under the Full Width / Canvas page layouts,
 * - whether a Canvas page is "pure" custom HTML (no theme CSS/JS at all).
 */
class Theme
{
    /** Which Blade view renders the page: a Canvas page holding only custom HTML is output "pure". */
    public static function view(\App\Models\Page $page, ?array $layout): string
    {
        return $page->template === 'canvas' && self::onlyHtml($layout['sections'] ?? []) ? 'pages.pure' : 'pages.show';
    }

    /** Is the very top of the page dark (so the transparent header keeps white text)? */
    public static function topIsDark(\App\Models\Page $page, array $layout): bool
    {
        $scheme = $page->color_scheme ?: 'light';
        if (($page->template ?: 'default') === 'default' && $page->show_page_header) {
            return $scheme === 'dark';
        }
        $first = $layout['sections'][0]['settings'] ?? null;

        return $first ? self::sectionIsDark($first, $scheme) : $scheme === 'dark';
    }

    /** Is this section dark (light text) on a page with the given colour scheme? */
    public static function sectionIsDark(array $s, string $scheme): bool
    {
        if ($scheme === 'dark') {
            return true;
        }
        if (($s['fx'] ?? 'none') === 'gradient') {
            return true;
        }

        return match ($s['bg'] ?? 'none') {
            'dark', 'gradient', 'image' => true,
            'color' => self::isDarkColor((string) ($s['bg_color'] ?? '')),
            default => false, // none, card (light lavender on white pages)
        };
    }

    public static function isDarkColor(string $hex): bool
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-f]{6}/i', $hex)) {
            return true;
        }
        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split(substr($hex, 0, 6), 2));
        $lin = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

        return (0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b)) < 0.4;
    }

    /** Effective content width: "boxed" means full screen width on Full Width / Canvas pages. */
    public static function sectionWidth(array $section, string $template): string
    {
        $w = $section['settings']['width'] ?? 'boxed';
        if ($w !== 'boxed' || ! in_array($template, ['full_width', 'canvas'], true)) {
            return $w;
        }
        // A section that only holds custom HTML goes edge to edge; anything else keeps a little side space.
        return self::onlyHtml([$section]) ? 'bleed' : 'full';
    }

    /** True when every widget in these sections is an HTML widget (and there is at least one). */
    public static function onlyHtml(array $sections): bool
    {
        $found = false;
        $walk = function (array $widgets) use (&$walk, &$found): bool {
            foreach ($widgets as $w) {
                if (($w['type'] ?? '') === 'row') {
                    foreach ($w['columns'] ?? [] as $c) {
                        if (! $walk($c['widgets'] ?? [])) {
                            return false;
                        }
                    }

                    continue;
                }
                if (($w['type'] ?? '') !== 'html') {
                    return false;
                }
                $found = true;
            }

            return true;
        };
        foreach ($sections as $s) {
            foreach ($s['columns'] ?? [] as $c) {
                if (! $walk($c['widgets'] ?? [])) {
                    return false;
                }
            }
        }

        return $found;
    }

    /**
     * Keeps one HTML widget from breaking the rest of the page: closes a <style>, <script>,
     * <textarea>, <title>, <noscript> or comment that was left open (e.g. code pasted only
     * partly) and balances <div> tags so later widgets stay in their own column.
     */
    public static function closeOpenTags(string $code): string
    {
        $tail = '';
        $last = strrpos($code, '<!--');
        if ($last !== false && strpos($code, '-->', $last) === false) {
            $tail .= '-->';
        }
        foreach (['style', 'script', 'textarea', 'title', 'noscript'] as $t) {
            $open = preg_match_all('#<'.$t.'\b#i', $code);
            $close = preg_match_all('#</'.$t.'\s*>#i', $code);
            if ($open > $close) {
                $tail .= str_repeat('</'.$t.'>', $open - $close);
            }
        }
        // count divs outside scripts/styles only
        $plain = preg_replace('#<(script|style)\b.*?</\1\s*>#is', '', $code.$tail) ?? '';
        $open = preg_match_all('#<div\b#i', $plain);
        $close = preg_match_all('#</div\s*>#i', $plain);
        if ($open > $close) {
            $tail .= str_repeat('</div>', $open - $close);
        } elseif ($close > $open) {
            // surplus closing tags would close the page's own wrappers: give them something to close
            $code = str_repeat('<div>', $close - $open).$code;
        }

        return $code.$tail;
    }
}

