<?php

namespace App\PageBuilder;

/**
 * Builds the HTML attributes that connect an element's saved settings
 * to the frontend motion system (public/assets/js/motion.js + pages.css).
 */
class Attr
{
    /** FAQ entries collected while rendering, printed once as JSON-LD. */
    public static array $faq = [];

    public static function element(array $s, string $class, ?string $editorId = null, array $extraClass = [], array $style = []): string
    {
        $classes = array_merge([$class], $extraClass);

        foreach (['desktop' => 'd', 'tablet' => 't', 'mobile' => 'm'] as $k => $short) {
            if (! empty($s['hide_'.$k])) {
                $classes[] = 'hide-'.$short;
            }
        }
        if (! empty($s['text_align'])) {
            $classes[] = 'ta-'.$s['text_align'];
        }
        if (($s['hover'] ?? 'none') !== 'none') {
            $classes[] = 'hv-'.$s['hover'];
        }
        if (! empty($s['css_class'])) {
            $classes[] = preg_replace('/[^A-Za-z0-9_\- ]/', '', $s['css_class']);
        }

        if (! empty($s['mt'])) {
            $style[] = 'margin-top:'.(int) $s['mt'].'px';
        }
        if (! empty($s['mb'])) {
            $style[] = 'margin-bottom:'.(int) $s['mb'].'px';
        }

        $a = ['class' => implode(' ', array_filter($classes))];
        if ($style) {
            $a['style'] = implode(';', $style);
        }
        if (! empty($s['anchor']) && preg_match('/^[A-Za-z][A-Za-z0-9_\-]*$/', $s['anchor'])) {
            $a['id'] = $s['anchor'];
        }

        $anim = $s['anim'] ?? 'none';
        if ($anim !== 'none') {
            $a['data-anim'] = $anim;
            $a['data-anim-dur'] = (int) ($s['anim_duration'] ?? 800);
            $a['data-anim-delay'] = (int) ($s['anim_delay'] ?? 0);
            $a['data-anim-int'] = $s['anim_intensity'] ?? 'normal';
            $a['data-anim-mobile'] = $s['anim_mobile'] ?? 'on';
            $a['data-anim-reduced'] = $s['anim_reduced'] ?? 'none';
        }
        if ((int) ($s['anim_stagger'] ?? 0) > 0) {
            $a['data-stagger'] = (int) $s['anim_stagger'];
        }
        if (($s['parallax'] ?? 'none') !== 'none') {
            $a['data-parallax'] = $s['parallax'];
        }
        if ($editorId) {
            $a['data-bfi-id'] = $editorId;
        }

        $html = '';
        foreach ($a as $k => $v) {
            $html .= ' '.$k.'="'.e((string) $v).'"';
        }

        return $html;
    }

    /** Colour class for repeated items when "cycle" is chosen. */
    public static function cycle(string $color, int $i): string
    {
        $all = ['orange', 'blue', 'green', 'purple'];

        return $color === 'cycle' ? $all[$i % 4] : $color;
    }

    public static function videoEmbed(string $url): ?array
    {
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_\-]{6,20})#', $url, $m)) {
            return ['type' => 'iframe', 'src' => 'https://www.youtube-nocookie.com/embed/'.$m[1].'?rel=0', 'thumb' => 'https://i.ytimg.com/vi/'.$m[1].'/hqdefault.jpg'];
        }
        if (preg_match('#vimeo\.com/(?:video/)?(\d{5,12})#', $url, $m)) {
            return ['type' => 'iframe', 'src' => 'https://player.vimeo.com/video/'.$m[1], 'thumb' => null];
        }
        if (preg_match('#^(https://|/)[^\s]+\.(mp4|webm)(\?.*)?$#i', $url)) {
            return ['type' => 'video', 'src' => $url, 'thumb' => null];
        }

        return null;
    }
}
