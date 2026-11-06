<?php

namespace App\Content;

/**
 * Small helpers that build page-builder layouts in PHP (used by the content seeder).
 * The result is ordinary builder JSON: every page created with it can be edited,
 * re-arranged and re-styled in Admin → Pages afterwards.
 */
class Blocks
{
    protected static int $n = 0;

    protected static string $prefix = 'x';

    /** Start a new page: makes element ids stable for the same page. */
    public static function begin(string $slug): void
    {
        self::$prefix = substr(md5($slug), 0, 4);
        self::$n = 0;
    }

    protected static function id(): string
    {
        return self::$prefix.str_pad(base_convert((string) ++self::$n, 10, 36), 6, '0', STR_PAD_LEFT);
    }

    public static function layout(array ...$sections): array
    {
        return ['version' => 1, 'sections' => $sections];
    }

    /**
     * A section. $columns: list of [width, [widgets…], columnSettings?] or a flat list of widgets (one column).
     */
    public static function section(array $columns, array $settings = []): array
    {
        if ($columns && isset($columns[0]['type'])) {
            $columns = [[100, $columns]];
        }
        $cols = [];
        foreach ($columns as $c) {
            [$w, $widgets] = $c;
            $cs = $c[2] ?? [];
            $cols[] = ['id' => self::id(), 'settings' => array_merge(['w' => $w, 'w_tablet' => $w >= 50 ? 100 : 50, 'w_mobile' => 100, 'anim' => 'none'], $cs), 'widgets' => $widgets];
        }

        return ['id' => self::id(), 'settings' => array_merge(['anim' => 'none'], $settings), 'columns' => $cols];
    }

    public static function w(string $type, array $data, array $settings = []): array
    {
        return ['id' => self::id(), 'type' => $type, 'data' => $data, 'settings' => $settings];
    }

    public static function hero(string $eyebrow, string $title, string $highlight, string $text, array $buttons, string $image = '', string $layout = 'center', string $visual = 'auto', array $rotate = []): array
    {
        return self::w('hero', compact('eyebrow', 'title', 'highlight', 'text', 'image', 'visual') + ['rotate_words' => implode(', ', $rotate), 'buttons' => $buttons, 'layout' => $image || $visual !== 'none' ? 'split' : 'center', 'title_effect' => 'words', 'image_effect' => 'float'], ['anim' => 'fade-up']);
    }

    public static function btn(string $text, string $url, string $style = 'primary', string $icon = 'fa-solid fa-arrow-right'): array
    {
        return ['text' => $text, 'url' => $url, 'style' => $style, 'icon' => $icon, 'new_tab' => false];
    }

    public static function heading(string $text, string $eyebrow = '', string $size = 'xl', string $tag = 'h2', array $settings = []): array
    {
        return self::w('heading', ['eyebrow' => $eyebrow, 'text' => $text, 'tag' => $tag, 'size' => $size, 'color' => 'white', 'effect' => 'words'], array_merge(['anim' => 'fade-up'], $settings));
    }

    public static function text(string $html, string $size = 'normal', array $settings = []): array
    {
        return self::w('text', ['content' => $html, 'size' => $size, 'columns' => '1'], array_merge(['anim' => 'fade-up', 'anim_delay' => 100], $settings));
    }

    /** $items: [[icon, title, text, url?, linkText?], …] */
    public static function boxes(array $items, string $columns = '3', string $style = 'card', array $settings = []): array
    {
        return self::w('icon_boxes', [
            'items' => array_map(fn ($i) => ['icon' => $i[0], 'title' => $i[1], 'text' => $i[2], 'url' => $i[3] ?? '', 'link_text' => $i[4] ?? 'Learn more'], $items),
            'columns' => $columns, 'style' => $style, 'icon_color' => 'cycle', 'align' => 'left',
        ], array_merge(['anim' => 'fade-up', 'anim_stagger' => 90, 'hover' => 'lift'], $settings));
    }

    public static function list(array $items, string $style = 'check', string $columns = '1', array $settings = []): array
    {
        return self::w('list', ['items' => array_map(fn ($t) => ['text' => $t], $items), 'style' => $style, 'columns' => $columns], array_merge(['anim' => 'fade-up', 'anim_stagger' => 60], $settings));
    }

    /** $items: [[title, text, icon?], …] */
    public static function steps(array $items, string $layout = 'horizontal', array $settings = []): array
    {
        return self::w('steps', ['items' => array_map(fn ($i) => ['title' => $i[0], 'text' => $i[1], 'icon' => $i[2] ?? ''], $items), 'layout' => $layout], array_merge(['anim' => 'fade-up', 'anim_stagger' => 120], $settings));
    }

    /** $nodes: [[icon, label, sub, color], …] */
    public static function flow(array $nodes): array
    {
        return self::w('workflow', ['nodes' => array_map(fn ($n) => ['icon' => $n[0], 'label' => $n[1], 'sub' => $n[2], 'color' => $n[3]], $nodes), 'animated' => true, 'speed' => 'normal'], ['anim' => 'fade-up']);
    }

