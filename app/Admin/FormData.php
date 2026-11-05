<?php

namespace App\Admin;

use App\Support\ImageUploader;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

/**
 * Turns posted admin forms (built from App\Admin\Schema) into clean values.
 *
 * Inputs arrive as  data[<key>]        (text values, current image paths, repeater rows)
 *              and  data_files[<key>]  (newly chosen image files, same shape)
 */
class FormData
{
    /** @var array<int, string> */
    public array $errors = [];

    public function __construct(protected ImageUploader $uploader)
    {
    }

    /**
     * Validate first (so nothing is uploaded when the form has mistakes).
     */
    public function validate(array $fields, array $input, array $files, string $prefix = ''): bool
    {
        foreach ($fields as $field) {
            $key = $field['key'];
            $label = $prefix.$field['label'];
            $value = $input[$key] ?? null;
            $file = $files[$key] ?? null;

            switch ($field['type']) {
                case 'repeater':
                    $n = 0;
                    foreach ((array) $value as $rowKey => $row) {
                        if (! is_array($row) || $this->rowIsEmpty($field['fields'], $row, $file[$rowKey] ?? [])) {
                            continue;
                        }
                        $n++;
                        $this->validate($field['fields'], $row, (array) ($file[$rowKey] ?? []), "{$label} #{$n} → ");
                    }
                    break;

                case 'image':
                    if ($file instanceof UploadedFile) {
                        $this->checkImage($file, $label);
                    } elseif (! empty($field['required']) && blank($value)) {
                        $this->errors[] = "{$label}: একটা ছবি দিন।";
                    }
                    break;

                case 'images':
                    foreach ((array) $file as $f) {
                        if ($f instanceof UploadedFile) {
                            $this->checkImage($f, $label);
                        }
                    }
                    break;

                case 'checkbox':
                    break;

                default:
                    $value = is_string($value) ? trim($value) : $value;
                    if (! empty($field['required']) && blank($value) && ! ($field['type'] === 'model_select' && filled($input[$key.'_new'] ?? null))) {
                        $this->errors[] = "{$label}: এটা খালি রাখা যাবে না।";
                        break;
                    }
                    if (blank($value)) {
                        break;
                    }
                    if ($field['type'] === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[] = "{$label}: সঠিক ইমেইল দিন।";
                    }
                    if ($field['type'] === 'url' && ! preg_match('#^(\#|/|mailto:|tel:)#i', $value) && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        $this->errors[] = "{$label}: সঠিক লিংক দিন (https:// দিয়ে শুরু)।";
                    }
                    if ($field['type'] === 'number') {
                        if (! is_numeric($value)) {
                            $this->errors[] = "{$label}: শুধু সংখ্যা দিন।";
                        } elseif ((isset($field['min']) && $value < $field['min']) || (isset($field['max']) && $value > $field['max'])) {
                            $this->errors[] = "{$label}: {$field['min']} থেকে {$field['max']}-এর মধ্যে দিন।";
                        }
                    }
                    if ($field['type'] === 'select' && ! array_key_exists($value, $field['options'])) {
                        $this->errors[] = "{$label}: তালিকা থেকে বেছে নিন।";
                    }
                    if ($field['type'] === 'datetime') {
                        try {
                            Carbon::parse($value);
                        } catch (\Throwable) {
                            $this->errors[] = "{$label}: সঠিক তারিখ দিন।";
                        }
                    }
            }
        }

        return $this->errors === [];
    }

    /**
     * Build the final values (uploads new images).
     */
    public function values(array $fields, array $input, array $files): array
    {
        $out = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $value = $input[$key] ?? null;
            $file = $files[$key] ?? null;

            $out[$key] = match ($field['type']) {
                'repeater' => $this->repeater($field, (array) $value, (array) $file),
                'image' => $file instanceof UploadedFile ? $this->uploader->store($file) : (blank($value) ? null : trim((string) $value)),
                'images' => $this->images((array) $value, (array) $file),
                'checkbox' => (bool) $value,
                'number' => blank($value) ? ($field['default'] ?? null) : $value + 0,
                'datetime' => blank($value) ? null : Carbon::parse($value),
                'html', 'textarea' => blank($value) ? null : str_replace("\r\n", "\n", trim((string) $value)),
                default => blank($value) ? null : trim((string) $value),
            };
        }

        return $out;
    }

    protected function repeater(array $field, array $rows, array $files): array
    {
        $out = [];
        foreach ($rows as $rowKey => $row) {
            if (! is_array($row) || $this->rowIsEmpty($field['fields'], $row, $files[$rowKey] ?? [])) {
                continue;
            }
            $out[] = $this->values($field['fields'], $row, (array) ($files[$rowKey] ?? []));
        }

        return $out;
    }

    protected function images(array $paths, array $files): array
    {
        $list = array_values(array_filter(array_map(fn ($p) => is_string($p) ? trim($p) : '', $paths)));
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $list[] = $this->uploader->store($file);
            }
        }

        return $list;
    }

    protected function rowIsEmpty(array $fields, array $row, mixed $files): bool
    {
        foreach ($fields as $f) {
            if ($f['type'] === 'checkbox') {
                continue;
            }
            $v = $row[$f['key']] ?? null;
            if (is_array($v) ? count(array_filter($v, fn ($x) => filled($x))) : filled($v)) {
                return false;
            }
            if (($files[$f['key']] ?? null) instanceof UploadedFile) {
                return false;
            }
        }

        return true;
    }

    protected function checkImage(UploadedFile $file, string $label): void
    {
        if ($error = $this->uploader->problem($file)) {
            $this->errors[] = "{$label}: {$error}";
        }
    }
}
