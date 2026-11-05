<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Read a site setting with dot notation, e.g. setting('hero.title').
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('media')) {
    /**
     * Turn a stored image path into a URL.
     *  - "uploads/2026/05/a.jpg"  -> public/uploads/2026/05/a.jpg
     *  - "storage/..."            -> files uploaded from the admin panel
     *  - "https://..."            -> returned unchanged
     */
    function media(?string $path, string $fallback = ''): string
    {
        if (blank($path)) {
            return $fallback;
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}

if (! function_exists('link_to_url')) {
    /**
     * Resolve a link saved in settings/menus:
     * "https://…", "mailto:…", "tel:…" and "#…" stay as they are,
     * "/about" or "/#contact" become full site URLs.
     */
    function link_to_url(?string $url): string
    {
        if (blank($url)) {
            return '#';
        }

        if (preg_match('#^([a-z][a-z0-9+.-]*:|//|\#)#i', $url)) {
            return $url;
        }

        return url($url);
    }
}

if (! function_exists('link_visible')) {
    /**
     * Menu links to a builder page that exists but is not published (e.g. a legal draft
     * still under review) are hidden instead of sending visitors to a 404.
     */
    function link_visible(?string $url): bool
    {
        static $hidden = null;
        if (! is_string($url) || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return true;
        }
        $slug = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($slug === '') {
            return true;
        }
        if ($hidden === null) {
            $hidden = rescue(fn () => \App\Models\Page::where('is_template', false)->where('status', '!=', \App\Models\Page::STATUS_PUBLISHED)->pluck('slug')->flip()->all(), [], false);
        }

        return ! isset($hidden[$slug]);
    }
}
