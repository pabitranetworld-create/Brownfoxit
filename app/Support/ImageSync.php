<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Finds every image path the site uses ("uploads/YYYY/MM/file.ext") and copies
 * the missing ones from the old WordPress site into public/uploads.
 * Used by `php artisan site:download-images` and by the web installer.
 */
class ImageSync
{
    public const PATTERN = '#uploads/[A-Za-z0-9_\-./%]+?\.(?:png|jpe?g|gif|webp|avif|svg)#i';

    public function __construct(protected ?string $source = null)
    {
        $this->source = rtrim($source ?: (string) config('site.image_source'), '/').'/';
    }

    /**
     * @return array<string, string> saved path (relative to public/) => remote URL
     */
    public function all(): array
    {
        $paths = [];

        // 1. Files: views, seeders, config and the CSS (which uses "../../uploads/...").
        $files = collect([
            resource_path('views'),
            database_path('seeders'),
            public_path('assets/css'),
            config_path(),
        ])->filter(fn ($dir) => File::isDirectory($dir))
            ->flatMap(fn ($dir) => File::allFiles($dir));

        foreach ($files as $file) {
            $this->scan(File::get($file->getPathname()), $paths);
        }

        // 2. Database: settings JSON and image columns.
        try {
            foreach (Setting::all() as $setting) {
                $this->scan(json_encode($setting->value, JSON_UNESCAPED_SLASHES), $paths);
            }
            $columns = [
                Service::class => ['image'], Client::class => ['logo'], Project::class => ['image'],
                Testimonial::class => ['avatar'], Post::class => ['image'],
            ];
            foreach ($columns as $model => $cols) {
                foreach ($model::query()->get($cols) as $row) {
                    foreach ($cols as $col) {
                        $this->scan((string) $row->{$col}, $paths);
                    }
                }
            }
        } catch (Throwable) {
            // Database not ready: files are enough.
        }

        $images = [];
        foreach (array_keys($paths) as $path) {
            $images[$path] = $this->source.$path;
        }

        // 3. Images that live somewhere else (e.g. the Gravatar avatar).
        foreach (config('site.extra_images', []) as $path => $url) {
            $images[$path] = $url;
        }

        ksort($images);

        return $images;
    }

    /**
     * Images not yet in public/.
     *
     * @return array<string, string>
     */
    public function missing(): array
    {
        return array_filter(
            $this->all(),
            fn ($url, $path) => ! (File::exists(public_path($path)) && File::size(public_path($path)) > 0),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Download one image. Returns null on success or an error message.
     */
    public function download(string $path, string $url): ?string
    {
        try {
            $response = Http::timeout(30)->retry(2, 500, throw: false)
                ->withHeaders(['User-Agent' => 'BrownFoxIT-ImageSync/1.0'])
                ->get($url);

            if (! $response->successful() || $response->body() === '') {
                return "HTTP {$response->status()}";
            }

            $target = public_path($path);
            File::ensureDirectoryExists(dirname($target));
            File::put($target, $response->body());

            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    protected function scan(string $text, array &$paths): void
    {
        if (preg_match_all(self::PATTERN, $text, $m)) {
            foreach ($m[0] as $path) {
                if (! str_starts_with($path, 'uploads/avatars/')) { // handled by extra_images
                    $paths[$path] = true;
                }
            }
        }
    }
}
