<?php

namespace App\PageBuilder;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Keeps only safe formatting from rich-text fields (no scripts, styles,
 * event handlers or javascript: links).
 */
class HtmlSanitizer
{
    protected const TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'a', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'h5', 'blockquote', 'code', 'pre', 'mark', 'small', 'sub', 'sup', 'hr', 'span'];

    /** Tags whose whole content is dropped. */
    protected const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript', 'link', 'meta', 'base'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('__root') ?? $dom->getElementsByTagName('div')->item(0);
        if (! $root) {
            return e(strip_tags($html));
        }

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= self::node($child);
        }

        return trim($out);
    }

    protected static function node(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, self::DROP, true)) {
            return '';
        }

        $inner = '';
        foreach (iterator_to_array($node->childNodes) as $child) {
            $inner .= self::node($child);
        }

        if (! in_array($tag, self::TAGS, true)) {
            return $inner; // unknown tag: keep its text only
        }
        if (in_array($tag, ['br', 'hr'], true)) {
            return "<{$tag}>";
        }

        $attrs = '';
        if ($tag === 'a') {
            $href = self::safeUrl((string) $node->getAttribute('href'));
            if ($href !== '') {
                $attrs .= ' href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'"';
            }
            if ($node->getAttribute('target') === '_blank') {
                $attrs .= ' target="_blank" rel="noopener"';
            }
        }

        return "<{$tag}{$attrs}>{$inner}</{$tag}>";
    }

    /** http(s), site-relative, #anchor, mailto: and tel: only. */
    public static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $check = preg_replace('/[\x00-\x20]+/', '', strtolower($url));
        if (preg_match('#^(https?://|/|\#|mailto:|tel:|\?)#', $check) && ! str_starts_with($check, '//')) {
            return $url;
        }
        if (preg_match('#^[a-z0-9][a-z0-9\-]*(/|$)#i', $url) && ! str_contains($url, ':')) {
            return '/'.$url; // "contact" -> "/contact"
        }

        return '';
    }
}
