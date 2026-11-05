<?php

namespace App\PageBuilder;

use Illuminate\Support\Str;

/**
 * Turns whatever the builder (or anyone) posts into a clean, versioned layout:
 * unknown elements and fields are dropped, every value is type-checked,
 * rich text is sanitised and limits are enforced. Only the result is stored.
 *
 * Layout format (version 1):
 * { "version": 1, "sections": [ { "id", "settings": {}, "columns": [
 *      { "id", "settings": {}, "widgets": [ { "id", "type", "data": {}, "settings": {},
 *                                               "columns": [ … only for type "row" … ] } ] } ] } ] }
 */
class LayoutValidator
{
    public const MAX_SECTIONS = 80;
    public const MAX_COLUMNS = 6;
    public const MAX_WIDGETS = 60;

    protected array $widgets;
    protected array $seenIds = [];

    public function __construct()
    {
        $this->widgets = Registry::widgets();
    }

    public static function empty(): array
    {
        return ['version' => Registry::VERSION, 'sections' => []];
    }

    public function clean(mixed $layout): array
    {
        $this->seenIds = [];
        $layout = is_array($layout) ? $layout : [];
        $sections = [];

        foreach (array_slice(array_values((array) ($layout['sections'] ?? [])), 0, self::MAX_SECTIONS) as $s) {
            if (! is_array($s)) {
                continue;
            }
            $sections[] = [
                'id' => $this->id($s['id'] ?? null),
                'settings' => $this->fields(Registry::sectionFields(), (array) ($s['settings'] ?? [])),
                'columns' => $this->columns((array) ($s['columns'] ?? []), true),
            ];
        }

        return ['version' => Registry::VERSION, 'sections' => $sections];
    }

    protected function columns(array $columns, bool $allowRows): array
    {
        $out = [];
        foreach (array_slice(array_values($columns), 0, self::MAX_COLUMNS) as $c) {
            if (! is_array($c)) {
                continue;
            }
            $widgets = [];
            foreach (array_slice(array_values((array) ($c['widgets'] ?? [])), 0, self::MAX_WIDGETS) as $w) {
                if ($clean = $this->widget($w, $allowRows)) {
                    $widgets[] = $clean;
                }
            }
            $out[] = [
                'id' => $this->id($c['id'] ?? null),
                'settings' => $this->fields(Registry::columnFields(), (array) ($c['settings'] ?? [])),
                'widgets' => $widgets,
            ];
        }

        return $out ?: [['id' => $this->id(null), 'settings' => $this->fields(Registry::columnFields(), []), 'widgets' => []]];
    }

    protected function widget(mixed $w, bool $allowRows): ?array
    {
        if (! is_array($w) || ! isset($w['type'], $this->widgets[$w['type']])) {
            return null;
        }
        $type = $w['type'];
        if ($type === 'row' && ! $allowRows) {
            return null; // only one level of nesting
        }

        $clean = [
            'id' => $this->id($w['id'] ?? null),
            'type' => $type,
            'data' => $this->fields($this->widgets[$type]['fields'], (array) ($w['data'] ?? [])),
            'settings' => $this->fields(Registry::widgetCommonFields(), (array) ($w['settings'] ?? [])),
        ];

        if ($type === 'row') {
            $clean['columns'] = $this->columns((array) ($w['columns'] ?? []), false);
        }

        return $clean;
    }

    /** Clean a set of values against field definitions (missing values get defaults). */
    public function fields(array $defs, array $values): array
    {
        $out = [];
        foreach ($defs as $def) {
            if (($def['type'] ?? '') === 'note') {
                continue;
            }
            $out[$def['key']] = $this->value($def, $values[$def['key']] ?? null, array_key_exists($def['key'], $values));
        }

        return $out;
    }

    protected function value(array $def, mixed $v, bool $given): mixed
    {
        $default = $def['default'] ?? null;
        if (! $given) {
            return $def['type'] === 'repeater' ? $this->repeater($def, (array) ($default ?? [])) : $default;
        }

        switch ($def['type']) {
            case 'text':
                return Str::limit(trim(strip_tags((string) (is_scalar($v) ? $v : ''))), 500, '');
            case 'textarea':
                return Str::limit(trim(strip_tags(str_replace("\r\n", "\n", (string) (is_scalar($v) ? $v : '')))), 6000, '');
            case 'richtext':
                return HtmlSanitizer::clean(Str::limit((string) (is_scalar($v) ? $v : ''), 60000, ''));
            case 'code':
                return Str::limit((string) (is_scalar($v) ? $v : ''), 20000, '');
            case 'number':
                if (! is_numeric($v)) {
                    return $default;
                }
                $n = $v + 0;
                if (isset($def['min'])) {
                    $n = max($def['min'], $n);
                }
                if (isset($def['max'])) {
                    $n = min($def['max'], $n);
                }

                return $n;
            case 'select':
                return is_scalar($v) && array_key_exists((string) $v, $def['options']) ? (string) $v : $default;
            case 'checkbox':
                return filter_var($v, FILTER_VALIDATE_BOOLEAN);
            case 'color':
                return is_string($v) && preg_match('/^#[0-9a-f]{3,8}$/i', $v) ? $v : $default;
            case 'icon':
                return is_string($v) && preg_match('/^[a-z0-9 \-]{0,80}$/i', trim($v)) ? trim($v) : '';
            case 'url':
                return HtmlSanitizer::safeUrl(is_string($v) ? $v : '');
            case 'image':
                return $this->image($v);
            case 'repeater':
                return $this->repeater($def, is_array($v) ? $v : []);
        }

        return $default;
    }

    protected function repeater(array $def, array $rows): array
    {
        $out = [];
        foreach (array_slice(array_values($rows), 0, $def['max'] ?? 50) as $row) {
            if (is_array($row)) {
                $out[] = $this->fields($def['fields'], $row);
            }
        }

        return $out;
    }

    protected function image(mixed $v): string
    {
        if (! is_string($v) || ($v = trim($v)) === '') {
            return '';
        }
        if (preg_match('#^uploads/[A-Za-z0-9_\-./]+\.(jpe?g|png|gif|webp|avif|ico)$#i', $v) && ! str_contains($v, '..')) {
            return $v;
        }
        if (preg_match('#^https://[^\s"\'<>]+$#i', $v)) {
            return $v;
        }

        return '';
    }

    protected function id(mixed $id): string
    {
        $id = is_string($id) && preg_match('/^[a-z0-9]{4,24}$/', $id) && ! isset($this->seenIds[$id]) ? $id : Str::lower(Str::random(10));
        while (isset($this->seenIds[$id])) {
            $id = Str::lower(Str::random(10));
        }
        $this->seenIds[$id] = true;

        return $id;
    }
}