    /** $items: [[question, answerHtml], …] */
    public static function faq(array $items): array
    {
        return self::w('faq', ['items' => array_map(fn ($i) => ['q' => $i[0], 'a' => str_starts_with($i[1], '<') ? $i[1] : '<p>'.$i[1].'</p>'], $items), 'first_open' => true, 'schema' => true], ['anim' => 'fade-up', 'anim_stagger' => 70]);
    }

    /** $items: [[title, html, icon?, image?], …] */
    public static function tabs(array $items, string $layout = 'top'): array
    {
        return self::w('tabs', ['items' => array_map(fn ($i) => ['title' => $i[0], 'content' => $i[1], 'icon' => $i[2] ?? '', 'image' => $i[3] ?? ''], $items), 'layout' => $layout], ['anim' => 'fade-up']);
    }

    public static function cta(string $title, string $text, array $buttons, string $style = 'gradient'): array
    {
        return self::w('cta', ['title' => $title, 'text' => $text, 'buttons' => $buttons, 'image' => '', 'style' => $style], ['anim' => 'zoom-in']);
    }

    public static function compare(string $c1, string $c2, array $rows, string $c3 = '', string $hl = '2'): array
    {
        return self::w('comparison', ['col1' => $c1, 'col2' => $c2, 'col3' => $c3, 'highlight' => $hl, 'rows' => array_map(fn ($r) => ['label' => $r[0], 'v1' => $r[1], 'v2' => $r[2], 'v3' => $r[3] ?? ''], $rows)], ['anim' => 'fade-up']);
    }

    public static function image(string $image, string $alt, array $settings = []): array
    {
        return self::w('image', ['image' => $image, 'alt' => $alt, 'caption' => '', 'url' => '', 'radius' => 'xl', 'shadow' => true, 'max_width' => 0, 'effect' => 'reveal'], array_merge(['anim' => 'none'], $settings));
    }

    public static function services(string $group = '', int $count = 0, string $exclude = '', string $columns = '3', string $heading = '', string $eyebrow = ''): array
    {
        return self::w('services_grid', ['count' => $count, 'group' => $group, 'exclude' => $exclude, 'columns' => $columns, 'heading' => $heading, 'eyebrow' => $eyebrow], ['anim' => 'fade-up', 'anim_stagger' => 80]);
    }

    public static function form(string $type, string $button, string $title = ''): array
    {
        return self::w('form', ['form_type' => $type, 'title' => $title, 'button' => $button], ['anim' => 'fade-up']);
    }

    public static function divider(int $height = 20, string $style = 'space'): array
    {
        return self::w('divider', ['style' => $style, 'height' => $height], ['anim' => 'none']);
    }

    public static function search(string $placeholder = 'Search the website…'): array
    {
        return self::w('search', ['placeholder' => $placeholder, 'button' => 'Search'], ['anim' => 'fade-up']);
    }

    /** Dark section with an animated background (page tops, CTAs). */
    public static function dark(array $extra = []): array
    {
        return array_merge(['bg' => 'gradient', 'fx' => 'orbs', 'pt' => 170, 'pb' => 110], $extra);
    }

    /** Top of a page: dark technology background with coloured light and running circuit lines. */
    public static function top(array $extra = []): array
    {
        return array_merge(['bg' => 'tech', 'fx' => 'circuit', 'pt' => 170, 'pb' => 110], $extra);
    }

    /** A colourful, slowly moving gradient band (white text) — used for contrast between white sections. */
    public static function vivid(array $extra = []): array
    {
        return array_merge(['bg' => 'vivid', 'fx' => 'stars', 'pt' => 100, 'pb' => 100], $extra);
    }

    /** A running strip of words with icons. $items: [[icon, text], …] or plain strings. */
    public static function marquee(array $items, string $style = 'pills', int $speed = 35, string $rows = '1'): array
    {
        $icons = ['fa-solid fa-bolt', 'fa-solid fa-star', 'fa-solid fa-wand-magic-sparkles', 'fa-solid fa-rocket', 'fa-solid fa-gem', 'fa-solid fa-heart'];

        return self::w('marquee', [
            'items' => array_map(fn ($i, $k) => is_array($i) ? ['icon' => $i[0], 'text' => $i[1]] : ['icon' => $icons[$k % count($icons)], 'text' => $i], $items, array_keys($items)),
            'style' => $style, 'speed' => $speed, 'direction' => 'left', 'rows' => $rows,
        ], ['anim' => 'none']);
    }

    public static function light(array $extra = []): array
    {
        return array_merge(['bg' => 'none', 'pt' => 90, 'pb' => 90], $extra);
    }

    public static function soft(array $extra = []): array
    {
        return array_merge(['bg' => 'tech2', 'pt' => 90, 'pb' => 90], $extra);
    }
}
